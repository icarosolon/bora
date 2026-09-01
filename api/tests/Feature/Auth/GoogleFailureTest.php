<?php

namespace Tests\Feature\Auth;

use App\Ports\IdentityProvider;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\FakeIdentityProvider;
use Tests\TestCase;

/**
 * US2-3 — cancelamento, recusa, falha do provedor e provedor sem e-mail.
 *
 * Os quatro têm a MESMA resposta de propósito: para quem está na tela, o
 * próximo passo é idêntico — tentar de novo ou usar e-mail e senha. Distinguir
 * "você cancelou" de "o Google caiu" não muda nada para a pessoa e só multiplica
 * texto a manter.
 *
 * O que é inegociável em todos: **nenhuma conta criada, nenhum estado parcial**.
 */
class GoogleFailureTest extends TestCase
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

    private function signInWithGoogle()
    {
        return $this->postJson('/api/v1/auth/google/sessoes', [
            'code' => 'qualquer',
            'state' => $this->getJson('/api/v1/auth/google/url')->json('data.state'),
        ]);
    }

    #[Test]
    public function a_provider_failure_returns_401_without_creating_an_account(): void
    {
        $this->provider->failing('usuário cancelou');

        $this->signInWithGoogle()->assertUnauthorized();

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('social_accounts', 0);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[Test]
    public function the_message_is_human_and_offers_an_alternative(): void
    {
        $this->provider->failing();

        $message = $this->signInWithGoogle()->json('message');

        $this->assertStringContainsString('Google', $message);
        // ux-requirements.md: erro diz o que fazer.
        $this->assertMatchesRegularExpression('/tente de novo|e-mail e senha/i', $message);
    }

    #[Test]
    public function the_message_does_not_leak_the_provider_technical_detail(): void
    {
        $this->provider->failing('invalid_grant: authorization code expired at 2026-08-31');

        $message = $this->signInWithGoogle()->json('message');

        $this->assertStringNotContainsString('invalid_grant', $message);
        $this->assertStringNotContainsString('authorization code', $message);
    }

    #[Test]
    public function a_missing_code_is_a_validation_error(): void
    {
        $this->postJson('/api/v1/auth/google/sessoes', [
            'state' => $this->getJson('/api/v1/auth/google/url')->json('data.state'),
        ])->assertStatus(422)->assertJsonValidationErrors('code');
    }

    #[Test]
    public function a_missing_state_is_a_validation_error(): void
    {
        $this->postJson('/api/v1/auth/google/sessoes', ['code' => 'x'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('state');
    }

    #[Test]
    public function the_state_is_consumed_even_when_the_provider_fails(): void
    {
        // Se o state sobrevivesse à falha, uma tentativa malsucedida deixaria
        // um state válido circulando — exatamente o que ele deveria impedir.
        $this->provider->failing();
        $state = $this->getJson('/api/v1/auth/google/url')->json('data.state');

        $this->postJson('/api/v1/auth/google/sessoes', ['code' => 'x', 'state' => $state])
            ->assertUnauthorized();

        $this->provider->returning('maria@gmail.com');

        $this->postJson('/api/v1/auth/google/sessoes', ['code' => 'x', 'state' => $state])
            ->assertStatus(422);
    }
}
