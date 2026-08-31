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
 * US3-4 e US3-5 — caminhos de erro da união, e a auditoria do evento.
 *
 * O ponto mais importante daqui: **a união é sujeita ao mesmo limite de
 * tentativas do login**. Sem isso, ela viraria a porta livre para adivinhar a
 * senha de uma conta — bastaria iniciar o fluxo do Google e tentar à vontade,
 * contornando o bloqueio da tela de entrar.
 */
class UniaoErrosTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL = 'maria@exemplo.com';

    private ProvedorDeIdentidadeFake $provedor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisSeeder::class);

        $this->provedor = new ProvedorDeIdentidadeFake;
        $this->app->instance(ProvedorDeIdentidade::class, $this->provedor);
    }

    private function uniaoPendente(): string
    {
        $conta = User::factory()->create(['email' => self::EMAIL, 'password' => 'senhaforte1']);
        $conta->assignRole(config('bora.conta.papel_inicial'));

        $this->provedor->devolvendo(self::EMAIL);

        return $this->postJson('/api/v1/auth/google/sessoes', [
            'code' => 'x',
            'state' => $this->getJson('/api/v1/auth/google/url')->json('data.state'),
        ])->assertStatus(409)->json('data.uniao_token');
    }

    #[Test]
    public function senha_errada_devolve_401_com_mensagem_util(): void
    {
        $token = $this->uniaoPendente();

        $mensagem = $this->postJson('/api/v1/uniao-credenciais', [
            'uniao_token' => $token, 'senha' => 'senhaerrada9',
        ])->assertUnauthorized()->json('message');

        // ux-requirements.md: erro diz o que fazer — aqui, o plano B.
        $this->assertMatchesRegularExpression('/link|e-mail/i', $mensagem);
    }

    #[Test]
    public function o_token_sobrevive_a_uma_senha_errada(): void
    {
        // Se errar a senha queimasse o token, a pessoa teria de recomeçar o
        // fluxo do Google a cada engano de digitação.
        $token = $this->uniaoPendente();

        $this->postJson('/api/v1/uniao-credenciais', [
            'uniao_token' => $token, 'senha' => 'errada1234',
        ])->assertUnauthorized();

        $this->postJson('/api/v1/uniao-credenciais', [
            'uniao_token' => $token, 'senha' => 'senhaforte1',
        ])->assertOk();
    }

    #[Test]
    public function a_uniao_respeita_o_mesmo_limite_de_tentativas_do_login(): void
    {
        $token = $this->uniaoPendente();
        $limite = (int) config('bora.tentativas.por_minuto');

        for ($i = 0; $i < $limite; $i++) {
            $this->postJson('/api/v1/uniao-credenciais', [
                'uniao_token' => $token, 'senha' => 'errada1234',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/v1/uniao-credenciais', [
            'uniao_token' => $token, 'senha' => 'errada1234',
        ])->assertStatus(429);
    }

    #[Test]
    public function o_bloqueio_vale_mesmo_com_a_senha_certa(): void
    {
        $token = $this->uniaoPendente();
        $limite = (int) config('bora.tentativas.por_minuto');

        for ($i = 0; $i < $limite; $i++) {
            $this->postJson('/api/v1/uniao-credenciais', [
                'uniao_token' => $token, 'senha' => 'errada1234',
            ]);
        }

        $this->postJson('/api/v1/uniao-credenciais', [
            'uniao_token' => $token, 'senha' => 'senhaforte1',
        ])->assertStatus(429);
    }

    #[Test]
    public function token_de_uniao_inexistente_devolve_410(): void
    {
        $this->postJson('/api/v1/uniao-credenciais', [
            'uniao_token' => 'nunca-emitido', 'senha' => 'senhaforte1',
        ])->assertStatus(410);
    }

    #[Test]
    public function campos_ausentes_sao_erro_de_validacao(): void
    {
        $this->postJson('/api/v1/uniao-credenciais', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['uniao_token', 'senha']);
    }

    #[Test]
    public function a_uniao_e_auditada_sem_valores_sensiveis(): void
    {
        $token = $this->uniaoPendente();

        $this->postJson('/api/v1/uniao-credenciais', [
            'uniao_token' => $token, 'senha' => 'senhaforte1',
        ])->assertOk();

        $registro = Activity::where('event', Auditoria::CREDENCIAIS_UNIDAS)->first();

        $this->assertNotNull($registro, 'a união é escrita de domínio e precisa auditar');
        $this->assertFalse(Auditoria::contemDadoSensivel($registro));
        $this->assertStringNotContainsString('senhaforte1', json_encode($registro->properties));
    }

    #[Test]
    public function a_resposta_de_erro_nunca_expoe_a_senha(): void
    {
        $token = $this->uniaoPendente();

        $conteudo = $this->postJson('/api/v1/uniao-credenciais', [
            'uniao_token' => $token, 'senha' => 'tentativaErrada9',
        ])->getContent();

        $this->assertStringNotContainsString('tentativaErrada9', $conteudo);
    }
}
