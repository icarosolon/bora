<?php

declare(strict_types=1);

namespace App\UseCases\Account;

use App\Models\User;
use App\Support\AuditLog;
use Illuminate\Validation\ValidationException;

/**
 * Conta nascida no Google define uma senha (US2-5, decisão D1 — direção
 * inversa).
 *
 * A confirmação do titular aqui é a **sessão ativa**: quem já está autenticado
 * naquela conta é quem pode lhe dar um segundo meio de entrada. É o espelho da
 * união (US3), onde quem tem senha confirma com a senha.
 *
 * Deliberadamente NÃO serve para trocar senha: quem já tem senha passa por
 * outro fluxo, que exige a atual. Confundir os dois transformaria uma sessão
 * roubada em troca de senha silenciosa.
 */
final readonly class SetPassword
{
    public function execute(User $account, string $password): void
    {
        if ($account->hasPassword()) {
            throw ValidationException::withMessages([
                'password' => 'Esta conta já tem senha. Para trocá-la, use "Esqueci minha senha".',
            ]);
        }

        $account->forceFill(['password' => $password])->save();

        AuditLog::record(
            AuditLog::PASSWORD_SET,
            subject: $account,
            causer: $account,
            properties: ['origin' => 'google_account'],
        );
    }
}
