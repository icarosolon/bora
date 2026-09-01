<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Ports\IdentityProvider;
use App\Support\AuditLog;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\Support\FakeIdentityProvider;
use Tests\TestCase;

/**
 * US2 — `POST /api/v1/auth/google/sessoes`.
 *
 * Inclui a PROVA DO BLOQUEIO do Princípio I pelo caminho do Google: repetir o
 * login nunca cria uma segunda conta.
 */
class GoogleSessionTest extends TestCase
{
    use RefreshDatabase;

    private FakeIdentityProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);

        $this->provider = new FakeIdentityProvider;
        $this->app->instance(IdentityProvider::class, $this->provider);
    }

    /** Faz o passo 1 (obter state) e devolve um state válido. */
    private function validState(): string
    {
        return $this->getJson('/api/v1/auth/google/url')->json('data.state');
    }

    private function signInWithGoogle(?string $state = null, string $code = 'codigo-valido')
    {
        return $this->postJson('/api/v1/auth/google/sessoes', [
            'code' => $code,
            'state' => $state ?? $this->validState(),
        ]);
    }

    #[Test]
    public function a_new_email_creates_the_account_already_verified(): void
    {
        $this->provider->returning('maria@gmail.com');

        $this->signInWithGoogle()
            ->assertOk()
            ->assertJsonPath('data.account.email', 'maria@gmail.com')
            // O Google já verificou o e-mail — não faz sentido pedir de novo.
            ->assertJsonPath('data.account.email_verified', true)
            ->assertJsonPath('data.account.roles', ['rolezeiro'])
            ->assertJsonStructure(['data' => ['account', 'token', 'expires_at']]);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('social_accounts', [
            'provider' => 'google',
            'provider_user_id' => 'google-123',
        ]);
    }

    #[Test]
    public function an_account_created_by_google_is_born_without_a_password(): void
    {
        $this->provider->returning('maria@gmail.com');

        $this->signInWithGoogle()->assertOk();

        $this->assertNull(User::first()->password);
        $this->assertFalse(User::first()->hasPassword());
    }

    #[Test]
    public function signing_in_again_reuses_the_same_account_and_does_not_duplicate(): void
    {
        // PROVA DO BLOQUEIO — Princípio I pelo caminho do Google.
        $this->provider->returning('maria@gmail.com');

        $this->signInWithGoogle()->assertOk();
        $this->signInWithGoogle()->assertOk();
        $this->signInWithGoogle()->assertOk();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('social_accounts', 1);
    }

    #[Test]
    public function the_returned_token_authenticates(): void
    {
        $this->provider->returning('maria@gmail.com');

        $token = $this->signInWithGoogle()->json('data.token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/eu')
            ->assertOk()
            ->assertJsonPath('data.signs_in_with', ['google']);
    }

    #[Test]
    public function the_email_from_the_provider_is_normalized(): void
    {
        $this->provider->returning('Maria@GMAIL.com');

        $this->signInWithGoogle()->assertOk();

        $this->assertSame('maria@gmail.com', User::first()->email);
    }

    #[Test]
    public function the_state_is_single_use(): void
    {
        // Sem consumo, um state capturado valeria para sempre.
        $this->provider->returning('maria@gmail.com');
        $state = $this->validState();

        $this->signInWithGoogle($state)->assertOk();
        $this->signInWithGoogle($state)->assertStatus(422);
    }

    #[Test]
    public function an_unknown_state_is_rejected(): void
    {
        $this->provider->returning('maria@gmail.com');

        $this->signInWithGoogle('state-que-ninguem-emitiu')->assertStatus(422);

        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function creation_via_google_is_audited(): void
    {
        $this->provider->returning('maria@gmail.com');

        $this->signInWithGoogle()->assertOk();

        $record = Activity::where('event', AuditLog::ACCOUNT_CREATED)->first();

        $this->assertNotNull($record);
        $this->assertSame('google_signup', $record->properties['origin'] ?? null);
    }

    #[Test]
    public function stores_only_what_is_necessary_from_the_provider(): void
    {
        // Princípio III: só nome, e-mail e identificador do provedor.
        $this->provider->returning('maria@gmail.com');

        $this->signInWithGoogle()->assertOk();

        $link = \App\Models\SocialAccount::first();

        $this->assertSame('google', $link->provider);
        $this->assertSame('google-123', $link->provider_user_id);
        $this->assertSame('maria@gmail.com', $link->provider_email);
    }
}
