<?php

namespace Tests\Feature\Auth;

use App\Models\TokenDeEmail;
use App\Models\User;
use App\Support\Auditoria;
use Database\Seeders\PapeisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * US4 — redefinir a senha pelo link (FR-015).
 *
 * Duas garantias que não são detalhe:
 *
 * 1. A senha antiga **deixa de valer** na hora.
 * 2. As sessões abertas são **revogadas**. A recuperação de senha é o caminho
 *    de quem pode ter tido a conta comprometida; manter sessões vivas do
 *    invasor esvaziaria o sentido da operação.
 */
class RedefinirSenhaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisSeeder::class);
        Queue::fake();
    }

    /** @return array{User, string} conta e o token em claro do link */
    private function contaComLink(): array
    {
        $conta = User::factory()->create([
            'email' => 'maria@exemplo.com',
            'password' => 'senhaAntiga1',
        ]);
        $conta->assignRole(config('bora.conta.papel_inicial'));

        $emClaro = bin2hex(random_bytes(32));
        $conta->tokensDeEmail()->create([
            'finalidade' => TokenDeEmail::REDEFINICAO_SENHA,
            'token_hash' => hash('sha256', $emClaro),
            'expira_em' => now()->addMinutes((int) config('bora.tokens_de_email.redefinicao_senha')),
        ]);

        return [$conta, $emClaro];
    }

    #[Test]
    public function a_senha_nova_passa_a_valer(): void
    {
        [, $link] = $this->contaComLink();

        $this->postJson('/api/v1/senha/redefinir', [
            'token' => $link, 'senha' => 'senhaNova123',
        ])->assertOk();

        $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com', 'senha' => 'senhaNova123',
        ])->assertOk();
    }

    #[Test]
    public function a_senha_antiga_deixa_de_valer(): void
    {
        [, $link] = $this->contaComLink();

        $this->postJson('/api/v1/senha/redefinir', [
            'token' => $link, 'senha' => 'senhaNova123',
        ])->assertOk();

        $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com', 'senha' => 'senhaAntiga1',
        ])->assertUnauthorized();
    }

    #[Test]
    public function as_sessoes_abertas_sao_revogadas(): void
    {
        [$conta, $link] = $this->contaComLink();

        $celular = $conta->createToken('celular')->plainTextToken;
        $computador = $conta->createToken('computador')->plainTextToken;

        $this->postJson('/api/v1/senha/redefinir', [
            'token' => $link, 'senha' => 'senhaNova123',
        ])->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$celular)->getJson('/api/v1/eu')->assertUnauthorized();

        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$computador)->getJson('/api/v1/eu')->assertUnauthorized();
    }

    #[Test]
    public function nao_faz_login_automatico(): void
    {
        // Decisão consciente: redefinir a senha NÃO abre sessão. Quem chegou
        // até aqui provou ler o e-mail, não que é a pessoa no aparelho — e o
        // link pode ter sido aberto num computador emprestado.
        [, $link] = $this->contaComLink();

        $resposta = $this->postJson('/api/v1/senha/redefinir', [
            'token' => $link, 'senha' => 'senhaNova123',
        ])->assertOk();

        $this->assertNull($resposta->json('data.token'));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[Test]
    public function a_mensagem_diz_o_proximo_passo(): void
    {
        [, $link] = $this->contaComLink();

        $mensagem = $this->postJson('/api/v1/senha/redefinir', [
            'token' => $link, 'senha' => 'senhaNova123',
        ])->json('message');

        $this->assertMatchesRegularExpression('/entrar/i', $mensagem);
    }

    #[Test]
    public function a_senha_e_guardada_com_hash(): void
    {
        [$conta, $link] = $this->contaComLink();

        $this->postJson('/api/v1/senha/redefinir', [
            'token' => $link, 'senha' => 'senhaNova123',
        ])->assertOk();

        $this->assertNotSame('senhaNova123', $conta->fresh()->password);
        $this->assertTrue(password_verify('senhaNova123', $conta->fresh()->password));
    }

    #[Test]
    public function a_operacao_e_auditada_sem_valores_sensiveis(): void
    {
        [, $link] = $this->contaComLink();

        $this->postJson('/api/v1/senha/redefinir', [
            'token' => $link, 'senha' => 'senhaNova123',
        ])->assertOk();

        $registro = Activity::where('event', Auditoria::SENHA_REDEFINIDA)->first();

        $this->assertNotNull($registro);
        $this->assertFalse(Auditoria::contemDadoSensivel($registro));
        $this->assertStringNotContainsString('senhaNova123', json_encode($registro->properties));
    }

    #[Test]
    public function redefinir_nao_exige_estar_autenticado(): void
    {
        // Quem esqueceu a senha não consegue entrar — exigir sessão aqui
        // fecharia a única porta de saída.
        [, $link] = $this->contaComLink();

        $this->postJson('/api/v1/senha/redefinir', [
            'token' => $link, 'senha' => 'senhaNova123',
        ])->assertOk();
    }
}
