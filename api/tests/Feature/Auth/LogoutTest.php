<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * US1-3 / FR-008 — sair encerra a sessão DAQUELE aparelho, não de todos.
 */
class LogoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    #[Test]
    public function ends_the_current_session(): void
    {
        $account = User::factory()->create();
        $token = $account->createToken('celular')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/sessoes/atual')
            ->assertNoContent();

        // O token some do banco de verdade — não é só a resposta que diz 204.
        $this->assertDatabaseCount('personal_access_tokens', 0);

        // ARTEFATO DE TESTE, não do produto: dentro de um mesmo teste o guard
        // guarda o usuário já resolvido, então a próxima chamada passaria sem
        // reconsultar o banco. Em produção cada request é um ciclo novo e isso
        // não ocorre. Sem este forgetGuards(), o teste daria falso verde.
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/eu')
            ->assertUnauthorized();
    }

    #[Test]
    public function does_not_drop_sessions_on_other_devices(): void
    {
        // Sair no celular não pode deslogar o computador — é o comportamento
        // que a pessoa espera e o contrário assusta.
        $account = User::factory()->create();
        $phone = $account->createToken('celular')->plainTextToken;
        $computer = $account->createToken('computador')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$phone)
            ->deleteJson('/api/v1/sessoes/atual')
            ->assertNoContent();

        $this->withHeader('Authorization', 'Bearer '.$computer)
            ->getJson('/api/v1/eu')
            ->assertOk();
    }

    #[Test]
    public function the_token_disappears_from_the_database(): void
    {
        $account = User::factory()->create();
        $token = $account->createToken('celular')->plainTextToken;

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/sessoes/atual')
            ->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[Test]
    public function signing_out_without_authentication_returns_401(): void
    {
        $this->deleteJson('/api/v1/sessoes/atual')->assertUnauthorized();
    }
}
