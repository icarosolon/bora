<?php

namespace Tests\Feature\Auth;

use App\Jobs\EnviarEmailTransacional;
use App\Models\TokenDeEmail;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * US1 — criar conta com e-mail e senha (RN-PLAT-001, RN-PLAT-002).
 */
class CriarContaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisSeeder::class);
    }

    #[Test]
    public function cria_a_conta_e_devolve_sessao(): void
    {
        Queue::fake();

        $resposta = $this->postJson('/api/v1/contas', [
            'nome' => 'Maria Souza',
            'email' => 'maria@exemplo.com',
            'senha' => 'senhaforte1',
        ]);

        $resposta->assertCreated()
            ->assertJsonPath('data.conta.nome', 'Maria Souza')
            ->assertJsonPath('data.conta.email', 'maria@exemplo.com')
            ->assertJsonPath('data.conta.email_verificado', false)
            ->assertJsonPath('data.conta.papeis', ['rolezeiro'])
            ->assertJsonStructure(['message', 'data' => ['conta' => ['id'], 'token', 'expira_em']]);

        $this->assertDatabaseCount('users', 1);
    }

    #[Test]
    public function o_token_devolvido_autentica_de_verdade(): void
    {
        Queue::fake();

        $token = $this->postJson('/api/v1/contas', [
            'nome' => 'Maria',
            'email' => 'maria@exemplo.com',
            'senha' => 'senhaforte1',
        ])->json('data.token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/eu')
            ->assertOk()
            ->assertJsonPath('data.email', 'maria@exemplo.com');
    }

    #[Test]
    public function a_resposta_nunca_expoe_a_senha(): void
    {
        Queue::fake();

        $resposta = $this->postJson('/api/v1/contas', [
            'nome' => 'Maria',
            'email' => 'maria@exemplo.com',
            'senha' => 'senhaforte1',
        ]);

        $this->assertStringNotContainsString('senhaforte1', $resposta->getContent());
        $this->assertStringNotContainsString('password', $resposta->getContent());
    }

    #[Test]
    public function a_senha_e_guardada_com_hash(): void
    {
        Queue::fake();

        $this->postJson('/api/v1/contas', [
            'nome' => 'Maria',
            'email' => 'maria@exemplo.com',
            'senha' => 'senhaforte1',
        ])->assertCreated();

        $this->assertNotSame('senhaforte1', User::first()->password);
        $this->assertTrue(password_verify('senhaforte1', User::first()->password));
    }

    #[Test]
    public function o_email_e_normalizado_antes_de_gravar(): void
    {
        Queue::fake();

        $this->postJson('/api/v1/contas', [
            'nome' => 'Maria',
            'email' => '  Maria@Exemplo.COM  ',
            'senha' => 'senhaforte1',
        ])->assertCreated();

        $this->assertSame('maria@exemplo.com', User::first()->email);
    }

    #[Test]
    public function enfileira_o_email_de_verificacao_sem_bloquear_o_uso(): void
    {
        // Decisão D5: envia, mas não bloqueia. A conta já nasce utilizável.
        Queue::fake();

        $this->postJson('/api/v1/contas', [
            'nome' => 'Maria',
            'email' => 'maria@exemplo.com',
            'senha' => 'senhaforte1',
        ])->assertCreated()
            ->assertJsonPath('data.conta.email_verificado', false);

        Queue::assertPushed(EnviarEmailTransacional::class);

        $this->assertDatabaseHas('tokens_de_email', [
            'finalidade' => TokenDeEmail::VERIFICACAO_EMAIL,
        ]);
    }

    #[Test]
    public function o_token_de_verificacao_nao_e_guardado_em_claro(): void
    {
        Queue::fake();

        $this->postJson('/api/v1/contas', [
            'nome' => 'Maria',
            'email' => 'maria@exemplo.com',
            'senha' => 'senhaforte1',
        ])->assertCreated();

        $registro = TokenDeEmail::first();

        // 64 caracteres hexadecimais = sha256. Se aparecesse o valor em claro,
        // um vazamento de banco tornaria os links reutilizáveis.
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $registro->token_hash);
    }

    #[Test]
    public function a_conta_nasce_com_o_papel_configurado(): void
    {
        Queue::fake();

        $this->postJson('/api/v1/contas', [
            'nome' => 'Maria',
            'email' => 'maria@exemplo.com',
            'senha' => 'senhaforte1',
        ])->assertCreated();

        // Princípio I: papéis são perfis da mesma conta e podem se acumular.
        $this->assertTrue(User::first()->hasRole(config('bora.conta.papel_inicial')));
    }
}
