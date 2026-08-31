<?php

namespace Tests\Feature\Auth;

use App\Ports\ProvedorDeIdentidade;
use Database\Seeders\PapeisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ProvedorDeIdentidadeFake;
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
        $this->seed(PapeisSeeder::class);
        $this->app->instance(ProvedorDeIdentidade::class, new ProvedorDeIdentidadeFake);
    }

    #[Test]
    public function devolve_a_url_de_autorizacao_e_o_state(): void
    {
        $this->getJson('/api/v1/auth/google/url')
            ->assertOk()
            ->assertJsonStructure(['data' => ['url', 'state']]);
    }

    #[Test]
    public function o_state_vai_dentro_da_url(): void
    {
        $resposta = $this->getJson('/api/v1/auth/google/url')->assertOk();

        $this->assertStringContainsString(
            $resposta->json('data.state'),
            $resposta->json('data.url')
        );
    }

    #[Test]
    public function cada_pedido_gera_um_state_diferente(): void
    {
        // State reaproveitado deixaria de ser proteção: bastaria capturar um.
        $primeiro = $this->getJson('/api/v1/auth/google/url')->json('data.state');
        $segundo = $this->getJson('/api/v1/auth/google/url')->json('data.state');

        $this->assertNotSame($primeiro, $segundo);
    }

    #[Test]
    public function o_state_e_longo_o_bastante_para_nao_ser_adivinhado(): void
    {
        $state = $this->getJson('/api/v1/auth/google/url')->json('data.state');

        $this->assertGreaterThanOrEqual(32, strlen($state));
    }

    #[Test]
    public function nao_exige_autenticacao(): void
    {
        // É o primeiro passo de quem ainda não tem conta.
        $this->getJson('/api/v1/auth/google/url')->assertOk();
    }

    #[Test]
    public function a_resposta_nao_expoe_o_segredo_do_cliente(): void
    {
        $conteudo = $this->getJson('/api/v1/auth/google/url')->getContent();

        $this->assertStringNotContainsString('client_secret', $conteudo);
        $this->assertStringNotContainsString((string) config('services.google.client_secret'), $conteudo);
    }
}
