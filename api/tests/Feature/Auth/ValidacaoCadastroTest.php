<?php

namespace Tests\Feature\Auth;

use Database\Seeders\PapeisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * US1-5 — erro no campo, em linguagem humana, e nada criado.
 */
class ValidacaoCadastroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisSeeder::class);
        Queue::fake();
    }

    /**
     * @param  array<string, mixed>  $dados
     * @param  array<int, string>  $camposComErro
     */
    #[Test]
    #[DataProvider('entradasInvalidas')]
    public function recusa_entrada_invalida_apontando_o_campo(array $dados, array $camposComErro): void
    {
        $this->postJson('/api/v1/contas', $dados)
            ->assertStatus(422)
            ->assertJsonValidationErrors($camposComErro);

        $this->assertDatabaseCount('users', 0);
    }

    /** @return array<string, array{array<string, mixed>, array<int, string>}> */
    public static function entradasInvalidas(): array
    {
        $valido = ['nome' => 'Maria', 'email' => 'maria@exemplo.com', 'senha' => 'senhaforte1'];

        return [
            'sem nome' => [['nome' => '', ...array_diff_key($valido, ['nome' => ''])], ['nome']],
            'sem e-mail' => [[...$valido, 'email' => ''], ['email']],
            'e-mail malformado' => [[...$valido, 'email' => 'maria.exemplo.com'], ['email']],
            'e-mail sem domínio' => [[...$valido, 'email' => 'maria@'], ['email']],
            'senha curta' => [[...$valido, 'senha' => '1234567'], ['senha']],
            'senha vazia' => [[...$valido, 'senha' => ''], ['senha']],
            'tudo vazio' => [[], ['nome', 'email', 'senha']],
        ];
    }

    #[Test]
    public function aceita_senha_no_minimo_exato(): void
    {
        // Caso de limite declarado na spec: o mínimo exato passa.
        $minimo = (int) config('bora.conta.senha_minima');

        $this->postJson('/api/v1/contas', [
            'nome' => 'Maria',
            'email' => 'maria@exemplo.com',
            'senha' => str_repeat('a', $minimo),
        ])->assertCreated();
    }

    #[Test]
    public function recusa_um_caractere_abaixo_do_minimo(): void
    {
        $minimo = (int) config('bora.conta.senha_minima');

        $this->postJson('/api/v1/contas', [
            'nome' => 'Maria',
            'email' => 'maria@exemplo.com',
            'senha' => str_repeat('a', $minimo - 1),
        ])->assertStatus(422)->assertJsonValidationErrors('senha');
    }

    #[Test]
    public function as_mensagens_sao_em_portugues_e_dizem_o_que_fazer(): void
    {
        $erros = $this->postJson('/api/v1/contas', [
            'nome' => 'Maria',
            'email' => 'nao-e-email',
            'senha' => '123',
        ])->json('errors');

        // ux-requirements.md proíbe "campo inválido": a mensagem tem de ensinar.
        $this->assertStringContainsString('@', $erros['email'][0]);
        $this->assertStringContainsString(
            (string) config('bora.conta.senha_minima'),
            $erros['senha'][0]
        );
    }

    #[Test]
    public function o_envelope_de_erro_segue_a_constituicao(): void
    {
        $this->postJson('/api/v1/contas', [])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }
}
