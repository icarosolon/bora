<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Decisão D7 — 30 dias de INATIVIDADE, renovados a cada uso.
 *
 * Este teste existe porque o comportamento NÃO vem do Sanctum: a opção
 * `expiration` dele é prazo absoluto desde a criação. O deslizamento é o
 * middleware RefreshTokenExpiration. Se alguém preencher `expiration` em
 * config/sanctum.php, ele passa a sobrepor o `expires_at` por token e estes
 * testes quebram — que é exatamente o alarme que queremos.
 */
class SlidingExpirationTest extends TestCase
{
    use RefreshDatabase;

    private function accountWithToken(): array
    {
        $account = User::factory()->create();

        $token = $account->createToken(
            'teste',
            ['*'],
            now()->addDays((int) config('bora.session.lifetime_days'))
        );

        return [$account, $token->plainTextToken, $token->accessToken];
    }

    #[Test]
    public function the_sanctum_config_must_stay_null(): void
    {
        // Guarda de regressão: um valor aqui quebraria a janela deslizante em
        // silêncio, e a pessoa seria deslogada no 30º dia mesmo usando todo dia.
        $this->assertNull(
            config('sanctum.expiration'),
            'sanctum.expiration precisa ficar null — ver research.md §1 da spec 001.'
        );
    }

    #[Test]
    public function an_authenticated_request_pushes_the_expiry_forward(): void
    {
        [, $plainTextToken, $token] = $this->accountWithToken();

        // Simula uma sessão parada há 20 dias: vencimento daqui a 10.
        $token->forceFill(['expires_at' => now()->addDays(10)])->save();
        $expiryBefore = $token->fresh()->expires_at;

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/v1/eu')
            ->assertOk();

        $expiryAfter = PersonalAccessToken::find($token->id)->expires_at;

        $this->assertTrue(
            $expiryAfter->greaterThan($expiryBefore),
            'O uso deveria ter renovado o prazo — é o que diferencia janela deslizante de prazo fixo.'
        );
        $this->assertEqualsWithDelta(
            (int) config('bora.session.lifetime_days'),
            now()->diffInDays($expiryAfter),
            1,
            'O novo vencimento deveria ser ~30 dias a partir de agora.'
        );
    }

    #[Test]
    public function use_also_records_the_last_access(): void
    {
        [$account, $plainTextToken] = $this->accountWithToken();

        $this->assertNull($account->last_seen_at);

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/v1/eu')
            ->assertOk();

        $this->assertNotNull($account->fresh()->last_seen_at);
    }

    #[Test]
    public function an_expired_token_is_rejected(): void
    {
        [, $plainTextToken, $token] = $this->accountWithToken();

        $token->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/v1/eu')
            ->assertUnauthorized();
    }

    #[Test]
    public function an_idle_account_past_the_period_loses_the_session(): void
    {
        // O cenário que a D7 descreve: quem some por mais de 30 dias reloga.
        [, $plainTextToken, $token] = $this->accountWithToken();

        $this->travel((int) config('bora.session.lifetime_days') + 1)->days();

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/v1/eu')
            ->assertUnauthorized();

        $this->travelBack();
        unset($token);
    }

    #[Test]
    public function without_a_token_the_response_is_401_with_a_human_message(): void
    {
        $this->getJson('/api/v1/eu')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Faça login para continuar.');
    }
}
