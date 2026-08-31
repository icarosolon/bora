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
 * US4-3 — caminhos de erro da redefinição.
 *
 * Link de e-mail é a superfície mais exposta desta feature: ele viaja fora do
 * nosso controle, pode ser reencaminhado, fica no histórico do provedor de
 * e-mail. Por isso: uso único, prazo curto, e recusa que explica o próximo
 * passo em vez de só negar.
 */
class RedefinirSenhaErrosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisSeeder::class);
        Queue::fake();
    }

    /** @return array{User, string} */
    private function contaComLink(): array
    {
        $conta = User::factory()->create([
            'email' => 'maria@exemplo.com',
            'password' => 'senhaAntiga1',
        ]);

        $emClaro = bin2hex(random_bytes(32));
        $conta->tokensDeEmail()->create([
            'finalidade' => TokenDeEmail::REDEFINICAO_SENHA,
            'token_hash' => hash('sha256', $emClaro),
            'expira_em' => now()->addMinutes((int) config('bora.tokens_de_email.redefinicao_senha')),
        ]);

        return [$conta, $emClaro];
    }

    #[Test]
    public function o_link_so_funciona_uma_vez(): void
    {
        [, $link] = $this->contaComLink();

        $this->postJson('/api/v1/senha/redefinir', ['token' => $link, 'senha' => 'senhaNova123'])
            ->assertOk();

        $this->postJson('/api/v1/senha/redefinir', ['token' => $link, 'senha' => 'outraSenha12'])
            ->assertStatus(410);
    }

    #[Test]
    public function reusar_o_link_nao_altera_a_senha_de_novo(): void
    {
        [$conta, $link] = $this->contaComLink();

        $this->postJson('/api/v1/senha/redefinir', ['token' => $link, 'senha' => 'senhaNova123'])->assertOk();
        $hashDepoisDaPrimeira = $conta->fresh()->password;

        $this->postJson('/api/v1/senha/redefinir', ['token' => $link, 'senha' => 'invasor12345'])
            ->assertStatus(410);

        $this->assertSame($hashDepoisDaPrimeira, $conta->fresh()->password);
    }

    #[Test]
    public function o_link_expirado_e_recusado_com_explicacao(): void
    {
        [$conta, $link] = $this->contaComLink();
        $conta->tokensDeEmail()->update(['expira_em' => now()->subMinute()]);

        $mensagem = $this->postJson('/api/v1/senha/redefinir', [
            'token' => $link, 'senha' => 'senhaNova123',
        ])->assertStatus(410)->json('message');

        // ux-requirements.md: erro diz o que fazer.
        $this->assertMatchesRegularExpression('/novo link|de novo|expirou/i', $mensagem);
        $this->assertTrue(password_verify('senhaAntiga1', $conta->fresh()->password));
    }

    #[Test]
    public function token_inexistente_e_recusado(): void
    {
        $this->postJson('/api/v1/senha/redefinir', [
            'token' => bin2hex(random_bytes(32)), 'senha' => 'senhaNova123',
        ])->assertStatus(410);
    }

    #[Test]
    public function token_de_outra_finalidade_nao_serve(): void
    {
        // Um link de verificação de e-mail não pode virar redefinição de senha.
        $conta = User::factory()->create(['password' => 'senhaAntiga1']);
        $emClaro = bin2hex(random_bytes(32));
        $conta->tokensDeEmail()->create([
            'finalidade' => TokenDeEmail::VERIFICACAO_EMAIL,
            'token_hash' => hash('sha256', $emClaro),
            'expira_em' => now()->addDay(),
        ]);

        $this->postJson('/api/v1/senha/redefinir', [
            'token' => $emClaro, 'senha' => 'senhaNova123',
        ])->assertStatus(410);

        $this->assertTrue(password_verify('senhaAntiga1', $conta->fresh()->password));
    }

    #[Test]
    public function senha_curta_e_recusada_com_erro_no_campo(): void
    {
        [$conta, $link] = $this->contaComLink();
        $minimo = (int) config('bora.conta.senha_minima');

        $this->postJson('/api/v1/senha/redefinir', [
            'token' => $link, 'senha' => str_repeat('a', $minimo - 1),
        ])->assertStatus(422)->assertJsonValidationErrors('senha');

        $this->assertTrue(password_verify('senhaAntiga1', $conta->fresh()->password));
    }

    #[Test]
    public function o_link_sobrevive_a_uma_senha_invalida(): void
    {
        // Errar o formato da senha nova não pode queimar o link — a pessoa
        // teria de pedir outro por um engano de digitação.
        [, $link] = $this->contaComLink();

        $this->postJson('/api/v1/senha/redefinir', ['token' => $link, 'senha' => 'curta'])
            ->assertStatus(422);

        $this->postJson('/api/v1/senha/redefinir', ['token' => $link, 'senha' => 'senhaNova123'])
            ->assertOk();
    }

    #[Test]
    public function campos_ausentes_sao_erro_de_validacao(): void
    {
        $this->postJson('/api/v1/senha/redefinir', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['token', 'senha']);
    }

    #[Test]
    public function a_resposta_de_erro_nunca_expoe_a_senha(): void
    {
        [, $link] = $this->contaComLink();

        $conteudo = $this->postJson('/api/v1/senha/redefinir', [
            'token' => $link, 'senha' => 'x',
        ])->getContent();

        $this->assertStringNotContainsString('"x"', $conteudo);
    }
}
