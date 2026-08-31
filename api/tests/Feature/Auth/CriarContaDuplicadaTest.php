<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * PROVA DO BLOQUEIO — Princípio I (NON-NEGOTIABLE) e RN-PLAT-001.
 *
 * Este é um dos testes que a constituição exige: todo princípio NON-NEGOTIABLE
 * tocado precisa de teste que prove o bloqueio. Aqui a tentativa deliberada de
 * criar uma segunda conta com o mesmo e-mail tem de ser recusada — nunca
 * duplicada — inclusive quando o e-mail chega escrito de outro jeito.
 *
 * Roda contra MySQL (phpunit.xml), de propósito: o índice único é a última
 * linha de defesa e precisa ser exercitado no banco de verdade.
 */
class CriarContaDuplicadaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisSeeder::class);
        Queue::fake();
    }

    private function criarConta(string $email): void
    {
        $this->postJson('/api/v1/contas', [
            'nome' => 'Maria',
            'email' => $email,
            'senha' => 'senhaforte1',
        ])->assertCreated();
    }

    #[Test]
    public function recusa_segunda_conta_com_o_mesmo_email(): void
    {
        $this->criarConta('maria@exemplo.com');

        $this->postJson('/api/v1/contas', [
            'nome' => 'Outra Maria',
            'email' => 'maria@exemplo.com',
            'senha' => 'outrasenha9',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    #[Test]
    #[DataProvider('variacoesDeEscrita')]
    public function variacao_de_escrita_nao_cria_conta_paralela(string $variacao): void
    {
        // O caminho mais fácil de burlar "uma conta por e-mail" seria mudar a
        // caixa das letras. A normalização no value object Email fecha isso.
        $this->criarConta('maria@exemplo.com');

        $this->postJson('/api/v1/contas', [
            'nome' => 'Outra',
            'email' => $variacao,
            'senha' => 'outrasenha9',
        ])->assertStatus(422);

        $this->assertDatabaseCount('users', 1);
    }

    /** @return array<string, array{string}> */
    public static function variacoesDeEscrita(): array
    {
        return [
            'tudo maiúsculo' => ['MARIA@EXEMPLO.COM'],
            'caixa mista' => ['Maria@Exemplo.Com'],
            'espaços nas pontas' => ['  maria@exemplo.com  '],
            'espaço e maiúscula' => [' Maria@EXEMPLO.com'],
        ];
    }

    #[Test]
    public function a_mensagem_orienta_a_entrar_em_vez_de_so_recusar(): void
    {
        // ux-requirements.md: erro diz O QUE FAZER. "E-mail já cadastrado" sem
        // saída deixa a pessoa travada.
        $this->criarConta('maria@exemplo.com');

        $erro = $this->postJson('/api/v1/contas', [
            'nome' => 'Outra',
            'email' => 'maria@exemplo.com',
            'senha' => 'outrasenha9',
        ])->json('errors.email.0');

        $this->assertStringContainsString('já tem conta', $erro);
        $this->assertMatchesRegularExpression('/entre|senha/i', $erro);
    }

    #[Test]
    public function conta_que_so_entra_pelo_google_orienta_para_o_google(): void
    {
        // US2-4: a conta existe, mas não tem senha. Dizer "e-mail já cadastrado,
        // use sua senha" mandaria a pessoa para uma senha que não existe.
        $conta = User::factory()->create([
            'email' => 'maria@exemplo.com',
            'password' => null,
        ]);
        $conta->contasSociais()->create([
            'provedor' => 'google',
            'provedor_user_id' => '123',
            'vinculado_em' => now(),
        ]);

        $erro = $this->postJson('/api/v1/contas', [
            'nome' => 'Maria',
            'email' => 'maria@exemplo.com',
            'senha' => 'senhaforte1',
        ])->assertStatus(422)->json('errors.email.0');

        $this->assertStringContainsString('Google', $erro);
        $this->assertDatabaseCount('users', 1);
    }

    #[Test]
    public function o_indice_unico_do_banco_e_a_ultima_linha_de_defesa(): void
    {
        // Se algum dia a validação da borda for contornada, o banco ainda
        // precisa recusar. Este teste falha se alguém remover o índice único.
        User::factory()->create(['email' => 'maria@exemplo.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        User::factory()->create(['email' => 'maria@exemplo.com']);
    }
}
