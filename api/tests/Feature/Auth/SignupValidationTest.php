<?php

namespace Tests\Feature\Auth;

use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * US1-5 — erro no campo, em linguagem humana, e nada criado.
 */
class SignupValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Queue::fake();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $fieldsWithErrors
     */
    #[Test]
    #[DataProvider('invalidInputs')]
    public function rejects_invalid_input_pointing_at_the_field(array $data, array $fieldsWithErrors): void
    {
        $this->postJson('/api/v1/contas', $data)
            ->assertStatus(422)
            ->assertJsonValidationErrors($fieldsWithErrors);

        $this->assertDatabaseCount('users', 0);
    }

    /** @return array<string, array{array<string, mixed>, array<int, string>}> */
    public static function invalidInputs(): array
    {
        $valid = ['name' => 'Maria', 'email' => 'maria@exemplo.com', 'password' => 'senhaforte1'];

        return [
            'sem nome' => [['name' => '', ...array_diff_key($valid, ['name' => ''])], ['name']],
            'sem e-mail' => [[...$valid, 'email' => ''], ['email']],
            'e-mail malformado' => [[...$valid, 'email' => 'maria.exemplo.com'], ['email']],
            'e-mail sem domínio' => [[...$valid, 'email' => 'maria@'], ['email']],
            'senha curta' => [[...$valid, 'password' => '1234567'], ['password']],
            'senha vazia' => [[...$valid, 'password' => ''], ['password']],
            'tudo vazio' => [[], ['name', 'email', 'password']],
        ];
    }

    #[Test]
    public function accepts_a_password_at_the_exact_minimum(): void
    {
        // Caso de limite declarado na spec: o mínimo exato passa.
        $minimum = (int) config('bora.account.minimum_password_length');

        $this->postJson('/api/v1/contas', [
            'name' => 'Maria',
            'email' => 'maria@exemplo.com',
            'password' => str_repeat('a', $minimum),
        ])->assertCreated();
    }

    #[Test]
    public function rejects_one_character_below_the_minimum(): void
    {
        $minimum = (int) config('bora.account.minimum_password_length');

        $this->postJson('/api/v1/contas', [
            'name' => 'Maria',
            'email' => 'maria@exemplo.com',
            'password' => str_repeat('a', $minimum - 1),
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    }

    #[Test]
    public function the_messages_are_in_portuguese_and_say_what_to_do(): void
    {
        $errors = $this->postJson('/api/v1/contas', [
            'name' => 'Maria',
            'email' => 'nao-e-email',
            'password' => '123',
        ])->json('errors');

        // ux-requirements.md proíbe "campo inválido": a mensagem tem de ensinar.
        $this->assertStringContainsString('@', $errors['email'][0]);
        $this->assertStringContainsString(
            (string) config('bora.account.minimum_password_length'),
            $errors['password'][0]
        );
    }

    #[Test]
    public function the_error_envelope_follows_the_constitution(): void
    {
        $this->postJson('/api/v1/contas', [])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }
}
