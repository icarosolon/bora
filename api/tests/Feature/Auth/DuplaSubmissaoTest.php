<?php

namespace Tests\Feature\Auth;

use Database\Seeders\PapeisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Edge case da spec — toque duplo no botão em rede lenta.
 *
 * O front desabilita o botão durante o envio, mas isso é conveniência de tela,
 * não garantia: uma requisição repetida (recarregar, reenviar, cliente teimoso)
 * chega igual à API. A garantia é do backend.
 */
class DuplaSubmissaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisSeeder::class);
        Queue::fake();
    }

    #[Test]
    public function duas_submissoes_iguais_criam_uma_conta_so(): void
    {
        $dados = [
            'nome' => 'Maria',
            'email' => 'maria@exemplo.com',
            'senha' => 'senhaforte1',
        ];

        $this->postJson('/api/v1/contas', $dados)->assertCreated();
        $this->postJson('/api/v1/contas', $dados)->assertStatus(422);

        $this->assertDatabaseCount('users', 1);
    }

    #[Test]
    public function a_segunda_submissao_nao_cria_papel_nem_token_extra(): void
    {
        // Não basta não duplicar a linha em `users`: a tentativa recusada não
        // pode deixar rastro pela metade.
        $dados = [
            'nome' => 'Maria',
            'email' => 'maria@exemplo.com',
            'senha' => 'senhaforte1',
        ];

        $this->postJson('/api/v1/contas', $dados)->assertCreated();

        $tokensDepoisDoPrimeiro = \DB::table('personal_access_tokens')->count();
        $verificacoesDepoisDoPrimeiro = \DB::table('tokens_de_email')->count();

        $this->postJson('/api/v1/contas', $dados)->assertStatus(422);

        $this->assertSame($tokensDepoisDoPrimeiro, \DB::table('personal_access_tokens')->count());
        $this->assertSame($verificacoesDepoisDoPrimeiro, \DB::table('tokens_de_email')->count());
        $this->assertDatabaseCount('model_has_roles', 1);
    }
}
