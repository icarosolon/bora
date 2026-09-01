<?php

declare(strict_types=1);

namespace App\Domain\Account;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Validade da sessão — decisão D7: 30 dias de INATIVIDADE, renovados a cada uso.
 *
 * O Sanctum não tem janela deslizante: sua opção `expiration` é prazo absoluto
 * contado da criação do token. Esta política calcula o novo vencimento a cada
 * request autenticada, e o middleware RefreshTokenExpiration o aplica.
 *
 * Núcleo de domínio: recebe o "agora" por parâmetro em vez de chamar now(), o
 * que torna o teste de expiração determinístico sem precisar viajar no tempo do
 * framework.
 */
final readonly class SessionPolicy
{
    public function __construct(public int $lifetimeInDays) {}

    public function newExpiry(?DateTimeInterface $now = null): DateTimeImmutable
    {
        $base = $now === null
            ? new DateTimeImmutable
            : DateTimeImmutable::createFromInterface($now);

        return $base->modify("+{$this->lifetimeInDays} days");
    }

    public function hasExpired(?DateTimeInterface $expiry, ?DateTimeInterface $now = null): bool
    {
        if ($expiry === null) {
            return false; // sem vencimento gravado: não expira por tempo
        }

        $reference = $now === null
            ? new DateTimeImmutable
            : DateTimeImmutable::createFromInterface($now);

        return $expiry < $reference;
    }
}
