<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\AuditLog;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * US2-5 e decisão D1 (direção inversa) — conta nascida no Google define senha.
 *
 * A confirmação do titular aqui é a **sessão ativa**: quem já está autenticado
 * naquela conta é quem pode dar a ela um segundo meio de entrada. É o espelho
 * da união (US3), onde quem tem senha confirma com a senha.
 */
class SetPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function googleAccount(): User
    {
        $account = User::factory()->create(['password' => null, 'email_verified_at' => now()]);
        $account->assignRole(config('bora.account.initial_role'));
        $account->socialAccounts()->create([
            'provider' => 'google',
            'provider_user_id' => 'google-123',
            'linked_at' => now(),
        ]);

        return $account;
    }

    #[Test]
    public function a_google_account_sets_a_password_with_an_active_session(): void
    {
        $account = $this->googleAccount();
        $token = $account->createToken('celular')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/senha', ['password' => 'senhaforte1'])
            ->assertOk();

        $this->assertTrue($account->fresh()->hasPassword());
    }

    #[Test]
    public function after_setting_it_both_paths_sign_in(): void
    {
        $account = $this->googleAccount();
        $token = $account->createToken('celular')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/senha', ['password' => 'senhaforte1'])
            ->assertOk();

        // Mesma conta, agora com dois meios de entrada (RN-PLAT-002).
        $response = $this->postJson('/api/v1/sessoes', [
            'email' => $account->email,
            'password' => 'senhaforte1',
        ])->assertOk();

        $this->assertSame($account->id, $response->json('data.account.id'));
        $this->assertEqualsCanonicalizing(
            ['password', 'google'],
            $response->json('data.account.signs_in_with')
        );
        $this->assertDatabaseCount('users', 1);
    }

    #[Test]
    public function without_an_active_session_it_is_rejected(): void
    {
        // É a confirmação do titular; sem ela, qualquer um daria senha à conta
        // alheia e passaria a entrar por ela.
        $this->googleAccount();

        $this->postJson('/api/v1/senha', ['password' => 'senhaforte1'])
            ->assertUnauthorized();
    }

    #[Test]
    public function an_account_that_already_has_a_password_is_rejected(): void
    {
        // Trocar senha é outra operação, com regras próprias (exige a atual).
        $account = User::factory()->create(['password' => 'senhaAntiga1']);
        $token = $account->createToken('celular')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/senha', ['password' => 'senhaNova123'])
            ->assertStatus(422);
    }

    #[Test]
    public function a_short_password_is_rejected_with_an_error_on_the_field(): void
    {
        $account = $this->googleAccount();
        $token = $account->createToken('celular')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/senha', ['password' => 'curta'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');

        $this->assertFalse($account->fresh()->hasPassword());
    }

    #[Test]
    public function the_password_is_stored_hashed_and_the_operation_is_audited(): void
    {
        $account = $this->googleAccount();
        $token = $account->createToken('celular')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/senha', ['password' => 'senhaforte1'])
            ->assertOk();

        $this->assertNotSame('senhaforte1', $account->fresh()->password);
        $this->assertTrue(password_verify('senhaforte1', $account->fresh()->password));

        $record = Activity::where('event', AuditLog::PASSWORD_SET)->first();
        $this->assertNotNull($record);
        $this->assertFalse(AuditLog::containsSensitiveData($record));
    }

    #[Test]
    public function setting_a_password_does_not_drop_the_current_session(): void
    {
        $account = $this->googleAccount();
        $token = $account->createToken('celular')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/senha', ['password' => 'senhaforte1'])
            ->assertOk();

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/eu')
            ->assertOk();
    }
}
