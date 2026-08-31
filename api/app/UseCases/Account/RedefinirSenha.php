<?php

declare(strict_types=1);

namespace App\UseCases\Account;

use App\Exceptions\TokenDeEmailInvalido;
use App\Models\TokenDeEmail;
use App\Support\Auditoria;
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
final readonly class RedefinirSenha
{
    public function __construct(private EmitirTokenDeEmail $emitirToken) {}

    public function executar(string $tokenEmClaro, string $senha): void
    {
        $registro = $this->emitirToken->resolver(
            $tokenEmClaro,
            TokenDeEmail::REDEFINICAO_SENHA
        );

        if ($registro === null) {
            throw new TokenDeEmailInvalido('Peça um novo link em "Esqueci minha senha".');
        }

        $conta = $registro->user;

        DB::transaction(function () use ($registro, $conta, $senha) {
            // Marca o uso ANTES do efeito: se algo falhar depois, o link não
            // fica reutilizável.
            $registro->forceFill(['usado_em' => now()])->save();

            $conta->forceFill(['password' => $senha])->save();

            $conta->tokens()->delete();
        });

        Auditoria::registrar(
            Auditoria::SENHA_REDEFINIDA,
            sobre: $conta,
            autor: $conta,
            propriedades: ['origem' => 'link_de_recuperacao', 'sessoes_revogadas' => true],
        );
    }
}
