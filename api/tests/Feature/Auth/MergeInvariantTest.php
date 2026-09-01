<?php

namespace Tests\Feature\Auth;

use App\Models\EmailToken;
use App\Models\User;
use App\Ports\IdentityProvider;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\FakeIdentityProvider;
use Tests\TestCase;

/**
 * PROVA DO BLOQUEIO — Princípio I (NON-NEGOTIABLE), cenário US3-6.
 *
 * **O teste mais importante da spec 001.** A união é o único ponto do produto
 * em que duas identidades se encontram, e portanto o único em que uma conta
 * paralela poderia nascer. A invariante é dura: **em QUALQUER desfecho** —
 * confirmada, cancelada, expirada, senha errada, link usado duas vezes — o
 * número de contas com aquele e-mail é exatamente um.
 *
 * Se este arquivo ficar vermelho, o produto violou a constituição.
 */
class MergeInvariantTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL = 'maria@exemplo.com';

    private FakeIdentityProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);

        $this->provider = new FakeIdentityProvider;
        $this->app->instance(IdentityProvider::class, $this->provider);
    }

    private function accountWithPassword(): User
    {
        $account = User::factory()->create(['email' => self::EMAIL, 'password' => 'senhaforte1']);
        $account->assignRole(config('bora.account.initial_role'));

        return $account;
    }

    private function requestMerge(): string
    {
        $this->provider->returning(self::EMAIL);

        return $this->postJson('/api/v1/auth/google/sessoes', [
            'code' => 'x',
            'state' => $this->getJson('/api/v1/auth/google/url')->json('data.state'),
        ])->assertStatus(409)->json('data.merge_token');
    }

    private function assertOnlyOneAccount(string $whereItFailed): void
    {
        $this->assertSame(
            1,
            User::where('email', self::EMAIL)->count(),
            "Princípio I violado em: {$whereItFailed}"
        );
    }

    #[Test]
    public function one_account_after_a_confirmed_merge(): void
    {
        $this->accountWithPassword();
        $token = $this->requestMerge();

        $this->postJson('/api/v1/uniao-credenciais', [
            'merge_token' => $token, 'password' => 'senhaforte1',
        ])->assertOk();

        $this->assertOnlyOneAccount('união confirmada');
        $this->assertDatabaseCount('social_accounts', 1);
    }

    #[Test]
    public function one_account_when_the_person_simply_gives_up(): void
    {
        // Cancelar é simplesmente não confirmar. Nada deve ter sido gravado no
        // passo do 409.
        $this->accountWithPassword();
        $this->requestMerge();

        $this->assertOnlyOneAccount('união abandonada');
        $this->assertDatabaseCount('social_accounts', 0);
    }

    #[Test]
    public function one_account_when_the_password_is_wrong(): void
    {
        $this->accountWithPassword();
        $token = $this->requestMerge();

        $this->postJson('/api/v1/uniao-credenciais', [
            'merge_token' => $token, 'password' => 'senhaerrada9',
        ])->assertUnauthorized();

        $this->assertOnlyOneAccount('senha errada');
        $this->assertDatabaseCount('social_accounts', 0);
    }

    #[Test]
    public function one_account_when_the_token_expires(): void
    {
        $this->accountWithPassword();
        $token = $this->requestMerge();

        $this->travel((int) config('bora.merge.lifetime_minutes') + 1)->minutes();

        $this->postJson('/api/v1/uniao-credenciais', [
            'merge_token' => $token, 'password' => 'senhaforte1',
        ])->assertStatus(410);

        $this->travelBack();
        $this->assertOnlyOneAccount('token expirado');
        $this->assertDatabaseCount('social_accounts', 0);
    }

    #[Test]
    public function one_account_after_several_merge_attempts(): void
    {
        // A pessoa tenta, desiste, tenta de novo, confirma. Cada tentativa
        // emite um token novo; nenhuma delas pode gerar conta.
        $this->accountWithPassword();

        $this->requestMerge();
        $this->requestMerge();
        $last = $this->requestMerge();

        $this->assertOnlyOneAccount('três tentativas sem confirmar');

        $this->postJson('/api/v1/uniao-credenciais', [
            'merge_token' => $last, 'password' => 'senhaforte1',
        ])->assertOk();

        $this->assertOnlyOneAccount('confirmada na terceira tentativa');
        $this->assertDatabaseCount('social_accounts', 1);
    }

    #[Test]
    public function one_account_even_confirming_by_password_and_by_link(): void
    {
        // Os dois caminhos ficam válidos ao mesmo tempo (quem pediu o link pode
        // lembrar a senha). Usar os dois não pode gerar vínculo duplicado.
        $this->accountWithPassword();
        $token = $this->requestMerge();

        $this->postJson('/api/v1/uniao-credenciais/link', ['merge_token' => $token])->assertOk();

        $record = EmailToken::where('purpose', EmailToken::CREDENTIAL_MERGE)->first();
        $this->assertNotNull($record);

        $this->postJson('/api/v1/uniao-credenciais', [
            'merge_token' => $token, 'password' => 'senhaforte1',
        ])->assertOk();

        // O link ficou obsoleto: a união já aconteceu.
        $this->assertOnlyOneAccount('senha depois de pedir o link');
        $this->assertDatabaseCount('social_accounts', 1);
    }

    #[Test]
    public function the_unique_index_prevents_a_duplicate_link_in_the_database(): void
    {
        // Última linha de defesa: se a borda for contornada, o banco recusa.
        $account = $this->accountWithPassword();
        $account->socialAccounts()->create([
            'provider' => 'google', 'provider_user_id' => 'google-123', 'linked_at' => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        $account->socialAccounts()->create([
            'provider' => 'google', 'provider_user_id' => 'google-999', 'linked_at' => now(),
        ]);
    }
}
