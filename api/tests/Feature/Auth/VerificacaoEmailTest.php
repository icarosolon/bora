<?php

namespace Tests\Feature\Auth;

use App\Models\TokenDeEmail;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * US1-7 e decisão D5 — verificação envia, mas NÃO bloqueia o uso.
 */
class VerificacaoEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisSeeder::class);
        Queue::fake();
    }

    /** @return array{User, string} conta e token em claro */
    private function contaComTokenDeVerificacao(array $atributos = []): array
    {
        $conta = User::factory()->create(['email_verified_at' => null, ...$atributos]);
        $emClaro = bin2hex(random_bytes(32));

        $conta->tokensDeEmail()->create([
            'finalidade' => TokenDeEmail::VERIFICACAO_EMAIL,
            'token_hash' => hash('sha256', $emClaro),
            'expira_em' => now()->addMinutes((int) config('bora.tokens_de_email.verificacao_email')),
        ]);

        return [$conta, $emClaro];
    }

    #[Test]
    public function conta_nao_verificada_continua_usando_o_site(): void
    {
        // É o coração da D5: não verificar não pode travar nada.
        [$conta] = $this->contaComTokenDeVerificacao();
        $token = $conta->createToken('celular')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/eu')
            ->assertOk()
            ->assertJsonPath('data.email_verificado', false);
    }

    #[Test]
    public function o_link_valido_confirma_o_email(): void
    {
        [$conta, $emClaro] = $this->contaComTokenDeVerificacao();

        $this->postJson('/api/v1/email/verificar', ['token' => $emClaro])
            ->assertOk()
            ->assertJsonPath('message', 'E-mail confirmado. Obrigado!');

        $this->assertNotNull($conta->fresh()->email_verified_at);
    }

    #[Test]
    public function o_link_so_funciona_uma_vez(): void
    {
        [, $emClaro] = $this->contaComTokenDeVerificacao();

        $this->postJson('/api/v1/email/verificar', ['token' => $emClaro])->assertOk();

        $this->postJson('/api/v1/email/verificar', ['token' => $emClaro])
            ->assertStatus(410);
    }

    #[Test]
    public function o_link_expirado_e_recusado_com_explicacao(): void
    {
        [$conta, $emClaro] = $this->contaComTokenDeVerificacao();
        $conta->tokensDeEmail()->update(['expira_em' => now()->subMinute()]);

        $resposta = $this->postJson('/api/v1/email/verificar', ['token' => $emClaro])
            ->assertStatus(410);

        // ux-requirements.md: erro diz o que fazer.
        $this->assertMatchesRegularExpression('/expirou|novo/i', $resposta->json('message'));
        $this->assertNull($conta->fresh()->email_verified_at);
    }

    #[Test]
    public function token_inexistente_e_recusado(): void
    {
        $this->postJson('/api/v1/email/verificar', ['token' => bin2hex(random_bytes(32))])
            ->assertStatus(410);
    }

    #[Test]
    public function reenvia_o_link_para_quem_esta_autenticado(): void
    {
        [$conta] = $this->contaComTokenDeVerificacao();
        $token = $conta->createToken('celular')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/email/verificar/reenviar')
            ->assertOk();

        Queue::assertPushed(\App\Jobs\EnviarEmailTransacional::class);
    }

    #[Test]
    public function o_reenvio_invalida_o_link_anterior(): void
    {
        // Dois links válidos ao mesmo tempo dobram a janela de ataque sem ganho:
        // a pessoa vai usar o último que recebeu.
        [$conta, $antigo] = $this->contaComTokenDeVerificacao();
        $sessao = $conta->createToken('celular')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$sessao)
            ->postJson('/api/v1/email/verificar/reenviar')
            ->assertOk();

        $this->postJson('/api/v1/email/verificar', ['token' => $antigo])
            ->assertStatus(410);
    }

    #[Test]
    public function quem_ja_verificou_nao_reenvia(): void
    {
        $conta = User::factory()->create(['email_verified_at' => now()]);
        $token = $conta->createToken('celular')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/email/verificar/reenviar')
            ->assertStatus(422);
    }

    #[Test]
    public function verificar_nao_exige_estar_autenticado(): void
    {
        // A pessoa costuma abrir o link no e-mail, muitas vezes noutro
        // aparelho, sem sessão. Exigir login aqui quebraria o fluxo comum.
        [, $emClaro] = $this->contaComTokenDeVerificacao();

        $this->postJson('/api/v1/email/verificar', ['token' => $emClaro])->assertOk();
    }
}
