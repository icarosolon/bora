<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Ports\ProvedorDeIdentidade;
use App\Support\Auditoria;
use Database\Seeders\PapeisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\Support\ProvedorDeIdentidadeFake;
use Tests\TestCase;

/**
 * US2 — `POST /api/v1/auth/google/sessoes`.
 *
 * Inclui a PROVA DO BLOQUEIO do Princípio I pelo caminho do Google: repetir o
 * login nunca cria uma segunda conta.
 */
class GoogleSessaoTest extends TestCase
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

    /** Faz o passo 1 (obter state) e devolve um state válido. */
    private function stateValido(): string
    {
        return $this->getJson('/api/v1/auth/google/url')->json('data.state');
    }

    private function entrarComGoogle(?string $state = null, string $code = 'codigo-valido')
    {
        return $this->postJson('/api/v1/auth/google/sessoes', [
            'code' => $code,
            'state' => $state ?? $this->stateValido(),
        ]);
    }

    #[Test]
    public function email_inedito_cria_a_conta_ja_verificada(): void
    {
        $this->provedor->devolvendo('maria@gmail.com');

        $this->entrarComGoogle()
            ->assertOk()
            ->assertJsonPath('data.conta.email', 'maria@gmail.com')
            // O Google já verificou o e-mail — não faz sentido pedir de novo.
            ->assertJsonPath('data.conta.email_verificado', true)
            ->assertJsonPath('data.conta.papeis', ['rolezeiro'])
            ->assertJsonStructure(['data' => ['conta', 'token', 'expira_em']]);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('contas_sociais', [
            'provedor' => 'google',
            'provedor_user_id' => 'google-123',
        ]);
    }

    #[Test]
    public function a_conta_criada_pelo_google_nasce_sem_senha(): void
    {
        $this->provedor->devolvendo('maria@gmail.com');

        $this->entrarComGoogle()->assertOk();

        $this->assertNull(User::first()->password);
        $this->assertFalse(User::first()->temSenha());
    }

    #[Test]
    public function repetir_o_login_entra_na_mesma_conta_e_nao_duplica(): void
    {
        // PROVA DO BLOQUEIO — Princípio I pelo caminho do Google.
        $this->provedor->devolvendo('maria@gmail.com');

        $this->entrarComGoogle()->assertOk();
        $this->entrarComGoogle()->assertOk();
        $this->entrarComGoogle()->assertOk();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('contas_sociais', 1);
    }

    #[Test]
    public function o_token_devolvido_autentica(): void
    {
        $this->provedor->devolvendo('maria@gmail.com');

        $token = $this->entrarComGoogle()->json('data.token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/eu')
            ->assertOk()
            ->assertJsonPath('data.entra_com', ['google']);
    }

    #[Test]
    public function o_email_vindo_do_provedor_e_normalizado(): void
    {
        $this->provedor->devolvendo('Maria@GMAIL.com');

        $this->entrarComGoogle()->assertOk();

        $this->assertSame('maria@gmail.com', User::first()->email);
    }

    #[Test]
    public function o_state_e_de_uso_unico(): void
    {
        // Sem consumo, um state capturado valeria para sempre.
        $this->provedor->devolvendo('maria@gmail.com');
        $state = $this->stateValido();

        $this->entrarComGoogle($state)->assertOk();
        $this->entrarComGoogle($state)->assertStatus(422);
    }

    #[Test]
    public function state_desconhecido_e_recusado(): void
    {
        $this->provedor->devolvendo('maria@gmail.com');

        $this->entrarComGoogle('state-que-ninguem-emitiu')->assertStatus(422);

        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function a_criacao_pelo_google_e_auditada(): void
    {
        $this->provedor->devolvendo('maria@gmail.com');

        $this->entrarComGoogle()->assertOk();

        $registro = Activity::where('event', Auditoria::CONTA_CRIADA)->first();

        $this->assertNotNull($registro);
        $this->assertSame('cadastro_google', $registro->properties['origem'] ?? null);
    }

    #[Test]
    public function guarda_do_provedor_apenas_o_necessario(): void
    {
        // Princípio III: só nome, e-mail e identificador do provedor.
        $this->provedor->devolvendo('maria@gmail.com');

        $this->entrarComGoogle()->assertOk();

        $vinculo = \App\Models\ContaSocial::first();

        $this->assertSame('google', $vinculo->provedor);
        $this->assertSame('google-123', $vinculo->provedor_user_id);
        $this->assertSame('maria@gmail.com', $vinculo->email_no_provedor);
    }
}
