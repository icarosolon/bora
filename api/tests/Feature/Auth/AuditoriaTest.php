<?php

namespace Tests\Feature\Auth;

use App\Models\TokenDeEmail;
use App\Models\User;
use App\Support\Auditoria;
use Database\Seeders\PapeisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * RN-PLAT-004 / Princípio VIII (auditoria) e Princípio V (nada sensível em log).
 *
 * As duas coisas juntas de propósito: auditar bem é registrar QUE aconteceu e
 * QUEM fez — e não registrar O QUE a pessoa digitou.
 */
class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisSeeder::class);
        Queue::fake();
    }

    #[Test]
    public function a_criacao_de_conta_gera_registro(): void
    {
        $this->postJson('/api/v1/contas', [
            'nome' => 'Maria',
            'email' => 'maria@exemplo.com',
            'senha' => 'senhaforte1',
        ])->assertCreated();

        $registro = Activity::latest('id')->first();

        $this->assertNotNull($registro, 'Toda escrita de domínio audita (Princípio VIII).');
        $this->assertSame(Auditoria::CONTA_CRIADA, $registro->event);
        $this->assertSame(User::first()->id, $registro->subject_id);
    }

    #[Test]
    public function a_verificacao_de_email_gera_registro(): void
    {
        $conta = User::factory()->create(['email_verified_at' => null]);
        $emClaro = bin2hex(random_bytes(32));
        $conta->tokensDeEmail()->create([
            'finalidade' => TokenDeEmail::VERIFICACAO_EMAIL,
            'token_hash' => hash('sha256', $emClaro),
            'expira_em' => now()->addDay(),
        ]);

        $this->postJson('/api/v1/email/verificar', ['token' => $emClaro])->assertOk();

        $this->assertTrue(
            Activity::where('event', Auditoria::EMAIL_VERIFICADO)->exists()
        );
    }

    #[Test]
    public function nenhum_registro_carrega_senha_ou_token(): void
    {
        // O teste que a spec exige: "senha e tokens nunca aparecem em log".
        $this->postJson('/api/v1/contas', [
            'nome' => 'Maria',
            'email' => 'maria@exemplo.com',
            'senha' => 'senhaSuperSecreta123',
        ])->assertCreated();

        $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com',
            'senha' => 'senhaSuperSecreta123',
        ])->assertOk();

        foreach (Activity::all() as $registro) {
            $conteudo = json_encode($registro->properties);

            $this->assertStringNotContainsString('senhaSuperSecreta123', $conteudo);
            $this->assertFalse(
                Auditoria::contemDadoSensivel($registro),
                "O registro {$registro->id} carrega propriedade sensível."
            );
        }
    }

    #[Test]
    public function o_filtro_barra_chave_sensivel_mesmo_se_alguem_a_passar(): void
    {
        // Defesa em profundidade: um caso de uso futuro pode passar 'password'
        // por descuido. A classe Auditoria remove antes de gravar.
        $conta = User::factory()->create();

        Auditoria::registrar(
            Auditoria::CONTA_CRIADA,
            sobre: $conta,
            propriedades: [
                'origem' => 'cadastro',
                'password' => 'nao-deveria-passar',
                'uniao_token' => 'nem-isto',
            ],
        );

        $registro = Activity::latest('id')->first();

        $this->assertSame('cadastro', $registro->properties['origem']);
        $this->assertArrayNotHasKey('password', $registro->properties->toArray());
        $this->assertArrayNotHasKey('uniao_token', $registro->properties->toArray());
    }

    #[Test]
    public function o_hash_do_token_tambem_e_barrado(): void
    {
        $conta = User::factory()->create();

        Auditoria::registrar(
            Auditoria::EMAIL_VERIFICADO,
            sobre: $conta,
            propriedades: ['token_hash' => hash('sha256', 'x')],
        );

        $this->assertArrayNotHasKey(
            'token_hash',
            Activity::latest('id')->first()->properties->toArray()
        );
    }
}
