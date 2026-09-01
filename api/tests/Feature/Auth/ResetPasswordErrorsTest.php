<?php

namespace Tests\Feature\Auth;

use App\Models\EmailToken;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * US4-3 — caminhos de erro da redefinição.
 *
 * Link de e-mail é a superfície mais exposta desta feature: ele viaja fora do
 * nosso controle, pode ser reencaminhado, fica no histórico do provedor de
 * e-mail. Por isso: uso único, prazo curto, e recusa que explica o próximo
 * passo em vez de só negar.
 */
class ResetPasswordErrorsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Queue::fake();
    }

    /** @return array{User, string} */
    private function accountWithLink(): array
    {
        $account = User::factory()->create([
            'email' => 'maria@exemplo.com',
            'password' => 'senhaAntiga1',
        ]);

        $plainText = bin2hex(random_bytes(32));
        $account->emailTokens()->create([
            'purpose' => EmailToken::PASSWORD_RESET,
            'token_hash' => hash('sha256', $plainText),
            'expires_at' => now()->addMinutes((int) config('bora.email_tokens.password_reset')),
        ]);

        return [$account, $plainText];
    }

    #[Test]
    public function the_link_works_only_once(): void
    {
        [, $link] = $this->accountWithLink();

        $this->postJson('/api/v1/senha/redefinir', ['token' => $link, 'password' => 'senhaNova123'])
            ->assertOk();

        $this->postJson('/api/v1/senha/redefinir', ['token' => $link, 'password' => 'outraSenha12'])
            ->assertStatus(410);
    }

    #[Test]
    public function reusing_the_link_does_not_change_the_password_again(): void
    {
        [$account, $link] = $this->accountWithLink();

        $this->postJson('/api/v1/senha/redefinir', ['token' => $link, 'password' => 'senhaNova123'])->assertOk();
        $hashAfterFirst = $account->fresh()->password;

        $this->postJson('/api/v1/senha/redefinir', ['token' => $link, 'password' => 'invasor12345'])
            ->assertStatus(410);

        $this->assertSame($hashAfterFirst, $account->fresh()->password);
    }

    #[Test]
    public function the_expired_link_is_rejected_with_an_explanation(): void
    {
        [$account, $link] = $this->accountWithLink();
        $account->emailTokens()->update(['expires_at' => now()->subMinute()]);

        $message = $this->postJson('/api/v1/senha/redefinir', [
            'token' => $link, 'password' => 'senhaNova123',
        ])->assertStatus(410)->json('message');

        // ux-requirements.md: erro diz o que fazer.
        $this->assertMatchesRegularExpression('/novo link|de novo|expirou/i', $message);
        $this->assertTrue(password_verify('senhaAntiga1', $account->fresh()->password));
    }

    #[Test]
    public function an_unknown_token_is_rejected(): void
    {
        $this->postJson('/api/v1/senha/redefinir', [
            'token' => bin2hex(random_bytes(32)), 'password' => 'senhaNova123',
        ])->assertStatus(410);
    }

    #[Test]
    public function a_token_of_another_purpose_does_not_work(): void
    {
        // Um link de verificação de e-mail não pode virar redefinição de senha.
        $account = User::factory()->create(['password' => 'senhaAntiga1']);
        $plainText = bin2hex(random_bytes(32));
        $account->emailTokens()->create([
            'purpose' => EmailToken::EMAIL_VERIFICATION,
            'token_hash' => hash('sha256', $plainText),
            'expires_at' => now()->addDay(),
        ]);

        $this->postJson('/api/v1/senha/redefinir', [
            'token' => $plainText, 'password' => 'senhaNova123',
        ])->assertStatus(410);

        $this->assertTrue(password_verify('senhaAntiga1', $account->fresh()->password));
    }

    #[Test]
    public function a_short_password_is_rejected_with_an_error_on_the_field(): void
    {
        [$account, $link] = $this->accountWithLink();
        $minimum = (int) config('bora.account.minimum_password_length');

        $this->postJson('/api/v1/senha/redefinir', [
            'token' => $link, 'password' => str_repeat('a', $minimum - 1),
        ])->assertStatus(422)->assertJsonValidationErrors('password');

        $this->assertTrue(password_verify('senhaAntiga1', $account->fresh()->password));
    }

    #[Test]
    public function the_link_survives_an_invalid_password(): void
    {
        // Errar o formato da senha nova não pode queimar o link — a pessoa
        // teria de pedir outro por um engano de digitação.
        [, $link] = $this->accountWithLink();

        $this->postJson('/api/v1/senha/redefinir', ['token' => $link, 'password' => 'curta'])
            ->assertStatus(422);

        $this->postJson('/api/v1/senha/redefinir', ['token' => $link, 'password' => 'senhaNova123'])
            ->assertOk();
    }

    #[Test]
    public function missing_fields_are_a_validation_error(): void
    {
        $this->postJson('/api/v1/senha/redefinir', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['token', 'password']);
    }

    #[Test]
    public function the_error_response_never_exposes_the_password(): void
    {
        [, $link] = $this->accountWithLink();

        $content = $this->postJson('/api/v1/senha/redefinir', [
            'token' => $link, 'password' => 'x',
        ])->getContent();

        $this->assertStringNotContainsString('"x"', $content);
    }
}
