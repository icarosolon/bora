<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\PapeisSeeder;
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
class GratuidadeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisSeeder::class);
        Queue::fake();
    }

    #[Test]
    public function a_jornada_completa_de_conta_nao_pede_pagamento(): void
    {
        $cadastro = $this->postJson('/api/v1/contas', [
            'nome' => 'Maria',
            'email' => 'maria@exemplo.com',
            'senha' => 'senhaforte1',
        ])->assertCreated();

        $login = $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com',
            'senha' => 'senhaforte1',
        ])->assertOk();

        $token = $login->json('data.token');

        $eu = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/eu')->assertOk();

        foreach ([$cadastro, $login, $eu] as $resposta) {
            $this->assertNaoMencionaCobranca($resposta->getContent());
        }
    }

    #[Test]
    public function a_conta_nasce_utilizavel_sem_nenhum_plano(): void
    {
        // Não pode existir estado "conta criada, mas inativa até assinar".
        $this->postJson('/api/v1/contas', [
            'nome' => 'Maria',
            'email' => 'maria@exemplo.com',
            'senha' => 'senhaforte1',
        ])->assertCreated();

        $conta = User::first();

        $this->assertTrue($conta->hasRole(config('bora.conta.papel_inicial')));
        // Nenhuma coluna de plano, assinatura ou cobrança na conta.
        foreach (['plano', 'assinatura', 'plan', 'subscription', 'trial', 'stripe_id'] as $proibida) {
            $this->assertArrayNotHasKey($proibida, $conta->getAttributes());
        }
    }

    #[Test]
    public function a_api_desta_feature_nao_expoe_rota_de_cobranca(): void
    {
        $rotasDeConta = collect(Route::getRoutes())
            ->map(fn ($r) => $r->uri())
            ->filter(fn ($uri) => str_starts_with($uri, 'api/v1'));

        foreach ($rotasDeConta as $uri) {
            $this->assertDoesNotMatchRegularExpression(
                '/pagamento|cobranca|assinatura|plano|checkout|payment|billing|subscription/i',
                $uri,
                "A rota {$uri} sugere cobrança numa feature de consumo (Princípio II)."
            );
        }
    }

    private function assertNaoMencionaCobranca(string $conteudo): void
    {
        foreach ([
            'pagamento', 'pagar', 'cobrança', 'cobranca', 'assinatura', 'plano pago',
            'cartão', 'cartao', 'checkout', 'payment', 'billing', 'subscription', 'upgrade',
        ] as $termo) {
            $this->assertStringNotContainsStringIgnoringCase(
                $termo,
                $conteudo,
                "A resposta menciona \"{$termo}\": consumo não pode ser condicionado a pagamento (Princípio II)."
            );
        }
    }
}
