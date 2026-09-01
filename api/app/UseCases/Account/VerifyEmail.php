<?php

declare(strict_types=1);

namespace App\UseCases\Account;

use App\Exceptions\InvalidEmailToken;
use App\Models\EmailToken;
use App\Models\User;
use App\Support\AuditLog;
use Illuminate\Support\Facades\DB;

/**
 * Confirmar o e-mail pelo link (US1-7, decisão D5).
 *
 * Não exige sessão: a pessoa costuma abrir o link no aplicativo de e-mail,
 * muitas vezes noutro aparelho. Exigir login aqui quebraria o caminho comum.
 * A segurança vem do token: uso único, com prazo, e guardado só como hash.
 */
final readonly class VerifyEmail
{
    public function __construct(private IssueEmailToken $issueToken) {}

    public function execute(string $plainTextToken): void
    {
        $record = $this->issueToken->resolve(
            $plainTextToken,
            EmailToken::EMAIL_VERIFICATION
        );

        if ($record === null) {
            throw new InvalidEmailToken('Entre na sua conta e peça um novo e-mail de confirmação.');
        }

        DB::transaction(function () use ($record) {
            // Marcar o uso ANTES de aplicar o efeito: se algo falhar depois, o
            // token não fica reutilizável.
            $record->forceFill(['used_at' => now()])->save();

            $record->user->forceFill(['email_verified_at' => now()])->save();
        });

        AuditLog::record(
            AuditLog::EMAIL_VERIFIED,
            subject: $record->user,
            causer: $record->user,
        );
    }

    /** Reenvia o link para quem está autenticado e ainda não confirmou. */
    public function resend(User $account): void
    {
        $this->issueToken->execute(
            account: $account,
            purpose: EmailToken::EMAIL_VERIFICATION,
            subject: 'Confirme seu e-mail no Bora',
            template: 'emails.verification',
        );
    }
}
