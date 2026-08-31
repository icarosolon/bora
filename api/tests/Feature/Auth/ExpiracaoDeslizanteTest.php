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
 * middleware RenovarExpiracaoDoToken. Se alguém preencher `expiration` em
 * config/sanctum.php, ele passa a sobrepor o `expires_at` por token e estes
 * testes quebram — que é exatamente o alarme que queremos.
 */
class ExpiracaoDeslizanteTest extends TestCase
{
    use RefreshDatabase;

    private function contaComToken(): array
    {
        $conta = User::factory()->create();

        $token = $conta->createToken(
            'teste',
            ['*'],
            now()->addDays((int) config('bora.sessao.validade_dias'))
        );

        return [$conta, $token->plainTextToken, $token->accessToken];
    }

    #[Test]
    public function config_do_sanctum_precisa_continuar_nula(): void
    {
        // Guarda de regressão: um valor aqui quebraria a janela deslizante em
        // silêncio, e a pessoa seria deslogada no 30º dia mesmo usando todo dia.
        $this->assertNull(
            config('sanctum.expiration'),
            'sanctum.expiration precisa ficar null — ver research.md §1 da spec 001.'
        );
    }

    #[Test]
    public function request_autenticada_empurra_o_vencimento_para_frente(): void
    {
        [, $tokenEmClaro, $token] = $this->contaComToken();

        // Simula uma sessão parada há 20 dias: vencimento daqui a 10.
        $token->forceFill(['expires_at' => now()->addDays(10)])->save();
        $vencimentoAntes = $token->fresh()->expires_at;

        $this->withHeader('Authorization', 'Bearer '.$tokenEmClaro)
            ->getJson('/api/v1/eu')
            ->assertOk();

        $vencimentoDepois = PersonalAccessToken::find($token->id)->expires_at;

        $this->assertTrue(
            $vencimentoDepois->greaterThan($vencimentoAntes),
            'O uso deveria ter renovado o prazo — é o que diferencia janela deslizante de prazo fixo.'
        );
        $this->assertEqualsWithDelta(
            (int) config('bora.sessao.validade_dias'),
            now()->diffInDays($vencimentoDepois),
            1,
            'O novo vencimento deveria ser ~30 dias a partir de agora.'
        );
    }

    #[Test]
    public function o_uso_tambem_registra_o_ultimo_acesso(): void
    {
        [$conta, $tokenEmClaro] = $this->contaComToken();

        $this->assertNull($conta->ultimo_acesso_em);

        $this->withHeader('Authorization', 'Bearer '.$tokenEmClaro)
            ->getJson('/api/v1/eu')
            ->assertOk();

        $this->assertNotNull($conta->fresh()->ultimo_acesso_em);
    }

    #[Test]
    public function token_expirado_e_recusado(): void
    {
        [, $tokenEmClaro, $token] = $this->contaComToken();

        $token->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->withHeader('Authorization', 'Bearer '.$tokenEmClaro)
            ->getJson('/api/v1/eu')
            ->assertUnauthorized();
    }

    #[Test]
    public function conta_parada_alem_do_prazo_perde_a_sessao(): void
    {
        // O cenário que a D7 descreve: quem some por mais de 30 dias reloga.
        [, $tokenEmClaro, $token] = $this->contaComToken();

        $this->travel((int) config('bora.sessao.validade_dias') + 1)->days();

        $this->withHeader('Authorization', 'Bearer '.$tokenEmClaro)
            ->getJson('/api/v1/eu')
            ->assertUnauthorized();

        $this->travelBack();
        unset($token);
    }

    #[Test]
    public function sem_token_a_resposta_e_401_com_mensagem_humana(): void
    {
        $this->getJson('/api/v1/eu')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Faça login para continuar.');
    }
}
