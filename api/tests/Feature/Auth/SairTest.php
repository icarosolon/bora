<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * US1-3 / FR-008 — sair encerra a sessão DAQUELE aparelho, não de todos.
 */
class SairTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisSeeder::class);
    }

    #[Test]
    public function encerra_a_sessao_atual(): void
    {
        $conta = User::factory()->create();
        $token = $conta->createToken('celular')->plainTextToken;

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
    public function nao_derruba_as_sessoes_dos_outros_aparelhos(): void
    {
        // Sair no celular não pode deslogar o computador — é o comportamento
        // que a pessoa espera e o contrário assusta.
        $conta = User::factory()->create();
        $celular = $conta->createToken('celular')->plainTextToken;
        $computador = $conta->createToken('computador')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$celular)
            ->deleteJson('/api/v1/sessoes/atual')
            ->assertNoContent();

        $this->withHeader('Authorization', 'Bearer '.$computador)
            ->getJson('/api/v1/eu')
            ->assertOk();
    }

    #[Test]
    public function o_token_some_do_banco(): void
    {
        $conta = User::factory()->create();
        $token = $conta->createToken('celular')->plainTextToken;

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/sessoes/atual')
            ->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[Test]
    public function sair_sem_estar_autenticado_devolve_401(): void
    {
        $this->deleteJson('/api/v1/sessoes/atual')->assertUnauthorized();
    }
}
