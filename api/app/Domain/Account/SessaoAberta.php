<?php

declare(strict_types=1);

namespace App\Domain\Account;

use DateTimeInterface;

/**
 * Resultado de uma autenticação bem-sucedida.
 *
 * O token em claro existe só neste objeto, no caminho de volta da request — ele
 * nunca é persistido (o banco guarda o hash) e nunca vai para log (Princípio V).
 *
 * `$conta` é tipada como object e não como App\Models\User de propósito: o
 * núcleo não depende do Eloquent (Princípio VII).
 */
final readonly class SessaoAberta
{
    public function __construct(
        public object $conta,
        public string $tokenEmClaro,
        public DateTimeInterface $expiraEm,
    ) {}
}
