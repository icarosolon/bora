<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolesSeeder;
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
class DuplicateAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Queue::fake();
    }

    private function createAccount(string $email): void
    {
        $this->postJson('/api/v1/contas', [
            'name' => 'Maria',
            'email' => $email,
            'password' => 'senhaforte1',
        ])->assertCreated();
    }

    #[Test]
    public function rejects_a_second_account_with_the_same_email(): void
    {
        $this->createAccount('maria@exemplo.com');

        $this->postJson('/api/v1/contas', [
            'name' => 'Outra Maria',
            'email' => 'maria@exemplo.com',
            'password' => 'outrasenha9',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    #[Test]
    #[DataProvider('spellingVariations')]
    public function a_spelling_variation_does_not_create_a_parallel_account(string $variation): void
    {
        // O caminho mais fácil de burlar "uma conta por e-mail" seria mudar a
        // caixa das letras. A normalização no value object Email fecha isso.
        $this->createAccount('maria@exemplo.com');

        $this->postJson('/api/v1/contas', [
            'name' => 'Outra',
            'email' => $variation,
            'password' => 'outrasenha9',
        ])->assertStatus(422);

        $this->assertDatabaseCount('users', 1);
    }

    /** @return array<string, array{string}> */
    public static function spellingVariations(): array
    {
        return [
            'tudo maiúsculo' => ['MARIA@EXEMPLO.COM'],
            'caixa mista' => ['Maria@Exemplo.Com'],
            'espaços nas pontas' => ['  maria@exemplo.com  '],
            'espaço e maiúscula' => [' Maria@EXEMPLO.com'],
        ];
    }

    #[Test]
    public function the_message_guides_to_sign_in_instead_of_just_refusing(): void
    {
        // ux-requirements.md: erro diz O QUE FAZER. "E-mail já cadastrado" sem
        // saída deixa a pessoa travada.
        $this->createAccount('maria@exemplo.com');

        $error = $this->postJson('/api/v1/contas', [
            'name' => 'Outra',
            'email' => 'maria@exemplo.com',
            'password' => 'outrasenha9',
        ])->json('errors.email.0');

        $this->assertStringContainsString('já tem conta', $error);
        $this->assertMatchesRegularExpression('/entre|senha/i', $error);
    }

    #[Test]
    public function an_account_that_only_signs_in_with_google_points_to_google(): void
    {
        // US2-4: a conta existe, mas não tem senha. Dizer "e-mail já cadastrado,
        // use sua senha" mandaria a pessoa para uma senha que não existe.
        $account = User::factory()->create([
            'email' => 'maria@exemplo.com',
            'password' => null,
        ]);
        $account->socialAccounts()->create([
            'provider' => 'google',
            'provider_user_id' => '123',
            'linked_at' => now(),
        ]);

        $error = $this->postJson('/api/v1/contas', [
            'name' => 'Maria',
            'email' => 'maria@exemplo.com',
            'password' => 'senhaforte1',
        ])->assertStatus(422)->json('errors.email.0');

        $this->assertStringContainsString('Google', $error);
        $this->assertDatabaseCount('users', 1);
    }

    #[Test]
    public function the_unique_index_is_the_last_line_of_defence(): void
    {
        // Se algum dia a validação da borda for contornada, o banco ainda
        // precisa recusar. Este teste falha se alguém remover o índice único.
        User::factory()->create(['email' => 'maria@exemplo.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        User::factory()->create(['email' => 'maria@exemplo.com']);
    }
}
