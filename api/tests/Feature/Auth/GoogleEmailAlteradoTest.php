<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Ports\ProvedorDeIdentidade;
use Database\Seeders\PapeisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ProvedorDeIdentidadeFake;
use Tests\TestCase;

/**
 * Edge case da spec: o e-mail da conta Google mudou desde o vínculo.
 *
 * É por isso que `contas_sociais` guarda o `provedor_user_id` e casa por ele, e
 * não pelo e-mail. Casar por e-mail faria a pessoa perder o acesso à própria
 * conta ao trocar o endereço no Google — ou, pior, criar uma segunda conta.
 */
class GoogleEmailAlteradoTest extends TestCase
{
    use RefreshDatabase;

    private ProvedorDeIdentidadeFake $provedor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisSeeder::class);

        $this->provedor = new ProvedorDeIdentidadeFake;
        $this->app->instance(ProvedorDeIdentidade::class, $this->provedor);
    }

    private function entrarComGoogle()
    {
        return $this->postJson('/api/v1/auth/google/sessoes', [
            'code' => 'x',
            'state' => $this->getJson('/api/v1/auth/google/url')->json('data.state'),
        ]);
    }

    #[Test]
    public function e_mail_novo_com_o_mesmo_identificador_entra_na_mesma_conta(): void
    {
        $this->provedor->devolvendo('maria.antiga@gmail.com', provedorUserId: 'google-abc');
        $this->entrarComGoogle()->assertOk();

        $idOriginal = User::first()->id;

        // A pessoa trocou o e-mail no Google; o identificador dela não muda.
        $this->provedor->devolvendo('maria.nova@gmail.com', provedorUserId: 'google-abc');
        $resposta = $this->entrarComGoogle()->assertOk();

        $this->assertDatabaseCount('users', 1);
        $this->assertSame($idOriginal, $resposta->json('data.conta.id'));
    }

    #[Test]
    public function identificadores_diferentes_sao_pessoas_diferentes(): void
    {
        $this->provedor->devolvendo('maria@gmail.com', provedorUserId: 'google-aaa');
        $this->entrarComGoogle()->assertOk();

        $this->provedor->devolvendo('joao@gmail.com', provedorUserId: 'google-bbb');
        $this->entrarComGoogle()->assertOk();

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('contas_sociais', 2);
    }

    #[Test]
    public function o_email_da_conta_no_bora_nao_muda_sozinho(): void
    {
        // Trocar o e-mail da conta é outra feature, com confirmação própria.
        // Um provedor externo não pode alterar a identidade de uma conta aqui.
        $this->provedor->devolvendo('maria.antiga@gmail.com', provedorUserId: 'google-abc');
        $this->entrarComGoogle()->assertOk();

        $this->provedor->devolvendo('maria.nova@gmail.com', provedorUserId: 'google-abc');
        $this->entrarComGoogle()->assertOk();

        $this->assertSame('maria.antiga@gmail.com', User::first()->email);
    }
}
