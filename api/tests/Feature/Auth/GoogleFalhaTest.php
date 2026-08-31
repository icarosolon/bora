<?php

namespace Tests\Feature\Auth;

use App\Ports\ProvedorDeIdentidade;
use Database\Seeders\PapeisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ProvedorDeIdentidadeFake;
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
class GoogleFalhaTest extends TestCase
{
    use RefreshDatabase;

    private ProvedorDeIdentidadeFake $provedor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisSeeder::class);

        $this->provedor = new ProvedorDeIdentidadeFake;
        $this->app->instance(ProvedorDeIdentidade::class, $this->provedor);
    }

    private function entrarComGoogle()
    {
        return $this->postJson('/api/v1/auth/google/sessoes', [
            'code' => 'qualquer',
            'state' => $this->getJson('/api/v1/auth/google/url')->json('data.state'),
        ]);
    }

    #[Test]
    public function falha_do_provedor_devolve_401_sem_criar_conta(): void
    {
        $this->provedor->falhando('usuário cancelou');

        $this->entrarComGoogle()->assertUnauthorized();

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('contas_sociais', 0);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[Test]
    public function a_mensagem_e_humana_e_oferece_alternativa(): void
    {
        $this->provedor->falhando();

        $mensagem = $this->entrarComGoogle()->json('message');

        $this->assertStringContainsString('Google', $mensagem);
        // ux-requirements.md: erro diz o que fazer.
        $this->assertMatchesRegularExpression('/tente de novo|e-mail e senha/i', $mensagem);
    }

    #[Test]
    public function a_mensagem_nao_vaza_detalhe_tecnico_do_provedor(): void
    {
        $this->provedor->falhando('invalid_grant: authorization code expired at 2026-08-31');

        $mensagem = $this->entrarComGoogle()->json('message');

        $this->assertStringNotContainsString('invalid_grant', $mensagem);
        $this->assertStringNotContainsString('authorization code', $mensagem);
    }

    #[Test]
    public function code_ausente_e_erro_de_validacao(): void
    {
        $this->postJson('/api/v1/auth/google/sessoes', [
            'state' => $this->getJson('/api/v1/auth/google/url')->json('data.state'),
        ])->assertStatus(422)->assertJsonValidationErrors('code');
    }

    #[Test]
    public function state_ausente_e_erro_de_validacao(): void
    {
        $this->postJson('/api/v1/auth/google/sessoes', ['code' => 'x'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('state');
    }

    #[Test]
    public function o_state_e_consumido_mesmo_quando_o_provedor_falha(): void
    {
        // Se o state sobrevivesse à falha, uma tentativa malsucedida deixaria
        // um state válido circulando — exatamente o que ele deveria impedir.
        $this->provedor->falhando();
        $state = $this->getJson('/api/v1/auth/google/url')->json('data.state');

        $this->postJson('/api/v1/auth/google/sessoes', ['code' => 'x', 'state' => $state])
            ->assertUnauthorized();

        $this->provedor->devolvendo('maria@gmail.com');

        $this->postJson('/api/v1/auth/google/sessoes', ['code' => 'x', 'state' => $state])
            ->assertStatus(422);
    }
}
