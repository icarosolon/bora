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
 * US1-7 e decisão D5 — verificação envia, mas NÃO bloqueia o uso.
 */
class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Queue::fake();
    }

    /** @return array{User, string} conta e token em claro */
    private function accountWithVerificationToken(array $attributes = []): array
    {
        $account = User::factory()->create(['email_verified_at' => null, ...$attributes]);
        $plainText = bin2hex(random_bytes(32));

        $account->emailTokens()->create([
            'purpose' => EmailToken::EMAIL_VERIFICATION,
            'token_hash' => hash('sha256', $plainText),
            'expires_at' => now()->addMinutes((int) config('bora.email_tokens.email_verification')),
        ]);

        return [$account, $plainText];
    }

    #[Test]
    public function an_unverified_account_keeps_using_the_site(): void
    {
        // É o coração da D5: não verificar não pode travar nada.
        [$account] = $this->accountWithVerificationToken();
        $token = $account->createToken('celular')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/eu')
            ->assertOk()
            ->assertJsonPath('data.email_verified', false);
    }

    #[Test]
    public function the_valid_link_confirms_the_email(): void
    {
        [$account, $plainText] = $this->accountWithVerificationToken();

        $this->postJson('/api/v1/email/verificar', ['token' => $plainText])
            ->assertOk()
            ->assertJsonPath('message', 'E-mail confirmado. Obrigado!');

        $this->assertNotNull($account->fresh()->email_verified_at);
    }

    #[Test]
    public function the_link_works_only_once(): void
    {
        [, $plainText] = $this->accountWithVerificationToken();

        $this->postJson('/api/v1/email/verificar', ['token' => $plainText])->assertOk();

        $this->postJson('/api/v1/email/verificar', ['token' => $plainText])
            ->assertStatus(410);
    }

    #[Test]
    public function the_expired_link_is_rejected_with_an_explanation(): void
    {
        [$account, $plainText] = $this->accountWithVerificationToken();
        $account->emailTokens()->update(['expires_at' => now()->subMinute()]);

        $response = $this->postJson('/api/v1/email/verificar', ['token' => $plainText])
            ->assertStatus(410);

        // ux-requirements.md: erro diz o que fazer.
        $this->assertMatchesRegularExpression('/expirou|novo/i', $response->json('message'));
        $this->assertNull($account->fresh()->email_verified_at);
    }

    #[Test]
    public function an_unknown_token_is_rejected(): void
    {
        $this->postJson('/api/v1/email/verificar', ['token' => bin2hex(random_bytes(32))])
            ->assertStatus(410);
    }

    #[Test]
    public function resends_the_link_to_an_authenticated_person(): void
    {
        [$account] = $this->accountWithVerificationToken();
        $token = $account->createToken('celular')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/email/verificar/reenviar')
            ->assertOk();

        Queue::assertPushed(\App\Jobs\SendTransactionalEmail::class);
    }

    #[Test]
    public function resending_invalidates_the_previous_link(): void
    {
        // Dois links válidos ao mesmo tempo dobram a janela de ataque sem ganho:
        // a pessoa vai usar o último que recebeu.
        [$account, $old] = $this->accountWithVerificationToken();
        $session = $account->createToken('celular')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$session)
            ->postJson('/api/v1/email/verificar/reenviar')
            ->assertOk();

        $this->postJson('/api/v1/email/verificar', ['token' => $old])
            ->assertStatus(410);
    }

    #[Test]
    public function someone_already_verified_cannot_resend(): void
    {
        $account = User::factory()->create(['email_verified_at' => now()]);
        $token = $account->createToken('celular')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/email/verificar/reenviar')
            ->assertStatus(422);
    }

    #[Test]
    public function verifying_does_not_require_authentication(): void
    {
        // A pessoa costuma abrir o link no e-mail, muitas vezes noutro
        // aparelho, sem sessão. Exigir login aqui quebraria o fluxo comum.
        [, $plainText] = $this->accountWithVerificationToken();

        $this->postJson('/api/v1/email/verificar', ['token' => $plainText])->assertOk();
    }
}
