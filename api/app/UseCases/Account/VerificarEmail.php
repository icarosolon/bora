<?php

declare(strict_types=1);

namespace App\UseCases\Account;

use App\Exceptions\TokenDeEmailInvalido;
use App\Models\TokenDeEmail;
use App\Models\User;
use App\Support\Auditoria;
use Illuminate\Support\Facades\DB;

/**
 * Confirmar o e-mail pelo link (US1-7, decisão D5).
 *
 * Não exige sessão: a pessoa costuma abrir o link no aplicativo de e-mail,
 * muitas vezes noutro aparelho. Exigir login aqui quebraria o caminho comum.
 * A segurança vem do token: uso único, com prazo, e guardado só como hash.
 */
final readonly class VerificarEmail
{
    public function __construct(private EmitirTokenDeEmail $emitirToken) {}

    public function executar(string $tokenEmClaro): void
    {
        $registro = $this->emitirToken->resolver(
            $tokenEmClaro,
            TokenDeEmail::VERIFICACAO_EMAIL
        );

        if ($registro === null) {
            throw new TokenDeEmailInvalido('Entre na sua conta e peça um novo e-mail de confirmação.');
        }

        DB::transaction(function () use ($registro) {
            // Marcar o uso ANTES de aplicar o efeito: se algo falhar depois, o
            // token não fica reutilizável.
            $registro->forceFill(['usado_em' => now()])->save();

            $registro->user->forceFill(['email_verified_at' => now()])->save();
        });

        Auditoria::registrar(
            Auditoria::EMAIL_VERIFICADO,
            sobre: $registro->user,
            autor: $registro->user,
        );
    }

    /** Reenvia o link para quem está autenticado e ainda não confirmou. */
    public function reenviar(User $conta): void
    {
        $this->emitirToken->executar(
            conta: $conta,
            finalidade: TokenDeEmail::VERIFICACAO_EMAIL,
            assunto: 'Confirme seu e-mail no Bora',
            template: 'emails.verificacao',
        );
    }
}
