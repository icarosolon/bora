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
 * request autenticada, e o middleware RenovarExpiracaoDoToken o aplica.
 *
 * Núcleo de domínio: recebe o "agora" por parâmetro em vez de chamar now(), o
 * que torna o teste de expiração determinístico sem precisar viajar no tempo do
 * framework.
 */
final readonly class PoliticaDeSessao
{
    public function __construct(public int $validadeEmDias) {}

    public function novoVencimento(?DateTimeInterface $agora = null): DateTimeImmutable
    {
        $base = $agora === null
            ? new DateTimeImmutable
            : DateTimeImmutable::createFromInterface($agora);

        return $base->modify("+{$this->validadeEmDias} days");
    }

    public function expirou(?DateTimeInterface $vencimento, ?DateTimeInterface $agora = null): bool
    {
        if ($vencimento === null) {
            return false; // sem vencimento gravado: não expira por tempo
        }

        $referencia = $agora === null
            ? new DateTimeImmutable
            : DateTimeImmutable::createFromInterface($agora);

        return $vencimento < $referencia;
    }
}
