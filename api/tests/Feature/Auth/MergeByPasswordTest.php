<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Ports\IdentityProvider;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\FakeIdentityProvider;
use Tests\TestCase;

/**
 * US3 — unir credenciais confirmando com a senha (decisão D1, caminho
 * principal).
 *
 * A confirmação do titular aqui é **a senha da conta existente**: só quem já
 * podia entrar por ela pode lhe acrescentar um segundo meio de entrada. É o
 * espelho da US2-5, onde quem entrou pelo Google confirma com a sessão ativa.
 */
class MergeByPasswordTest extends TestCase
{
    use RefreshDatabase;

    private FakeIdentityProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);

        $this->provider = new FakeIdentityProvider;
        $this->app->instance(IdentityProvider::class, $this->provider);
    }

    /**
     * Cria conta por e-mail/senha e leva o fluxo do Google até o 409.
     *
     * @return array{User, string} conta e o `merge_token`
     */
    private function pendingMerge(string $email = 'maria@exemplo.com'): array
    {
        $account = User::factory()->create(['email' => $email, 'password' => 'senhaforte1']);
        $account->assignRole(config('bora.account.initial_role'));

        $this->provider->returning($email);

        $response = $this->postJson('/api/v1/auth/google/sessoes', [
            'code' => 'x',
            'state' => $this->getJson('/api/v1/auth/google/url')->json('data.state'),
        ])->assertStatus(409);

        return [$account, $response->json('data.merge_token')];
    }

    #[Test]
    public function the_correct_password_merges_and_opens_a_session(): void
    {
        [$account, $token] = $this->pendingMerge();

        $this->postJson('/api/v1/uniao-credenciais', [
            'merge_token' => $token,
            'password' => 'senhaforte1',
        ])->assertOk()
            ->assertJsonPath('data.account.id', $account->id)
            ->assertJsonStructure(['message', 'data' => ['account', 'token', 'expires_at']]);
    }

    #[Test]
    public function after_the_merge_there_is_one_account_with_both_paths(): void
    {
        [$account, $token] = $this->pendingMerge();

        $response = $this->postJson('/api/v1/uniao-credenciais', [
            'merge_token' => $token,
            'password' => 'senhaforte1',
        ])->assertOk();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('social_accounts', 1);
        $this->assertEqualsCanonicalizing(
            ['password', 'google'],
            $response->json('data.account.signs_in_with')
        );
    }

    #[Test]
    public function after_the_merge_google_signs_straight_into_the_same_account(): void
    {
        // É o desfecho que a pessoa espera: uniu uma vez, nunca mais é
        // perguntada.
        [$account, $token] = $this->pendingMerge();

        $this->postJson('/api/v1/uniao-credenciais', [
            'merge_token' => $token,
            'password' => 'senhaforte1',
        ])->assertOk();

        $this->provider->returning('maria@exemplo.com');

        $this->postJson('/api/v1/auth/google/sessoes', [
            'code' => 'y',
            'state' => $this->getJson('/api/v1/auth/google/url')->json('data.state'),
        ])->assertOk()
            ->assertJsonPath('data.account.id', $account->id);

        $this->assertDatabaseCount('users', 1);
    }

    #[Test]
    public function after_the_merge_the_password_still_works(): void
    {
        [$account, $token] = $this->pendingMerge();

        $this->postJson('/api/v1/uniao-credenciais', [
            'merge_token' => $token,
            'password' => 'senhaforte1',
        ])->assertOk();

        $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com',
            'password' => 'senhaforte1',
        ])->assertOk()->assertJsonPath('data.account.id', $account->id);
    }

    #[Test]
    public function the_merge_token_is_single_use(): void
    {
        [, $token] = $this->pendingMerge();

        $this->postJson('/api/v1/uniao-credenciais', [
            'merge_token' => $token, 'password' => 'senhaforte1',
        ])->assertOk();

        $this->postJson('/api/v1/uniao-credenciais', [
            'merge_token' => $token, 'password' => 'senhaforte1',
        ])->assertStatus(410);
    }

    #[Test]
    public function the_success_message_explains_what_changed(): void
    {
        [, $token] = $this->pendingMerge();

        $message = $this->postJson('/api/v1/uniao-credenciais', [
            'merge_token' => $token, 'password' => 'senhaforte1',
        ])->json('message');

        $this->assertStringContainsString('Google', $message);
        $this->assertStringContainsString('senha', $message);
    }

    #[Test]
    public function the_merge_changes_neither_the_email_nor_the_account_name(): void
    {
        // O provedor externo não redefine a identidade de uma conta existente.
        $account = User::factory()->create([
            'email' => 'maria@exemplo.com',
            'name' => 'Maria Souza',
            'password' => 'senhaforte1',
        ]);
        $account->assignRole(config('bora.account.initial_role'));

        $this->provider->returning('maria@exemplo.com', name: 'Outro Nome No Google');

        $token = $this->postJson('/api/v1/auth/google/sessoes', [
            'code' => 'x',
            'state' => $this->getJson('/api/v1/auth/google/url')->json('data.state'),
        ])->json('data.merge_token');

        $this->postJson('/api/v1/uniao-credenciais', [
            'merge_token' => $token, 'password' => 'senhaforte1',
        ])->assertOk();

        $this->assertSame('Maria Souza', $account->fresh()->name);
        $this->assertSame('maria@exemplo.com', $account->fresh()->email);
    }
}
