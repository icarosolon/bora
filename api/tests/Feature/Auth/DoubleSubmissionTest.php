<?php

namespace Tests\Feature\Auth;

use Database\Seeders\RolesSeeder;
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
class DoubleSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Queue::fake();
    }

    #[Test]
    public function two_identical_submissions_create_only_one_account(): void
    {
        $data = [
            'name' => 'Maria',
            'email' => 'maria@exemplo.com',
            'password' => 'senhaforte1',
        ];

        $this->postJson('/api/v1/contas', $data)->assertCreated();
        $this->postJson('/api/v1/contas', $data)->assertStatus(422);

        $this->assertDatabaseCount('users', 1);
    }

    #[Test]
    public function the_second_submission_creates_no_extra_role_or_token(): void
    {
        // Não basta não duplicar a linha em `users`: a tentativa recusada não
        // pode deixar rastro pela metade.
        $data = [
            'name' => 'Maria',
            'email' => 'maria@exemplo.com',
            'password' => 'senhaforte1',
        ];

        $this->postJson('/api/v1/contas', $data)->assertCreated();

        $tokensAfterFirst = \DB::table('personal_access_tokens')->count();
        $verificationsAfterFirst = \DB::table('email_tokens')->count();

        $this->postJson('/api/v1/contas', $data)->assertStatus(422);

        $this->assertSame($tokensAfterFirst, \DB::table('personal_access_tokens')->count());
        $this->assertSame($verificationsAfterFirst, \DB::table('email_tokens')->count());
        $this->assertDatabaseCount('model_has_roles', 1);
    }
}
