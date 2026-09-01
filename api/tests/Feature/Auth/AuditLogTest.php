<?php

namespace Tests\Feature\Auth;

use App\Models\EmailToken;
use App\Models\User;
use App\Support\AuditLog;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * RN-PLAT-004 / Princípio VIII (auditoria) e Princípio V (nada sensível em log).
 *
 * As duas coisas juntas de propósito: auditar bem é registrar QUE aconteceu e
 * QUEM fez — e não registrar O QUE a pessoa digitou.
 */
class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Queue::fake();
    }

    #[Test]
    public function creating_an_account_produces_a_record(): void
    {
        $this->postJson('/api/v1/contas', [
            'name' => 'Maria',
            'email' => 'maria@exemplo.com',
            'password' => 'senhaforte1',
        ])->assertCreated();

        $record = Activity::latest('id')->first();

        $this->assertNotNull($record, 'Toda escrita de domínio audita (Princípio VIII).');
        $this->assertSame(AuditLog::ACCOUNT_CREATED, $record->event);
        $this->assertSame(User::first()->id, $record->subject_id);
    }

    #[Test]
    public function verifying_the_email_produces_a_record(): void
    {
        $account = User::factory()->create(['email_verified_at' => null]);
        $plainText = bin2hex(random_bytes(32));
        $account->emailTokens()->create([
            'purpose' => EmailToken::EMAIL_VERIFICATION,
            'token_hash' => hash('sha256', $plainText),
            'expires_at' => now()->addDay(),
        ]);

        $this->postJson('/api/v1/email/verificar', ['token' => $plainText])->assertOk();

        $this->assertTrue(
            Activity::where('event', AuditLog::EMAIL_VERIFIED)->exists()
        );
    }

    #[Test]
    public function no_record_carries_a_password_or_a_token(): void
    {
        // O teste que a spec exige: "senha e tokens nunca aparecem em log".
        $this->postJson('/api/v1/contas', [
            'name' => 'Maria',
            'email' => 'maria@exemplo.com',
            'password' => 'senhaSuperSecreta123',
        ])->assertCreated();

        $this->postJson('/api/v1/sessoes', [
            'email' => 'maria@exemplo.com',
            'password' => 'senhaSuperSecreta123',
        ])->assertOk();

        foreach (Activity::all() as $record) {
            $content = json_encode($record->properties);

            $this->assertStringNotContainsString('senhaSuperSecreta123', $content);
            $this->assertFalse(
                AuditLog::containsSensitiveData($record),
                "O registro {$record->id} carrega propriedade sensível."
            );
        }
    }

    #[Test]
    public function the_filter_blocks_a_sensitive_key_even_if_someone_passes_it(): void
    {
        // Defesa em profundidade: um caso de uso futuro pode passar 'password'
        // por descuido. A classe AuditLog remove antes de gravar.
        $account = User::factory()->create();

        AuditLog::record(
            AuditLog::ACCOUNT_CREATED,
            subject: $account,
            properties: [
                'origin' => 'cadastro',
                'password' => 'nao-deveria-passar',
                'merge_token' => 'nem-isto',
            ],
        );

        $record = Activity::latest('id')->first();

        $this->assertSame('cadastro', $record->properties['origin']);
        $this->assertArrayNotHasKey('password', $record->properties->toArray());
        $this->assertArrayNotHasKey('merge_token', $record->properties->toArray());
    }

    #[Test]
    public function the_token_hash_is_blocked_too(): void
    {
        $account = User::factory()->create();

        AuditLog::record(
            AuditLog::EMAIL_VERIFIED,
            subject: $account,
            properties: ['token_hash' => hash('sha256', 'x')],
        );

        $this->assertArrayNotHasKey(
            'token_hash',
            Activity::latest('id')->first()->properties->toArray()
        );
    }
}
