<?php

namespace Tests\Feature\Auth;

use App\Jobs\SendTransactionalEmail;
use App\Models\EmailToken;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * US1 — criar conta com e-mail e senha (RN-PLAT-001, RN-PLAT-002).
 */
class CreateAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    #[Test]
    public function creates_the_account_and_returns_a_session(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/contas', [
            'name' => 'Maria Souza',
            'email' => 'maria@exemplo.com',
            'password' => 'senhaforte1',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.account.name', 'Maria Souza')
            ->assertJsonPath('data.account.email', 'maria@exemplo.com')
            ->assertJsonPath('data.account.email_verified', false)
            ->assertJsonPath('data.account.roles', ['rolezeiro'])
            ->assertJsonStructure(['message', 'data' => ['account' => ['id'], 'token', 'expires_at']]);

        $this->assertDatabaseCount('users', 1);
    }

    #[Test]
    public function the_returned_token_really_authenticates(): void
    {
        Queue::fake();

        $token = $this->postJson('/api/v1/contas', [
            'name' => 'Maria',
            'email' => 'maria@exemplo.com',
            'password' => 'senhaforte1',
        ])->json('data.token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/eu')
            ->assertOk()
            ->assertJsonPath('data.email', 'maria@exemplo.com');
    }

    #[Test]
    public function the_response_never_exposes_the_password(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/contas', [
            'name' => 'Maria',
            'email' => 'maria@exemplo.com',
            'password' => 'senhaforte1',
        ]);

        $content = $response->getContent();

        // Três coisas distintas, e nenhuma pode aparecer: o valor digitado, a
        // COLUNA `password` (mesmo hasheada) e qualquer hash bcrypt solto.
        //
        // A checagem é pela chave `"password":`, e não pela palavra: desde que
        // a API passou a responder em inglês, `signs_in_with` carrega o valor
        // legítimo `"password"` — ele diz por qual caminho a conta entra, e não
        // é segredo nenhum. Barrar a palavra solta acusaria isso como
        // vazamento, e detector que grita sem motivo ensina a ser ignorado.
        $this->assertStringNotContainsString('senhaforte1', $content);
        $this->assertStringNotContainsString('"password":', $content);
        $this->assertStringNotContainsString('$2y$', $content);
    }

    #[Test]
    public function the_password_is_stored_hashed(): void
    {
        Queue::fake();

        $this->postJson('/api/v1/contas', [
            'name' => 'Maria',
            'email' => 'maria@exemplo.com',
            'password' => 'senhaforte1',
        ])->assertCreated();

        $this->assertNotSame('senhaforte1', User::first()->password);
        $this->assertTrue(password_verify('senhaforte1', User::first()->password));
    }

    #[Test]
    public function the_email_is_normalized_before_saving(): void
    {
        Queue::fake();

        $this->postJson('/api/v1/contas', [
            'name' => 'Maria',
            'email' => '  Maria@Exemplo.COM  ',
            'password' => 'senhaforte1',
        ])->assertCreated();

        $this->assertSame('maria@exemplo.com', User::first()->email);
    }

    #[Test]
    public function queues_the_verification_email_without_blocking_use(): void
    {
        // Decisão D5: envia, mas não bloqueia. A conta já nasce utilizável.
        Queue::fake();

        $this->postJson('/api/v1/contas', [
            'name' => 'Maria',
            'email' => 'maria@exemplo.com',
            'password' => 'senhaforte1',
        ])->assertCreated()
            ->assertJsonPath('data.account.email_verified', false);

        Queue::assertPushed(SendTransactionalEmail::class);

        $this->assertDatabaseHas('email_tokens', [
            'purpose' => EmailToken::EMAIL_VERIFICATION,
        ]);
    }

    #[Test]
    public function the_verification_token_is_not_stored_in_plain_text(): void
    {
        Queue::fake();

        $this->postJson('/api/v1/contas', [
            'name' => 'Maria',
            'email' => 'maria@exemplo.com',
            'password' => 'senhaforte1',
        ])->assertCreated();

        $record = EmailToken::first();

        // 64 caracteres hexadecimais = sha256. Se aparecesse o valor em claro,
        // um vazamento de banco tornaria os links reutilizáveis.
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $record->token_hash);
    }

    #[Test]
    public function the_account_is_born_with_the_configured_role(): void
    {
        Queue::fake();

        $this->postJson('/api/v1/contas', [
            'name' => 'Maria',
            'email' => 'maria@exemplo.com',
            'password' => 'senhaforte1',
        ])->assertCreated();

        // Princípio I: papéis são perfis da mesma conta e podem se acumular.
        $this->assertTrue(User::first()->hasRole(config('bora.account.initial_role')));
    }
}
