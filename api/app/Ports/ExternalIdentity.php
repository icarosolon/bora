<?php

declare(strict_types=1);

namespace App\Ports;

use App\Domain\Account\Email;

/**
 * O que o Bora guarda de uma identidade externa — e nada além disso.
 *
 * Princípio III (LGPD / conteúdo de terceiros): do Google só entram nome, e-mail
 * e o identificador do provedor, que são os dados consentidos no OAuth. Foto,
 * lista de contatos e qualquer outro campo do payload são deliberadamente
 * descartados no adapter, não trafegam até o domínio.
 */
final readonly class ExternalIdentity
{
    public function __construct(
        public string $provider,
        public string $providerUserId,
        public Email $email,
        public ?string $name = null,
    ) {}
}
