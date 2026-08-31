<?php

declare(strict_types=1);

namespace App\Domain\Account;

use DateTimeInterface;

/**
 * O login com Google encontrou uma conta já existente com aquele e-mail.
 *
 * NÃO é erro: é um passo a mais (US3). E, sobretudo, **nada foi gravado** — nem
 * conta, nem vínculo. Este objeto carrega só o necessário para a tela pedir a
 * confirmação do titular; a união em si acontece na US3.
 *
 * O `token` é curto e de uso único, e **não autentica nada** — serve apenas
 * para amarrar a confirmação a esta tentativa específica.
 */
final readonly class UniaoPendente
{
    public function __construct(
        public Email $email,
        public string $token,
        public DateTimeInterface $expiraEm,
    ) {}
}
