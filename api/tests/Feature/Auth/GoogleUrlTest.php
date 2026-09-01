<?php

namespace Tests\Feature\Auth;

use App\Ports\IdentityProvider;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\FakeIdentityProvider;
use Tests\TestCase;

/**
 * US2 — `GET /api/v1/auth/google/url`.
 *
 * O `state` é a proteção contra CSRF do fluxo OAuth: sem ele, um site
 * malicioso poderia iniciar um retorno de autorização e emparelhar a conta
 * Google dele com a sessão de outra pessoa. O Socialite em modo `stateless`
 * NÃO verifica isso sozinho — a validação é nossa.
 */
class GoogleUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->app->instance(IdentityProvider::class, new FakeIdentityProvider);
    }

    #[Test]
    public function returns_the_authorization_url_and_the_state(): void
    {
        $this->getJson('/api/v1/auth/google/url')
            ->assertOk()
            ->assertJsonStructure(['data' => ['url', 'state']]);
    }

    #[Test]
    public function the_state_travels_inside_the_url(): void
    {
        $response = $this->getJson('/api/v1/auth/google/url')->assertOk();

        $this->assertStringContainsString(
            $response->json('data.state'),
            $response->json('data.url')
        );
    }

    #[Test]
    public function each_request_generates_a_different_state(): void
    {
        // State reaproveitado deixaria de ser proteção: bastaria capturar um.
        $first = $this->getJson('/api/v1/auth/google/url')->json('data.state');
        $second = $this->getJson('/api/v1/auth/google/url')->json('data.state');

        $this->assertNotSame($first, $second);
    }

    #[Test]
    public function the_state_is_long_enough_not_to_be_guessed(): void
    {
        $state = $this->getJson('/api/v1/auth/google/url')->json('data.state');

        $this->assertGreaterThanOrEqual(32, strlen($state));
    }

    #[Test]
    public function does_not_require_authentication(): void
    {
        // É o primeiro passo de quem ainda não tem conta.
        $this->getJson('/api/v1/auth/google/url')->assertOk();
    }

    #[Test]
    public function the_response_does_not_expose_the_client_secret(): void
    {
        $content = $this->getJson('/api/v1/auth/google/url')->getContent();

        $this->assertStringNotContainsString('client_secret', $content);
        $this->assertStringNotContainsString((string) config('services.google.client_secret'), $content);
    }
}
