<?php

namespace Tests\Feature\Auth;

use App\Models\TokenDeEmail;
use App\Models\User;
use App\Ports\ProvedorDeIdentidade;
use Database\Seeders\PapeisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ProvedorDeIdentidadeFake;
use Tests\TestCase;

/**
 * PROVA DO BLOQUEIO — Princípio I (NON-NEGOTIABLE), cenário US3-6.
 *
 * **O teste mais importante da spec 001.** A união é o único ponto do produto
 * em que duas identidades se encontram, e portanto o único em que uma conta
 * paralela poderia nascer. A invariante é dura: **em QUALQUER desfecho** —
 * confirmada, cancelada, expirada, senha errada, link usado duas vezes — o
 * número de contas com aquele e-mail é exatamente um.
 *
 * Se este arquivo ficar vermelho, o produto violou a constituição.
 */
class UniaoInvarianteTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL = 'maria@exemplo.com';

    private ProvedorDeIdentidadeFake $provedor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisSeeder::class);

        $this->provedor = new ProvedorDeIdentidadeFake;
        $this->app->instance(ProvedorDeIdentidade::class, $this->provedor);
    }

    private function contaComSenha(): User
    {
        $conta = User::factory()->create(['email' => self::EMAIL, 'password' => 'senhaforte1']);
        $conta->assignRole(config('bora.conta.papel_inicial'));

        return $conta;
    }

    private function pedirUniao(): string
    {
        $this->provedor->devolvendo(self::EMAIL);

        return $this->postJson('/api/v1/auth/google/sessoes', [
            'code' => 'x',
            'state' => $this->getJson('/api/v1/auth/google/url')->json('data.state'),
        ])->assertStatus(409)->json('data.uniao_token');
    }

    private function assertUmaContaSo(string $ondeFalhou): void
    {
        $this->assertSame(
            1,
            User::where('email', self::EMAIL)->count(),
            "Princípio I violado em: {$ondeFalhou}"
        );
    }

    #[Test]
    public function uma_conta_apos_uniao_confirmada(): void
    {
        $this->contaComSenha();
        $token = $this->pedirUniao();

        $this->postJson('/api/v1/uniao-credenciais', [
            'uniao_token' => $token, 'senha' => 'senhaforte1',
        ])->assertOk();

        $this->assertUmaContaSo('união confirmada');
        $this->assertDatabaseCount('contas_sociais', 1);
    }

    #[Test]
    public function uma_conta_quando_a_pessoa_apenas_desiste(): void
    {
        // Cancelar é simplesmente não confirmar. Nada deve ter sido gravado no
        // passo do 409.
        $this->contaComSenha();
        $this->pedirUniao();

        $this->assertUmaContaSo('união abandonada');
        $this->assertDatabaseCount('contas_sociais', 0);
    }

    #[Test]
    public function uma_conta_quando_a_senha_esta_errada(): void
    {
        $this->contaComSenha();
        $token = $this->pedirUniao();

        $this->postJson('/api/v1/uniao-credenciais', [
            'uniao_token' => $token, 'senha' => 'senhaerrada9',
        ])->assertUnauthorized();

        $this->assertUmaContaSo('senha errada');
        $this->assertDatabaseCount('contas_sociais', 0);
    }

    #[Test]
    public function uma_conta_quando_o_token_expira(): void
    {
        $this->contaComSenha();
        $token = $this->pedirUniao();

        $this->travel((int) config('bora.uniao.validade_minutos') + 1)->minutes();

        $this->postJson('/api/v1/uniao-credenciais', [
            'uniao_token' => $token, 'senha' => 'senhaforte1',
        ])->assertStatus(410);

        $this->travelBack();
        $this->assertUmaContaSo('token expirado');
        $this->assertDatabaseCount('contas_sociais', 0);
    }

    #[Test]
    public function uma_conta_apos_varias_tentativas_de_uniao(): void
    {
        // A pessoa tenta, desiste, tenta de novo, confirma. Cada tentativa
        // emite um token novo; nenhuma delas pode gerar conta.
        $this->contaComSenha();

        $this->pedirUniao();
        $this->pedirUniao();
        $ultimo = $this->pedirUniao();

        $this->assertUmaContaSo('três tentativas sem confirmar');

        $this->postJson('/api/v1/uniao-credenciais', [
            'uniao_token' => $ultimo, 'senha' => 'senhaforte1',
        ])->assertOk();

        $this->assertUmaContaSo('confirmada na terceira tentativa');
        $this->assertDatabaseCount('contas_sociais', 1);
    }

    #[Test]
    public function uma_conta_mesmo_confirmando_por_senha_e_por_link(): void
    {
        // Os dois caminhos ficam válidos ao mesmo tempo (quem pediu o link pode
        // lembrar a senha). Usar os dois não pode gerar vínculo duplicado.
        $this->contaComSenha();
        $token = $this->pedirUniao();

        $this->postJson('/api/v1/uniao-credenciais/link', ['uniao_token' => $token])->assertOk();

        $registro = TokenDeEmail::where('finalidade', TokenDeEmail::UNIAO_CREDENCIAIS)->first();
        $this->assertNotNull($registro);

        $this->postJson('/api/v1/uniao-credenciais', [
            'uniao_token' => $token, 'senha' => 'senhaforte1',
        ])->assertOk();

        // O link ficou obsoleto: a união já aconteceu.
        $this->assertUmaContaSo('senha depois de pedir o link');
        $this->assertDatabaseCount('contas_sociais', 1);
    }

    #[Test]
    public function o_indice_unico_impede_vinculo_duplicado_no_banco(): void
    {
        // Última linha de defesa: se a borda for contornada, o banco recusa.
        $conta = $this->contaComSenha();
        $conta->contasSociais()->create([
            'provedor' => 'google', 'provedor_user_id' => 'google-123', 'vinculado_em' => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        $conta->contasSociais()->create([
            'provedor' => 'google', 'provedor_user_id' => 'google-999', 'vinculado_em' => now(),
        ]);
    }
}
