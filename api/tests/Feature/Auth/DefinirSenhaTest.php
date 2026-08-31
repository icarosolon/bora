<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\Auditoria;
use Database\Seeders\PapeisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * US2-5 e decisão D1 (direção inversa) — conta nascida no Google define senha.
 *
 * A confirmação do titular aqui é a **sessão ativa**: quem já está autenticado
 * naquela conta é quem pode dar a ela um segundo meio de entrada. É o espelho
 * da união (US3), onde quem tem senha confirma com a senha.
 */
class DefinirSenhaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisSeeder::class);
    }

    private function contaDoGoogle(): User
    {
        $conta = User::factory()->create(['password' => null, 'email_verified_at' => now()]);
        $conta->assignRole(config('bora.conta.papel_inicial'));
        $conta->contasSociais()->create([
            'provedor' => 'google',
            'provedor_user_id' => 'google-123',
            'vinculado_em' => now(),
        ]);

        return $conta;
    }

    #[Test]
    public function conta_do_google_define_senha_com_sessao_ativa(): void
    {
        $conta = $this->contaDoGoogle();
        $token = $conta->createToken('celular')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/senha', ['senha' => 'senhaforte1'])
            ->assertOk();

        $this->assertTrue($conta->fresh()->temSenha());
    }

    #[Test]
    public function depois_de_definir_passa_a_entrar_pelos_dois_caminhos(): void
    {
        $conta = $this->contaDoGoogle();
        $token = $conta->createToken('celular')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/senha', ['senha' => 'senhaforte1'])
            ->assertOk();

        // Mesma conta, agora com dois meios de entrada (RN-PLAT-002).
        $resposta = $this->postJson('/api/v1/sessoes', [
            'email' => $conta->email,
            'senha' => 'senhaforte1',
        ])->assertOk();

        $this->assertSame($conta->id, $resposta->json('data.conta.id'));
        $this->assertEqualsCanonicalizing(
            ['senha', 'google'],
            $resposta->json('data.conta.entra_com')
        );
        $this->assertDatabaseCount('users', 1);
    }

    #[Test]
    public function sem_sessao_ativa_e_recusado(): void
    {
        // É a confirmação do titular; sem ela, qualquer um daria senha à conta
        // alheia e passaria a entrar por ela.
        $this->contaDoGoogle();

        $this->postJson('/api/v1/senha', ['senha' => 'senhaforte1'])
            ->assertUnauthorized();
    }

    #[Test]
    public function conta_que_ja_tem_senha_e_recusada(): void
    {
        // Trocar senha é outra operação, com regras próprias (exige a atual).
        $conta = User::factory()->create(['password' => 'senhaAntiga1']);
        $token = $conta->createToken('celular')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/senha', ['senha' => 'senhaNova123'])
            ->assertStatus(422);
    }

    #[Test]
    public function senha_curta_e_recusada_com_erro_no_campo(): void
    {
        $conta = $this->contaDoGoogle();
        $token = $conta->createToken('celular')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/senha', ['senha' => 'curta'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('senha');

        $this->assertFalse($conta->fresh()->temSenha());
    }

    #[Test]
    public function a_senha_e_guardada_com_hash_e_a_operacao_e_auditada(): void
    {
        $conta = $this->contaDoGoogle();
        $token = $conta->createToken('celular')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/senha', ['senha' => 'senhaforte1'])
            ->assertOk();

        $this->assertNotSame('senhaforte1', $conta->fresh()->password);
        $this->assertTrue(password_verify('senhaforte1', $conta->fresh()->password));

        $registro = Activity::where('event', Auditoria::SENHA_DEFINIDA)->first();
        $this->assertNotNull($registro);
        $this->assertFalse(Auditoria::contemDadoSensivel($registro));
    }

    #[Test]
    public function definir_senha_nao_derruba_a_sessao_atual(): void
    {
        $conta = $this->contaDoGoogle();
        $token = $conta->createToken('celular')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/senha', ['senha' => 'senhaforte1'])
            ->assertOk();

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/eu')
            ->assertOk();
    }
}
