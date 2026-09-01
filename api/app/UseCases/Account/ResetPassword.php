<?php

declare(strict_types=1);

namespace App\UseCases\Account;

use App\Exceptions\InvalidEmailToken;
use App\Models\EmailToken;
use App\Support\AuditLog;
use Illuminate\Support\Facades\DB;

/**
 * "Esqueci minha senha", passo 2 (US4, FR-015).
 *
 * Duas decisões que valem explicar:
 *
 * **Revoga TODAS as sessões.** A recuperação de senha é o caminho de quem pode
 * ter tido a conta comprometida. Manter sessões vivas deixaria o invasor
 * dentro, e a troca de senha viraria teatro.
 *
 * **Não abre sessão nova.** Quem chegou aqui provou que lê o e-mail, não que é
 * a pessoa naquele aparelho — o link pode ter sido aberto num computador
 * emprestado. Redefinida a senha, a pessoa entra normalmente.
 */
final readonly class ResetPassword
{
    public function __construct(private IssueEmailToken $issueToken) {}

    public function execute(string $plainTextToken, string $password): void
    {
        $record = $this->issueToken->resolve(
            $plainTextToken,
            EmailToken::PASSWORD_RESET
        );

        if ($record === null) {
            throw new InvalidEmailToken('Peça um novo link em "Esqueci minha senha".');
        }

        $account = $record->user;

        DB::transaction(function () use ($record, $account, $password) {
            // Marca o uso ANTES do efeito: se algo falhar depois, o link não
            // fica reutilizável.
            $record->forceFill(['used_at' => now()])->save();

            $account->forceFill(['password' => $password])->save();

            $account->tokens()->delete();
        });

        AuditLog::record(
            AuditLog::PASSWORD_RESET,
            subject: $account,
            causer: $account,
            properties: ['origin' => 'recovery_link', 'sessions_revoked' => true],
        );
    }
}
