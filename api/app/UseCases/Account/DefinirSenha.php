<?php

declare(strict_types=1);

namespace App\UseCases\Account;

use App\Models\User;
use App\Support\Auditoria;
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
final readonly class DefinirSenha
{
    public function executar(User $conta, string $senha): void
    {
        if ($conta->temSenha()) {
            throw ValidationException::withMessages([
                'senha' => 'Esta conta já tem senha. Para trocá-la, use "Esqueci minha senha".',
            ]);
        }

        $conta->forceFill(['password' => $senha])->save();

        Auditoria::registrar(
            Auditoria::SENHA_DEFINIDA,
            sobre: $conta,
            autor: $conta,
            propriedades: ['origem' => 'conta_google'],
        );
    }
}
