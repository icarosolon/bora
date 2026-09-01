<?php

declare(strict_types=1);

namespace App\UseCases\Account;

use App\Domain\Account\Email;
use App\Domain\Account\SessionPolicy;
use App\Domain\Account\OpenSession;
use App\Models\EmailToken;
use App\Models\User;
use App\Support\AuditLog;
use Illuminate\Support\Facades\DB;

/**
 * Criar conta com e-mail e senha (US1, RN-PLAT-001).
 *
 * Invariantes que este caso de uso sustenta:
 *
 * - **Uma conta por e-mail normalizado.** A checagem de duplicata acontece na
 *   validação da borda (regra EmailAvailableForSignup), e o índice único do banco é
 *   a última linha de defesa. Aqui a garantia é a TRANSAÇÃO: conta, papel e
 *   token de verificação nascem juntos ou não nascem.
 * - **A conta nasce utilizável** (Princípio II): papel de rolezeiro, sem plano,
 *   sem paywall, sem etapa de pagamento.
 * - **Verificação não bloqueia** (D5): o e-mail sai por Job; se o envio falhar,
 *   a conta continua de pé.
 */
final readonly class RegisterAccount
{
    public function __construct(
        private SessionPolicy $sessionPolicy,
        private IssueEmailToken $issueToken,
    ) {}

    public function execute(
        string $name,
        Email $email,
        string $password,
        ?string $device = null,
    ): OpenSession {
        $account = DB::transaction(function () use ($name, $email, $password) {
            $account = User::create([
                'name' => $name,
                'email' => $email->value,
                'password' => $password,
            ]);

            $account->assignRole(config('bora.account.initial_role'));

            return $account;
        });

        // Fora da transação: enfileirar e-mail dentro dela correria o risco de
        // o Job rodar antes do commit e não encontrar a conta.
        $this->issueToken->execute(
            account: $account,
            purpose: EmailToken::EMAIL_VERIFICATION,
            subject: 'Confirme seu e-mail no Bora',
            template: 'emails.verification',
        );

        AuditLog::record(
            AuditLog::ACCOUNT_CREATED,
            subject: $account,
            causer: $account,
            properties: ['origin' => 'email_password_signup'],
        );

        $expiresAt = $this->sessionPolicy->newExpiry();

        $token = $account->createToken($device ?? 'browser', ['*'], $expiresAt);

        $account->forceFill(['last_seen_at' => now()])->saveQuietly();

        return new OpenSession(
            account: $account->fresh(['roles', 'socialAccounts']),
            plainTextToken: $token->plainTextToken,
            expiresAt: $expiresAt,
        );
    }
}
