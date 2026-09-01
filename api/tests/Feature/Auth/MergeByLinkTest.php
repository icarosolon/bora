<?php

namespace Tests\Feature\Auth;

use App\Jobs\SendTransactionalEmail;
use App\Models\EmailToken;
use App\Models\User;
use App\Ports\IdentityProvider;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\FakeIdentityProvider;
use Tests\TestCase;

/**
 * US3-3 — plano B da decisão D1: quem não lembra a senha confirma por link.
 *
 * Sem este caminho, quem esqueceu a senha ficaria preso: entraria pelo Google,
 * seria mandado a confirmar com a senha que não lembra, e não teria saída. Foi
 * exatamente por isso que a D1 previu o plano B.
 */
class MergeByLinkTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL = 'maria@exemplo.com';

    private FakeIdentityProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Queue::fake();

        $this->provider = new FakeIdentityProvider;
        $this->app->instance(IdentityProvider::class, $this->provider);
    }

    /** @return array{User, string} conta e `merge_token` */
    private function pendingMerge(): array
    {
        $account = User::factory()->create(['email' => self::EMAIL, 'password' => 'senhaforte1']);
        $account->assignRole(config('bora.account.initial_role'));

        $this->provider->returning(self::EMAIL);

        $token = $this->postJson('/api/v1/auth/google/sessoes', [
            'code' => 'x',
            'state' => $this->getJson('/api/v1/auth/google/url')->json('data.state'),
        ])->assertStatus(409)->json('data.merge_token');

        return [$account, $token];
    }

    /** Emite o link e devolve o valor em claro que iria no e-mail. */
    private function requestLink(string $mergeToken): string
    {
        $this->postJson('/api/v1/uniao-credenciais/link', ['merge_token' => $mergeToken])
            ->assertOk();

        $sent = null;
        Queue::assertPushed(SendTransactionalEmail::class, function ($job) use (&$sent) {
            $r = new \ReflectionProperty($job, 'variables');
            $sent = $r->getValue($job)['token'] ?? null;

            return true;
        });

        $this->assertNotNull($sent, 'o Job precisa carregar o token do link');

        return $sent;
    }

    #[Test]
    public function queues_the_email_with_the_link(): void
    {
        [, $mergeToken] = $this->pendingMerge();

        $this->postJson('/api/v1/uniao-credenciais/link', ['merge_token' => $mergeToken])
            ->assertOk();

        Queue::assertPushed(SendTransactionalEmail::class);
        $this->assertDatabaseHas('email_tokens', [
            'purpose' => EmailToken::CREDENTIAL_MERGE,
        ]);
    }

    #[Test]
    public function the_valid_link_completes_the_merge(): void
    {
        [$account, $mergeToken] = $this->pendingMerge();
        $link = $this->requestLink($mergeToken);

        $this->postJson('/api/v1/uniao-credenciais/link/confirmar', ['token' => $link])
            ->assertOk()
            ->assertJsonPath('data.account.id', $account->id);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('social_accounts', 1);
    }

    #[Test]
    public function the_link_is_single_use(): void
    {
        [, $mergeToken] = $this->pendingMerge();
        $link = $this->requestLink($mergeToken);

        $this->postJson('/api/v1/uniao-credenciais/link/confirmar', ['token' => $link])->assertOk();
        $this->postJson('/api/v1/uniao-credenciais/link/confirmar', ['token' => $link])->assertStatus(410);
    }

    #[Test]
    public function the_expired_link_is_rejected(): void
    {
        [, $mergeToken] = $this->pendingMerge();
        $link = $this->requestLink($mergeToken);

        EmailToken::query()->update(['expires_at' => now()->subMinute()]);

        $this->postJson('/api/v1/uniao-credenciais/link/confirmar', ['token' => $link])
            ->assertStatus(410);

        $this->assertDatabaseCount('social_accounts', 0);
    }

    #[Test]
    public function the_plain_text_token_is_not_stored_in_the_database(): void
    {
        [, $mergeToken] = $this->pendingMerge();
        $link = $this->requestLink($mergeToken);

        $record = EmailToken::where('purpose', EmailToken::CREDENTIAL_MERGE)->first();

        $this->assertNotSame($link, $record->token_hash);
        $this->assertSame(hash('sha256', $link), $record->token_hash);
    }

    #[Test]
    public function requesting_a_link_with_an_invalid_merge_token_is_rejected(): void
    {
        $this->postJson('/api/v1/uniao-credenciais/link', ['merge_token' => 'nao-existe'])
            ->assertStatus(410);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function the_link_carries_the_frontend_address_not_the_api_one(): void
    {
        // Quem tem tela é o `web/` (Princípio XI). Link apontando para a API
        // levaria a pessoa a um JSON.
        [, $mergeToken] = $this->pendingMerge();

        $this->postJson('/api/v1/uniao-credenciais/link', ['merge_token' => $mergeToken])->assertOk();

        Queue::assertPushed(SendTransactionalEmail::class, function ($job) {
            $url = (new \ReflectionProperty($job, 'variables'))->getValue($job)['url'] ?? '';

            return str_contains($url, '/unir-contas/confirmar');
        });
    }
}
