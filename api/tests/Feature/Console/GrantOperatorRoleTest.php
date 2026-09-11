<?php

namespace Tests\Feature\Console;

use App\Models\User;
use App\Support\AuditLog;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Concessão do papel de operação (spec 002, FR-027).
 *
 * O papel existe porque a aprovação de reivindicação (FR-008) precisa de alguém
 * autorizado a aprovar. O comando é o único caminho: o papel **nunca** se
 * autoatribui e não nasce de cadastro.
 */
class GrantOperatorRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    #[Test]
    public function it_grants_the_operation_role_to_an_existing_account(): void
    {
        $account = User::factory()->create(['email' => 'icaro@exemplo.com']);

        $this->artisan('bora:grant-operator', ['email' => 'icaro@exemplo.com'])
            ->assertExitCode(0);

        $this->assertTrue(
            $account->fresh()->hasRole(config('bora.account.operation_role')),
            'a conta deveria ter o papel de operação depois do comando',
        );
    }

    #[Test]
    public function running_it_twice_neither_duplicates_nor_fails(): void
    {
        $account = User::factory()->create(['email' => 'icaro@exemplo.com']);

        $this->artisan('bora:grant-operator', ['email' => 'icaro@exemplo.com'])->assertExitCode(0);
        $this->artisan('bora:grant-operator', ['email' => 'icaro@exemplo.com'])->assertExitCode(0);

        // O vínculo é um só: rodar de novo não empilha uma segunda linha na
        // tabela de papéis, e não explode por chave duplicada.
        $this->assertCount(1, $account->fresh()->roles);

        // E a segunda execução não gera um segundo registro de auditoria: nada
        // mudou, então não há escrita a auditar.
        $this->assertCount(
            1,
            Activity::where('event', AuditLog::OPERATOR_ROLE_GRANTED)->get(),
        );
    }

    #[Test]
    public function it_refuses_an_unknown_email_without_side_effects(): void
    {
        $this->artisan('bora:grant-operator', ['email' => 'ninguem@exemplo.com'])
            ->expectsOutputToContain('Nao existe conta com o e-mail ninguem@exemplo.com')
            ->assertExitCode(1);

        // Sem efeito colateral: nenhuma conta criada e nada auditado.
        $this->assertDatabaseCount('users', 0);
        $this->assertCount(0, Activity::where('event', AuditLog::OPERATOR_ROLE_GRANTED)->get());
    }

    #[Test]
    public function the_grant_produces_a_recoverable_audit_record(): void
    {
        $account = User::factory()->create(['email' => 'icaro@exemplo.com']);

        $this->artisan('bora:grant-operator', ['email' => 'icaro@exemplo.com'])->assertExitCode(0);

        $activity = Activity::where('event', AuditLog::OPERATOR_ROLE_GRANTED)->sole();

        // Quem recebeu, quando, e por onde veio a concessão.
        $this->assertTrue($activity->subject->is($account));
        $this->assertNotNull($activity->created_at);
        $this->assertSame('console', $activity->properties['origin']);

        // Autor nulo de propósito: o terminal não tem sessão, e o AuditLog
        // manda registrar ação sem autor como tal em vez de atribuí-la a
        // alguém errado.
        $this->assertNull($activity->causer);

        $this->assertFalse(
            AuditLog::containsSensitiveData($activity),
            'o registro de concessão não pode carregar nada sensível',
        );
    }
}
