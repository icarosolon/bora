<?php

namespace Tests\Feature\Auth;

use App\Jobs\EnviarEmailTransacional;
use App\Models\TokenDeEmail;
use App\Models\User;
use App\Ports\ProvedorDeIdentidade;
use Database\Seeders\PapeisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ProvedorDeIdentidadeFake;
use Tests\TestCase;

/**
 * US3-3 — plano B da decisão D1: quem não lembra a senha confirma por link.
 *
 * Sem este caminho, quem esqueceu a senha ficaria preso: entraria pelo Google,
 * seria mandado a confirmar com a senha que não lembra, e não teria saída. Foi
 * exatamente por isso que a D1 previu o plano B.
 */
class UniaoPorLinkTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL = 'maria@exemplo.com';

    private ProvedorDeIdentidadeFake $provedor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisSeeder::class);
        Queue::fake();

        $this->provedor = new ProvedorDeIdentidadeFake;
        $this->app->instance(ProvedorDeIdentidade::class, $this->provedor);
    }

    /** @return array{User, string} conta e `uniao_token` */
    private function uniaoPendente(): array
    {
        $conta = User::factory()->create(['email' => self::EMAIL, 'password' => 'senhaforte1']);
        $conta->assignRole(config('bora.conta.papel_inicial'));

        $this->provedor->devolvendo(self::EMAIL);

        $token = $this->postJson('/api/v1/auth/google/sessoes', [
            'code' => 'x',
            'state' => $this->getJson('/api/v1/auth/google/url')->json('data.state'),
        ])->assertStatus(409)->json('data.uniao_token');

        return [$conta, $token];
    }

    /** Emite o link e devolve o valor em claro que iria no e-mail. */
    private function pedirLink(string $uniaoToken): string
    {
        $this->postJson('/api/v1/uniao-credenciais/link', ['uniao_token' => $uniaoToken])
            ->assertOk();

        $enviado = null;
        Queue::assertPushed(EnviarEmailTransacional::class, function ($job) use (&$enviado) {
            $r = new \ReflectionProperty($job, 'variaveis');
            $enviado = $r->getValue($job)['token'] ?? null;

            return true;
        });

        $this->assertNotNull($enviado, 'o Job precisa carregar o token do link');

        return $enviado;
    }

    #[Test]
    public function enfileira_o_email_com_o_link(): void
    {
        [, $uniaoToken] = $this->uniaoPendente();

        $this->postJson('/api/v1/uniao-credenciais/link', ['uniao_token' => $uniaoToken])
            ->assertOk();

        Queue::assertPushed(EnviarEmailTransacional::class);
        $this->assertDatabaseHas('tokens_de_email', [
            'finalidade' => TokenDeEmail::UNIAO_CREDENCIAIS,
        ]);
    }

    #[Test]
    public function o_link_valido_conclui_a_uniao(): void
    {
        [$conta, $uniaoToken] = $this->uniaoPendente();
        $link = $this->pedirLink($uniaoToken);

        $this->postJson('/api/v1/uniao-credenciais/link/confirmar', ['token' => $link])
            ->assertOk()
            ->assertJsonPath('data.conta.id', $conta->id);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('contas_sociais', 1);
    }

    #[Test]
    public function o_link_e_de_uso_unico(): void
    {
        [, $uniaoToken] = $this->uniaoPendente();
        $link = $this->pedirLink($uniaoToken);

        $this->postJson('/api/v1/uniao-credenciais/link/confirmar', ['token' => $link])->assertOk();
        $this->postJson('/api/v1/uniao-credenciais/link/confirmar', ['token' => $link])->assertStatus(410);
    }

    #[Test]
    public function o_link_expirado_e_recusado(): void
    {
        [, $uniaoToken] = $this->uniaoPendente();
        $link = $this->pedirLink($uniaoToken);

        TokenDeEmail::query()->update(['expira_em' => now()->subMinute()]);

        $this->postJson('/api/v1/uniao-credenciais/link/confirmar', ['token' => $link])
            ->assertStatus(410);

        $this->assertDatabaseCount('contas_sociais', 0);
    }

    #[Test]
    public function o_token_em_claro_nao_e_guardado_no_banco(): void
    {
        [, $uniaoToken] = $this->uniaoPendente();
        $link = $this->pedirLink($uniaoToken);

        $registro = TokenDeEmail::where('finalidade', TokenDeEmail::UNIAO_CREDENCIAIS)->first();

        $this->assertNotSame($link, $registro->token_hash);
        $this->assertSame(hash('sha256', $link), $registro->token_hash);
    }

    #[Test]
    public function pedir_link_com_token_de_uniao_invalido_e_recusado(): void
    {
        $this->postJson('/api/v1/uniao-credenciais/link', ['uniao_token' => 'nao-existe'])
            ->assertStatus(410);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function o_link_carrega_o_endereco_do_frontend_e_nao_o_da_api(): void
    {
        // Quem tem tela é o `web/` (Princípio XI). Link apontando para a API
        // levaria a pessoa a um JSON.
        [, $uniaoToken] = $this->uniaoPendente();

        $this->postJson('/api/v1/uniao-credenciais/link', ['uniao_token' => $uniaoToken])->assertOk();

        Queue::assertPushed(EnviarEmailTransacional::class, function ($job) {
            $url = (new \ReflectionProperty($job, 'variaveis'))->getValue($job)['url'] ?? '';

            return str_contains($url, '/unir-contas/confirmar');
        });
    }
}
