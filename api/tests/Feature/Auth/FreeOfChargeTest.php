<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * PROVA DO BLOQUEIO — Princípio II (NON-NEGOTIABLE) e RN-PLAT-003.
 *
 * "Nenhuma funcionalidade de consumo fica atrás de pagamento." Um teste de
 * gratuidade parece bobo enquanto ninguém tentou cobrar; ele existe para o dia
 * em que alguém adicionar um passo de plano no cadastro sem perceber que está
 * violando a constituição. Aí ele falha e explica o porquê.
 */
class FreeOfChargeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Queue::fake();
    }

    #[Test]
    public function the_full_account_journey_never_asks_for_payment(): void
    {
        $signup = $this->postJson('/api/v1/contas', [
            'name' => 'Maria',
            'email' => 'maria@exemplo.com',
            'password' => 'senhaforte1',
        ])->assertCreated();

        $login = $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com',
            'password' => 'senhaforte1',
        ])->assertOk();

        $token = $login->json('data.token');

        $me = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/eu')->assertOk();

        foreach ([$signup, $login, $me] as $response) {
            $this->assertDoesNotMentionBilling($response->getContent());
        }
    }

    #[Test]
    public function the_account_is_usable_from_birth_without_any_plan(): void
    {
        // Não pode existir estado "conta criada, mas inativa até assinar".
        $this->postJson('/api/v1/contas', [
            'name' => 'Maria',
            'email' => 'maria@exemplo.com',
            'password' => 'senhaforte1',
        ])->assertCreated();

        $account = User::first();

        $this->assertTrue($account->hasRole(config('bora.account.initial_role')));
        // Nenhuma coluna de plano, assinatura ou cobrança na conta.
        foreach (['plano', 'assinatura', 'plan', 'subscription', 'trial', 'stripe_id'] as $forbidden) {
            $this->assertArrayNotHasKey($forbidden, $account->getAttributes());
        }
    }

    #[Test]
    public function this_feature_exposes_no_billing_route(): void
    {
        $accountRoutes = collect(Route::getRoutes())
            ->map(fn ($r) => $r->uri())
            ->filter(fn ($uri) => str_starts_with($uri, 'api/v1'));

        foreach ($accountRoutes as $uri) {
            $this->assertDoesNotMatchRegularExpression(
                '/pagamento|cobranca|assinatura|plano|checkout|payment|billing|subscription/i',
                $uri,
                "A rota {$uri} sugere cobrança numa feature de consumo (Princípio II)."
            );
        }
    }

    private function assertDoesNotMentionBilling(string $content): void
    {
        foreach ([
            'pagamento', 'pagar', 'cobrança', 'cobranca', 'assinatura', 'plano pago',
            'cartão', 'cartao', 'checkout', 'payment', 'billing', 'subscription', 'upgrade',
        ] as $term) {
            $this->assertStringNotContainsStringIgnoringCase(
                $term,
                $content,
                "A resposta menciona \"{$term}\": consumo não pode ser condicionado a pagamento (Princípio II)."
            );
        }
    }
}
