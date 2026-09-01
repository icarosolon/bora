<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolesSeeder;
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
        $this->seed(RolesSeeder::class);
    }

    private function account(array $attributes = []): User
    {
        return User::factory()->create([
            'email' => 'maria@exemplo.com',
            'password' => 'senhaforte1',
            ...$attributes,
        ]);
    }

    #[Test]
    public function signs_in_with_correct_credentials(): void
    {
        $this->account();

        $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com',
            'password' => 'senhaforte1',
        ])->assertOk()
            ->assertJsonStructure(['data' => ['account' => ['id', 'name', 'email'], 'token', 'expires_at']]);
    }

    #[Test]
    public function signs_in_even_with_the_email_written_differently(): void
    {
        $this->account();

        $this->postJson('/api/v1/sessoes', [
            'email' => '  Maria@Exemplo.COM ',
            'password' => 'senhaforte1',
        ])->assertOk();
    }

    #[Test]
    public function the_failure_message_does_not_reveal_which_field_was_wrong(): void
    {
        // FR-006: mensagem única. Dizer "e-mail não existe" entregaria quais
        // e-mails têm conta no Bora.
        $this->account();

        $withWrongPassword = $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com',
            'password' => 'senhaerrada9',
        ])->assertUnauthorized()->json('message');

        $withUnknownEmail = $this->postJson('/api/v1/sessoes', [
            'email' => 'ninguem@exemplo.com',
            'password' => 'senhaforte1',
        ])->assertUnauthorized()->json('message');

        $this->assertSame($withWrongPassword, $withUnknownEmail);
        $this->assertStringContainsString('não conferem', $withWrongPassword);
    }

    #[Test]
    public function the_message_offers_the_recovery_path(): void
    {
        $this->account();

        $message = $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com',
            'password' => 'senhaerrada9',
        ])->json('message');

        $this->assertMatchesRegularExpression('/esqueci|senha/i', $message);
    }

    #[Test]
    public function an_account_without_a_password_points_to_google(): void
    {
        // US2: a conta nasceu no Google. Repetir "e-mail ou senha não conferem"
        // deixaria a pessoa tentando uma senha que nunca existiu.
        $account = $this->account(['password' => null]);
        $account->socialAccounts()->create([
            'provider' => 'google',
            'provider_user_id' => '123',
            'linked_at' => now(),
        ]);

        $message = $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com',
            'password' => 'qualquercoisa1',
        ])->assertUnauthorized()->json('message');

        $this->assertStringContainsString('Google', $message);
    }

    #[Test]
    public function signing_in_records_the_last_access(): void
    {
        $account = $this->account();
        $this->assertNull($account->last_seen_at);

        $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com',
            'password' => 'senhaforte1',
        ])->assertOk();

        $this->assertNotNull($account->fresh()->last_seen_at);
    }

    #[Test]
    public function each_sign_in_opens_its_own_session(): void
    {
        // Entrar no celular não pode derrubar a sessão do computador.
        $this->account();

        $first = $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com', 'password' => 'senhaforte1',
        ])->json('data.token');

        $second = $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com', 'password' => 'senhaforte1',
        ])->json('data.token');

        $this->assertNotSame($first, $second);
        $this->withHeader('Authorization', 'Bearer '.$first)->getJson('/api/v1/eu')->assertOk();
        $this->withHeader('Authorization', 'Bearer '.$second)->getJson('/api/v1/eu')->assertOk();
    }

    #[Test]
    public function the_response_never_exposes_the_password(): void
    {
        $this->account();

        $content = $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com',
            'password' => 'senhaforte1',
        ])->getContent();

        // Ver CreateAccountTest: a checagem é pela chave `"password":`, porque
        // `signs_in_with` carrega o valor legítimo `"password"`.
        $this->assertStringNotContainsString('senhaforte1', $content);
        $this->assertStringNotContainsString('"password":', $content);
        $this->assertStringNotContainsString('$2y$', $content);
    }
}
