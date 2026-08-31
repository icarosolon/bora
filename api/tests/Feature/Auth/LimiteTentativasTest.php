<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * FR-007 — limite de tentativas (Princípio V: segurança por padrão).
 *
 * Sem isto, a tela de entrar é um oráculo de força bruta.
 */
class LimiteTentativasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisSeeder::class);

        User::factory()->create([
            'email' => 'maria@exemplo.com',
            'password' => 'senhaforte1',
        ]);
    }

    private function tentarComSenhaErrada(): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com',
            'senha' => 'senhaerrada9',
        ]);
    }

    #[Test]
    public function bloqueia_apos_exceder_o_limite(): void
    {
        $limite = (int) config('bora.tentativas.por_minuto');

        for ($i = 0; $i < $limite; $i++) {
            $this->tentarComSenhaErrada()->assertUnauthorized();
        }

        $this->tentarComSenhaErrada()->assertStatus(429);
    }

    #[Test]
    public function a_resposta_bloqueada_traz_retry_after(): void
    {
        // O header é o que permite à tela dizer quanto esperar. Ele só chega ao
        // JavaScript porque config/cors.php o expõe.
        $limite = (int) config('bora.tentativas.por_minuto');

        for ($i = 0; $i < $limite; $i++) {
            $this->tentarComSenhaErrada();
        }

        $bloqueada = $this->tentarComSenhaErrada()->assertStatus(429);

        $this->assertNotNull($bloqueada->headers->get('Retry-After'));
    }

    #[Test]
    public function a_mensagem_diz_quanto_tempo_esperar(): void
    {
        $limite = (int) config('bora.tentativas.por_minuto');

        for ($i = 0; $i < $limite; $i++) {
            $this->tentarComSenhaErrada();
        }

        $mensagem = $this->tentarComSenhaErrada()->json('message');

        $this->assertStringContainsString('Aguarde', $mensagem);
        $this->assertMatchesRegularExpression('/minuto|segundo/i', $mensagem);
    }

    #[Test]
    public function o_bloqueio_vale_mesmo_com_a_senha_certa(): void
    {
        // Se a senha correta passasse depois do bloqueio, o limite não seria
        // limite: bastaria continuar tentando até acertar.
        $limite = (int) config('bora.tentativas.por_minuto');

        for ($i = 0; $i < $limite; $i++) {
            $this->tentarComSenhaErrada();
        }

        $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com',
            'senha' => 'senhaforte1',
        ])->assertStatus(429);
    }

    #[Test]
    public function o_limite_e_por_conta_e_nao_derruba_terceiros(): void
    {
        // Só limitar por IP puniria gente inocente atrás do mesmo NAT — um bar
        // com wi-fi compartilhado, que é exatamente o cenário do Bora.
        User::factory()->create([
            'email' => 'joao@exemplo.com',
            'password' => 'outrasenha9',
        ]);

        $limite = (int) config('bora.tentativas.por_minuto');
        for ($i = 0; $i < $limite; $i++) {
            $this->tentarComSenhaErrada();
        }

        $this->postJson('/api/v1/sessoes', [
            'email' => 'joao@exemplo.com',
            'senha' => 'outrasenha9',
        ])->assertOk();
    }
}
