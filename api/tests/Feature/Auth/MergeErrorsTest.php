<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Ports\IdentityProvider;
use App\Support\AuditLog;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\Support\FakeIdentityProvider;
use Tests\TestCase;

/**
 * US3-4 e US3-5 — caminhos de erro da união, e a auditoria do evento.
 *
 * O ponto mais importante daqui: **a união é sujeita ao mesmo limite de
 * tentativas do login**. Sem isso, ela viraria a porta livre para adivinhar a
 * senha de uma conta — bastaria iniciar o fluxo do Google e tentar à vontade,
 * contornando o bloqueio da tela de entrar.
 */
class MergeErrorsTest extends TestCase
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

    private function pendingMerge(): string
    {
        $account = User::factory()->create(['email' => self::EMAIL, 'password' => 'senhaforte1']);
        $account->assignRole(config('bora.account.initial_role'));

        $this->provider->returning(self::EMAIL);

        return $this->postJson('/api/v1/auth/google/sessoes', [
            'code' => 'x',
            'state' => $this->getJson('/api/v1/auth/google/url')->json('data.state'),
        ])->assertStatus(409)->json('data.merge_token');
    }

    #[Test]
    public function a_wrong_password_returns_401_with_a_useful_message(): void
    {
        $token = $this->pendingMerge();

        $message = $this->postJson('/api/v1/uniao-credenciais', [
            'merge_token' => $token, 'password' => 'senhaerrada9',
        ])->assertUnauthorized()->json('message');

        // ux-requirements.md: erro diz o que fazer — aqui, o plano B.
        $this->assertMatchesRegularExpression('/link|e-mail/i', $message);
    }

    #[Test]
    public function the_token_survives_one_wrong_password(): void
    {
        // Se errar a senha queimasse o token, a pessoa teria de recomeçar o
        // fluxo do Google a cada engano de digitação.
        $token = $this->pendingMerge();

        $this->postJson('/api/v1/uniao-credenciais', [
            'merge_token' => $token, 'password' => 'errada1234',
        ])->assertUnauthorized();

        $this->postJson('/api/v1/uniao-credenciais', [
            'merge_token' => $token, 'password' => 'senhaforte1',
        ])->assertOk();
    }

    #[Test]
    public function the_merge_respects_the_same_rate_limit_as_sign_in(): void
    {
        $token = $this->pendingMerge();
        $limit = (int) config('bora.attempts.per_minute');

        for ($i = 0; $i < $limit; $i++) {
            $this->postJson('/api/v1/uniao-credenciais', [
                'merge_token' => $token, 'password' => 'errada1234',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/v1/uniao-credenciais', [
            'merge_token' => $token, 'password' => 'errada1234',
        ])->assertStatus(429);
    }

    #[Test]
    public function the_block_applies_even_with_the_right_password(): void
    {
        $token = $this->pendingMerge();
        $limit = (int) config('bora.attempts.per_minute');

        for ($i = 0; $i < $limit; $i++) {
            $this->postJson('/api/v1/uniao-credenciais', [
                'merge_token' => $token, 'password' => 'errada1234',
            ]);
        }

        $this->postJson('/api/v1/uniao-credenciais', [
            'merge_token' => $token, 'password' => 'senhaforte1',
        ])->assertStatus(429);
    }

    #[Test]
    public function an_unknown_merge_token_returns_410(): void
    {
        $this->postJson('/api/v1/uniao-credenciais', [
            'merge_token' => 'nunca-emitido', 'password' => 'senhaforte1',
        ])->assertStatus(410);
    }

    #[Test]
    public function missing_fields_are_a_validation_error(): void
    {
        $this->postJson('/api/v1/uniao-credenciais', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['merge_token', 'password']);
    }

    #[Test]
    public function the_merge_is_audited_without_sensitive_values(): void
    {
        $token = $this->pendingMerge();

        $this->postJson('/api/v1/uniao-credenciais', [
            'merge_token' => $token, 'password' => 'senhaforte1',
        ])->assertOk();

        $record = Activity::where('event', AuditLog::CREDENTIALS_MERGED)->first();

        $this->assertNotNull($record, 'a união é escrita de domínio e precisa auditar');
        $this->assertFalse(AuditLog::containsSensitiveData($record));
        $this->assertStringNotContainsString('senhaforte1', json_encode($record->properties));
    }

    #[Test]
    public function the_error_response_never_exposes_the_password(): void
    {
        $token = $this->pendingMerge();

        $content = $this->postJson('/api/v1/uniao-credenciais', [
            'merge_token' => $token, 'password' => 'tentativaErrada9',
        ])->getContent();

        $this->assertStringNotContainsString('tentativaErrada9', $content);
    }
}
