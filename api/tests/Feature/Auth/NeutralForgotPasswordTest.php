<?php

namespace Tests\Feature\Auth;

use App\Jobs\SendTransactionalEmail;
use App\Models\EmailToken;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * PROVA DA NÃO-ENUMERAÇÃO — FR-014, Princípio V.
 *
 * "Esqueci minha senha" é o endpoint mais fácil de transformar em verificador
 * de quais e-mails têm conta no Bora: basta comparar as respostas. Por isso a
 * resposta é **idêntica** exista ou não a conta — mesmo texto, mesmo status.
 *
 * O teste compara byte a byte de propósito. Uma vírgula diferente já seria um
 * sinal explorável.
 */
class NeutralForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Queue::fake();
    }

    private function requestReset(string $email)
    {
        return $this->postJson('/api/v1/senha/esqueci', ['email' => $email]);
    }

    #[Test]
    public function the_response_is_identical_for_existing_and_unknown_accounts(): void
    {
        User::factory()->create(['email' => 'existe@exemplo.com', 'password' => 'senhaforte1']);

        $withAccount = $this->requestReset('existe@exemplo.com')->assertOk();
        $withoutAccount = $this->requestReset('ninguem@exemplo.com')->assertOk();

        $this->assertSame($withAccount->getContent(), $withoutAccount->getContent());
        $this->assertSame($withAccount->status(), $withoutAccount->status());
    }

    #[Test]
    public function the_message_is_conditional_and_does_not_assert_existence(): void
    {
        $message = $this->requestReset('ninguem@exemplo.com')->json('message');

        $this->assertStringContainsString('Se este e-mail estiver cadastrado', $message);
    }

    #[Test]
    public function an_account_that_only_signs_in_with_google_gets_the_same_response(): void
    {
        // Diferenciar aqui entregaria QUAIS contas usam Google — informação
        // que também não é de ninguém.
        $account = User::factory()->create(['email' => 'google@exemplo.com', 'password' => null]);
        $account->socialAccounts()->create([
            'provider' => 'google', 'provider_user_id' => 'g-1', 'linked_at' => now(),
        ]);

        User::factory()->create(['email' => 'senha@exemplo.com', 'password' => 'senhaforte1']);

        $this->assertSame(
            $this->requestReset('senha@exemplo.com')->getContent(),
            $this->requestReset('google@exemplo.com')->getContent(),
        );
    }

    #[Test]
    public function no_email_is_queued_for_an_unknown_account(): void
    {
        // A resposta é igual, mas o efeito não: não se manda e-mail para quem
        // não tem conta — seria spam a partir do nosso domínio.
        $this->requestReset('ninguem@exemplo.com')->assertOk();

        Queue::assertNothingPushed();
        $this->assertDatabaseCount('email_tokens', 0);
    }

    #[Test]
    public function an_account_with_a_password_receives_the_link(): void
    {
        User::factory()->create(['email' => 'maria@exemplo.com', 'password' => 'senhaforte1']);

        $this->requestReset('maria@exemplo.com')->assertOk();

        Queue::assertPushed(SendTransactionalEmail::class);
        $this->assertDatabaseHas('email_tokens', [
            'purpose' => EmailToken::PASSWORD_RESET,
        ]);
    }

    #[Test]
    public function a_google_only_account_receives_an_explanation_instead_of_a_link(): void
    {
        // US4-4: a resposta pública não muda, mas quem lê o e-mail precisa
        // entender por que não há link — senão fica esperando para sempre.
        $account = User::factory()->create(['email' => 'google@exemplo.com', 'password' => null]);
        $account->socialAccounts()->create([
            'provider' => 'google', 'provider_user_id' => 'g-1', 'linked_at' => now(),
        ]);

        $this->requestReset('google@exemplo.com')->assertOk();

        Queue::assertPushed(SendTransactionalEmail::class, function ($job) {
            $template = (new \ReflectionProperty($job, 'template'))->getValue($job);

            return $template === 'emails.password-google-account';
        });

        // Sem token de redefinição: não há senha para redefinir.
        $this->assertDatabaseCount('email_tokens', 0);
    }

    #[Test]
    public function the_email_is_normalized_before_the_lookup(): void
    {
        User::factory()->create(['email' => 'maria@exemplo.com', 'password' => 'senhaforte1']);

        $this->requestReset('  Maria@Exemplo.COM ')->assertOk();

        Queue::assertPushed(SendTransactionalEmail::class);
    }

    #[Test]
    public function a_malformed_email_is_a_validation_error(): void
    {
        // Aqui pode recusar: é formato, não existência — não revela nada.
        $this->postJson('/api/v1/senha/esqueci', ['email' => 'nao-e-email'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    #[Test]
    public function the_request_is_rate_limited(): void
    {
        // Sem limite, isto vira ferramenta de varredura e de envio em massa.
        User::factory()->create(['email' => 'maria@exemplo.com', 'password' => 'senhaforte1']);

        for ($i = 0; $i < 3; $i++) {
            $this->requestReset('maria@exemplo.com')->assertOk();
        }

        $this->requestReset('maria@exemplo.com')->assertStatus(429);
    }
}
