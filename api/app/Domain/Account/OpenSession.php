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
 * `$account` é tipada como object e não como App\Models\User de propósito: o
 * núcleo não depende do Eloquent (Princípio VII).
 */
final readonly class OpenSession
{
    public function __construct(
        public object $account,
        public string $plainTextToken,
        public DateTimeInterface $expiresAt,
    ) {}
}
