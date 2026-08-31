<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Ports\ProvedorDeIdentidade;
use Database\Seeders\PapeisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ProvedorDeIdentidadeFake;
use Tests\TestCase;

/**
 * US3 — unir credenciais confirmando com a senha (decisão D1, caminho
 * principal).
 *
 * A confirmação do titular aqui é **a senha da conta existente**: só quem já
 * podia entrar por ela pode lhe acrescentar um segundo meio de entrada. É o
 * espelho da US2-5, onde quem entrou pelo Google confirma com a sessão ativa.
 */
class UniaoPorSenhaTest extends TestCase
{
    use RefreshDatabase;

    private ProvedorDeIdentidadeFake $provedor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisSeeder::class);

        $this->provedor = new ProvedorDeIdentidadeFake;
        $this->app->instance(ProvedorDeIdentidade::class, $this->provedor);
    }

    /**
     * Cria conta por e-mail/senha e leva o fluxo do Google até o 409.
     *
     * @return array{User, string} conta e o `uniao_token`
     */
    private function uniaoPendente(string $email = 'maria@exemplo.com'): array
    {
        $conta = User::factory()->create(['email' => $email, 'password' => 'senhaforte1']);
        $conta->assignRole(config('bora.conta.papel_inicial'));

        $this->provedor->devolvendo($email);

        $resposta = $this->postJson('/api/v1/auth/google/sessoes', [
            'code' => 'x',
            'state' => $this->getJson('/api/v1/auth/google/url')->json('data.state'),
        ])->assertStatus(409);

        return [$conta, $resposta->json('data.uniao_token')];
    }

    #[Test]
    public function a_senha_correta_une_e_abre_sessao(): void
    {
        [$conta, $token] = $this->uniaoPendente();

        $this->postJson('/api/v1/uniao-credenciais', [
            'uniao_token' => $token,
            'senha' => 'senhaforte1',
        ])->assertOk()
            ->assertJsonPath('data.conta.id', $conta->id)
            ->assertJsonStructure(['message', 'data' => ['conta', 'token', 'expira_em']]);
    }

    #[Test]
    public function depois_da_uniao_existe_uma_conta_com_os_dois_caminhos(): void
    {
        [$conta, $token] = $this->uniaoPendente();

        $resposta = $this->postJson('/api/v1/uniao-credenciais', [
            'uniao_token' => $token,
            'senha' => 'senhaforte1',
        ])->assertOk();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('contas_sociais', 1);
        $this->assertEqualsCanonicalizing(
            ['senha', 'google'],
            $resposta->json('data.conta.entra_com')
        );
    }

    #[Test]
    public function depois_da_uniao_o_google_entra_direto_na_mesma_conta(): void
    {
        // É o desfecho que a pessoa espera: uniu uma vez, nunca mais é
        // perguntada.
        [$conta, $token] = $this->uniaoPendente();

        $this->postJson('/api/v1/uniao-credenciais', [
            'uniao_token' => $token,
            'senha' => 'senhaforte1',
        ])->assertOk();

        $this->provedor->devolvendo('maria@exemplo.com');

        $this->postJson('/api/v1/auth/google/sessoes', [
            'code' => 'y',
            'state' => $this->getJson('/api/v1/auth/google/url')->json('data.state'),
        ])->assertOk()
            ->assertJsonPath('data.conta.id', $conta->id);

        $this->assertDatabaseCount('users', 1);
    }

    #[Test]
    public function depois_da_uniao_a_senha_continua_valendo(): void
    {
        [$conta, $token] = $this->uniaoPendente();

        $this->postJson('/api/v1/uniao-credenciais', [
            'uniao_token' => $token,
            'senha' => 'senhaforte1',
        ])->assertOk();

        $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com',
            'senha' => 'senhaforte1',
        ])->assertOk()->assertJsonPath('data.conta.id', $conta->id);
    }

    #[Test]
    public function o_token_de_uniao_e_de_uso_unico(): void
    {
        [, $token] = $this->uniaoPendente();

        $this->postJson('/api/v1/uniao-credenciais', [
            'uniao_token' => $token, 'senha' => 'senhaforte1',
        ])->assertOk();

        $this->postJson('/api/v1/uniao-credenciais', [
            'uniao_token' => $token, 'senha' => 'senhaforte1',
        ])->assertStatus(410);
    }

    #[Test]
    public function a_mensagem_de_sucesso_explica_o_que_mudou(): void
    {
        [, $token] = $this->uniaoPendente();

        $mensagem = $this->postJson('/api/v1/uniao-credenciais', [
            'uniao_token' => $token, 'senha' => 'senhaforte1',
        ])->json('message');

        $this->assertStringContainsString('Google', $mensagem);
        $this->assertStringContainsString('senha', $mensagem);
    }

    #[Test]
    public function a_uniao_nao_altera_o_email_nem_o_nome_da_conta(): void
    {
        // O provedor externo não redefine a identidade de uma conta existente.
        $conta = User::factory()->create([
            'email' => 'maria@exemplo.com',
            'name' => 'Maria Souza',
            'password' => 'senhaforte1',
        ]);
        $conta->assignRole(config('bora.conta.papel_inicial'));

        $this->provedor->devolvendo('maria@exemplo.com', nome: 'Outro Nome No Google');

        $token = $this->postJson('/api/v1/auth/google/sessoes', [
            'code' => 'x',
            'state' => $this->getJson('/api/v1/auth/google/url')->json('data.state'),
        ])->json('data.uniao_token');

        $this->postJson('/api/v1/uniao-credenciais', [
            'uniao_token' => $token, 'senha' => 'senhaforte1',
        ])->assertOk();

        $this->assertSame('Maria Souza', $conta->fresh()->name);
        $this->assertSame('maria@exemplo.com', $conta->fresh()->email);
    }
}
