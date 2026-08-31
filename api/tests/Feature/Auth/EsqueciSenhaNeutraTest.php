<?php

namespace Tests\Feature\Auth;

use App\Jobs\EnviarEmailTransacional;
use App\Models\TokenDeEmail;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * PROVA DA NÃO-ENUMERAÇÃO — FR-014, Princípio V.
 *
 * "Esqueci minha senha" é o endpoint mais fácil de transformar em verificador
 * de quais e-mails têm conta no Bora: basta comparar as respostas. Por isso a
 * resposta é **idêntica** exista ou não a conta — mesmo texto, mesmo status.
 *
 * O teste compara byte a byte de propósito. Uma vírgula diferente já seria um
 * sinal explorável.
 */
class EsqueciSenhaNeutraTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisSeeder::class);
        Queue::fake();
    }

    private function pedir(string $email)
    {
        return $this->postJson('/api/v1/senha/esqueci', ['email' => $email]);
    }

    #[Test]
    public function a_resposta_e_identica_para_conta_existente_e_inexistente(): void
    {
        User::factory()->create(['email' => 'existe@exemplo.com', 'password' => 'senhaforte1']);

        $comConta = $this->pedir('existe@exemplo.com')->assertOk();
        $semConta = $this->pedir('ninguem@exemplo.com')->assertOk();

        $this->assertSame($comConta->getContent(), $semConta->getContent());
        $this->assertSame($comConta->status(), $semConta->status());
    }

    #[Test]
    public function a_mensagem_e_condicional_e_nao_afirma_que_existe(): void
    {
        $mensagem = $this->pedir('ninguem@exemplo.com')->json('message');

        $this->assertStringContainsString('Se este e-mail estiver cadastrado', $mensagem);
    }

    #[Test]
    public function conta_que_so_entra_pelo_google_tem_a_mesma_resposta(): void
    {
        // Diferenciar aqui entregaria QUAIS contas usam Google — informação
        // que também não é de ninguém.
        $conta = User::factory()->create(['email' => 'google@exemplo.com', 'password' => null]);
        $conta->contasSociais()->create([
            'provedor' => 'google', 'provedor_user_id' => 'g-1', 'vinculado_em' => now(),
        ]);

        User::factory()->create(['email' => 'senha@exemplo.com', 'password' => 'senhaforte1']);

        $this->assertSame(
            $this->pedir('senha@exemplo.com')->getContent(),
            $this->pedir('google@exemplo.com')->getContent(),
        );
    }

    #[Test]
    public function nenhum_email_e_enfileirado_para_conta_inexistente(): void
    {
        // A resposta é igual, mas o efeito não: não se manda e-mail para quem
        // não tem conta — seria spam a partir do nosso domínio.
        $this->pedir('ninguem@exemplo.com')->assertOk();

        Queue::assertNothingPushed();
        $this->assertDatabaseCount('tokens_de_email', 0);
    }

    #[Test]
    public function conta_com_senha_recebe_o_link(): void
    {
        User::factory()->create(['email' => 'maria@exemplo.com', 'password' => 'senhaforte1']);

        $this->pedir('maria@exemplo.com')->assertOk();

        Queue::assertPushed(EnviarEmailTransacional::class);
        $this->assertDatabaseHas('tokens_de_email', [
            'finalidade' => TokenDeEmail::REDEFINICAO_SENHA,
        ]);
    }

    #[Test]
    public function conta_so_do_google_recebe_email_explicando_em_vez_de_link(): void
    {
        // US4-4: a resposta pública não muda, mas quem lê o e-mail precisa
        // entender por que não há link — senão fica esperando para sempre.
        $conta = User::factory()->create(['email' => 'google@exemplo.com', 'password' => null]);
        $conta->contasSociais()->create([
            'provedor' => 'google', 'provedor_user_id' => 'g-1', 'vinculado_em' => now(),
        ]);

        $this->pedir('google@exemplo.com')->assertOk();

        Queue::assertPushed(EnviarEmailTransacional::class, function ($job) {
            $template = (new \ReflectionProperty($job, 'template'))->getValue($job);

            return $template === 'emails.senha-conta-google';
        });

        // Sem token de redefinição: não há senha para redefinir.
        $this->assertDatabaseCount('tokens_de_email', 0);
    }

    #[Test]
    public function o_email_e_normalizado_antes_de_procurar(): void
    {
        User::factory()->create(['email' => 'maria@exemplo.com', 'password' => 'senhaforte1']);

        $this->pedir('  Maria@Exemplo.COM ')->assertOk();

        Queue::assertPushed(EnviarEmailTransacional::class);
    }

    #[Test]
    public function e_mail_malformado_e_erro_de_validacao(): void
    {
        // Aqui pode recusar: é formato, não existência — não revela nada.
        $this->postJson('/api/v1/senha/esqueci', ['email' => 'nao-e-email'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    #[Test]
    public function o_pedido_tem_limite_de_tentativas(): void
    {
        // Sem limite, isto vira ferramenta de varredura e de envio em massa.
        User::factory()->create(['email' => 'maria@exemplo.com', 'password' => 'senhaforte1']);

        for ($i = 0; $i < 3; $i++) {
            $this->pedir('maria@exemplo.com')->assertOk();
        }

        $this->pedir('maria@exemplo.com')->assertStatus(429);
    }
}
