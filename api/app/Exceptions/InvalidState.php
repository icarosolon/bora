<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Validation\ValidationException;

/**
 * O `state` do fluxo OAuth nao confere.
 *
 * Vira 422 (e nao 401) de proposito: nao e credencial recusada, e um pedido
 * malformado ou velho. A tela recomeca o fluxo em vez de dizer a pessoa que ela
 * errou algo -- porque ela nao errou.
 *
 * Acontece em tres situacoes, todas tratadas igual: o state expirou, ja foi
 * usado, ou nunca foi emitido por nos (o caso que a protecao existe para pegar).
 */
final class InvalidState
{
    public static function raise(): never
    {
        throw ValidationException::withMessages([
            'state' => 'Este pedido expirou. Toque em "Entrar com Google" de novo.',
        ]);
    }
}
