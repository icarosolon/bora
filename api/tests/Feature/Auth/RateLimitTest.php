<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * FR-007 — limite de tentativas (Princípio V: segurança por padrão).
 *
 * Sem isto, a tela de entrar é um oráculo de força bruta.
 */
class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);

        User::factory()->create([
            'email' => 'maria@exemplo.com',
            'password' => 'senhaforte1',
        ]);
    }

    private function tryWithWrongPassword(): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com',
            'password' => 'senhaerrada9',
        ]);
    }

    #[Test]
    public function blocks_after_exceeding_the_limit(): void
    {
        $limit = (int) config('bora.attempts.per_minute');

        for ($i = 0; $i < $limit; $i++) {
            $this->tryWithWrongPassword()->assertUnauthorized();
        }

        $this->tryWithWrongPassword()->assertStatus(429);
    }

    #[Test]
    public function the_blocked_response_carries_retry_after(): void
    {
        // O header é o que permite à tela dizer quanto esperar. Ele só chega ao
        // JavaScript porque config/cors.php o expõe.
        $limit = (int) config('bora.attempts.per_minute');

        for ($i = 0; $i < $limit; $i++) {
            $this->tryWithWrongPassword();
        }

        $blocked = $this->tryWithWrongPassword()->assertStatus(429);

        $this->assertNotNull($blocked->headers->get('Retry-After'));
    }

    #[Test]
    public function the_message_says_how_long_to_wait(): void
    {
        $limit = (int) config('bora.attempts.per_minute');

        for ($i = 0; $i < $limit; $i++) {
            $this->tryWithWrongPassword();
        }

        $message = $this->tryWithWrongPassword()->json('message');

        $this->assertStringContainsString('Aguarde', $message);
        $this->assertMatchesRegularExpression('/minuto|segundo/i', $message);
    }

    #[Test]
    public function the_block_applies_even_with_the_right_password(): void
    {
        // Se a senha correta passasse depois do bloqueio, o limite não seria
        // limite: bastaria continuar tentando até acertar.
        $limit = (int) config('bora.attempts.per_minute');

        for ($i = 0; $i < $limit; $i++) {
            $this->tryWithWrongPassword();
        }

        $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com',
            'password' => 'senhaforte1',
        ])->assertStatus(429);
    }

    #[Test]
    public function the_limit_is_per_account_and_does_not_hit_third_parties(): void
    {
        // Só limitar por IP puniria gente inocente atrás do mesmo NAT — um bar
        // com wi-fi compartilhado, que é exatamente o cenário do Bora.
        User::factory()->create([
            'email' => 'joao@exemplo.com',
            'password' => 'outrasenha9',
        ]);

        $limit = (int) config('bora.attempts.per_minute');
        for ($i = 0; $i < $limit; $i++) {
            $this->tryWithWrongPassword();
        }

        $this->postJson('/api/v1/sessoes', [
            'email' => 'joao@exemplo.com',
            'password' => 'outrasenha9',
        ])->assertOk();
    }
}
