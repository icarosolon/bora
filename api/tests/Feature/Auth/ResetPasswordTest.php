<?php

namespace Tests\Feature\Auth;

use App\Models\EmailToken;
use App\Models\User;
use App\Support\AuditLog;
use Database\Seeders\RolesSeeder;
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
class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Queue::fake();
    }

    /** @return array{User, string} conta e o token em claro do link */
    private function accountWithLink(): array
    {
        $account = User::factory()->create([
            'email' => 'maria@exemplo.com',
            'password' => 'senhaAntiga1',
        ]);
        $account->assignRole(config('bora.account.initial_role'));

        $plainText = bin2hex(random_bytes(32));
        $account->emailTokens()->create([
            'purpose' => EmailToken::PASSWORD_RESET,
            'token_hash' => hash('sha256', $plainText),
            'expires_at' => now()->addMinutes((int) config('bora.email_tokens.password_reset')),
        ]);

        return [$account, $plainText];
    }

    #[Test]
    public function the_new_password_takes_effect(): void
    {
        [, $link] = $this->accountWithLink();

        $this->postJson('/api/v1/senha/redefinir', [
            'token' => $link, 'password' => 'senhaNova123',
        ])->assertOk();

        $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com', 'password' => 'senhaNova123',
        ])->assertOk();
    }

    #[Test]
    public function the_old_password_stops_working(): void
    {
        [, $link] = $this->accountWithLink();

        $this->postJson('/api/v1/senha/redefinir', [
            'token' => $link, 'password' => 'senhaNova123',
        ])->assertOk();

        $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com', 'password' => 'senhaAntiga1',
        ])->assertUnauthorized();
    }

    #[Test]
    public function open_sessions_are_revoked(): void
    {
        [$account, $link] = $this->accountWithLink();

        $phone = $account->createToken('celular')->plainTextToken;
        $computer = $account->createToken('computador')->plainTextToken;

        $this->postJson('/api/v1/senha/redefinir', [
            'token' => $link, 'password' => 'senhaNova123',
        ])->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$phone)->getJson('/api/v1/eu')->assertUnauthorized();

        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$computer)->getJson('/api/v1/eu')->assertUnauthorized();
    }

    #[Test]
    public function does_not_sign_in_automatically(): void
    {
        // Decisão consciente: redefinir a senha NÃO abre sessão. Quem chegou
        // até aqui provou ler o e-mail, não que é a pessoa no aparelho — e o
        // link pode ter sido aberto num computador emprestado.
        [, $link] = $this->accountWithLink();

        $response = $this->postJson('/api/v1/senha/redefinir', [
            'token' => $link, 'password' => 'senhaNova123',
        ])->assertOk();

        $this->assertNull($response->json('data.token'));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[Test]
    public function the_message_says_the_next_step(): void
    {
        [, $link] = $this->accountWithLink();

        $message = $this->postJson('/api/v1/senha/redefinir', [
            'token' => $link, 'password' => 'senhaNova123',
        ])->json('message');

        $this->assertMatchesRegularExpression('/entrar/i', $message);
    }

    #[Test]
    public function the_password_is_stored_hashed(): void
    {
        [$account, $link] = $this->accountWithLink();

        $this->postJson('/api/v1/senha/redefinir', [
            'token' => $link, 'password' => 'senhaNova123',
        ])->assertOk();

        $this->assertNotSame('senhaNova123', $account->fresh()->password);
        $this->assertTrue(password_verify('senhaNova123', $account->fresh()->password));
    }

    #[Test]
    public function the_operation_is_audited_without_sensitive_values(): void
    {
        [, $link] = $this->accountWithLink();

        $this->postJson('/api/v1/senha/redefinir', [
            'token' => $link, 'password' => 'senhaNova123',
        ])->assertOk();

        $record = Activity::where('event', AuditLog::PASSWORD_RESET)->first();

        $this->assertNotNull($record);
        $this->assertFalse(AuditLog::containsSensitiveData($record));
        $this->assertStringNotContainsString('senhaNova123', json_encode($record->properties));
    }

    #[Test]
    public function resetting_does_not_require_authentication(): void
    {
        // Quem esqueceu a senha não consegue entrar — exigir sessão aqui
        // fecharia a única porta de saída.
        [, $link] = $this->accountWithLink();

        $this->postJson('/api/v1/senha/redefinir', [
            'token' => $link, 'password' => 'senhaNova123',
        ])->assertOk();
    }
}
