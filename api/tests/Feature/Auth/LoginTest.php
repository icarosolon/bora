<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * US1-2 e US1-6 — entrar com e-mail e senha.
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisSeeder::class);
    }

    private function conta(array $atributos = []): User
    {
        return User::factory()->create([
            'email' => 'maria@exemplo.com',
            'password' => 'senhaforte1',
            ...$atributos,
        ]);
    }

    #[Test]
    public function entra_com_credenciais_corretas(): void
    {
        $this->conta();

        $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com',
            'senha' => 'senhaforte1',
        ])->assertOk()
            ->assertJsonStructure(['data' => ['conta' => ['id', 'nome', 'email'], 'token', 'expira_em']]);
    }

    #[Test]
    public function entra_mesmo_com_o_email_escrito_diferente(): void
    {
        $this->conta();

        $this->postJson('/api/v1/sessoes', [
            'email' => '  Maria@Exemplo.COM ',
            'senha' => 'senhaforte1',
        ])->assertOk();
    }

    #[Test]
    public function a_mensagem_de_falha_nao_revela_qual_campo_errou(): void
    {
        // FR-006: mensagem única. Dizer "e-mail não existe" entregaria quais
        // e-mails têm conta no Bora.
        $this->conta();

        $comSenhaErrada = $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com',
            'senha' => 'senhaerrada9',
        ])->assertUnauthorized()->json('message');

        $comEmailInexistente = $this->postJson('/api/v1/sessoes', [
            'email' => 'ninguem@exemplo.com',
            'senha' => 'senhaforte1',
        ])->assertUnauthorized()->json('message');

        $this->assertSame($comSenhaErrada, $comEmailInexistente);
        $this->assertStringContainsString('não conferem', $comSenhaErrada);
    }

    #[Test]
    public function a_mensagem_oferece_o_caminho_de_recuperacao(): void
    {
        $this->conta();

        $mensagem = $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com',
            'senha' => 'senhaerrada9',
        ])->json('message');

        $this->assertMatchesRegularExpression('/esqueci|senha/i', $mensagem);
    }

    #[Test]
    public function conta_sem_senha_orienta_a_entrar_pelo_google(): void
    {
        // US2: a conta nasceu no Google. Repetir "e-mail ou senha não conferem"
        // deixaria a pessoa tentando uma senha que nunca existiu.
        $conta = $this->conta(['password' => null]);
        $conta->contasSociais()->create([
            'provedor' => 'google',
            'provedor_user_id' => '123',
            'vinculado_em' => now(),
        ]);

        $mensagem = $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com',
            'senha' => 'qualquercoisa1',
        ])->assertUnauthorized()->json('message');

        $this->assertStringContainsString('Google', $mensagem);
    }

    #[Test]
    public function o_login_registra_o_ultimo_acesso(): void
    {
        $conta = $this->conta();
        $this->assertNull($conta->ultimo_acesso_em);

        $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com',
            'senha' => 'senhaforte1',
        ])->assertOk();

        $this->assertNotNull($conta->fresh()->ultimo_acesso_em);
    }

    #[Test]
    public function cada_login_abre_uma_sessao_propria(): void
    {
        // Entrar no celular não pode derrubar a sessão do computador.
        $this->conta();

        $primeiro = $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com', 'senha' => 'senhaforte1',
        ])->json('data.token');

        $segundo = $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com', 'senha' => 'senhaforte1',
        ])->json('data.token');

        $this->assertNotSame($primeiro, $segundo);
        $this->withHeader('Authorization', 'Bearer '.$primeiro)->getJson('/api/v1/eu')->assertOk();
        $this->withHeader('Authorization', 'Bearer '.$segundo)->getJson('/api/v1/eu')->assertOk();
    }

    #[Test]
    public function a_resposta_nunca_expoe_a_senha(): void
    {
        $this->conta();

        $conteudo = $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com',
            'senha' => 'senhaforte1',
        ])->getContent();

        $this->assertStringNotContainsString('senhaforte1', $conteudo);
        $this->assertStringNotContainsString('password', $conteudo);
    }
}
