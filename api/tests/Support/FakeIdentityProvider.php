<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Account\Email;
use App\Ports\IdentityProviderFailure;
use App\Ports\ExternalIdentity;
use App\Ports\IdentityProvider;

/**
 * Provedor de identidade falso para os testes da US2.
 *
 * É esta classe que justifica a porta `IdentityProvider` existir
 * (Princípio VII): o teste troca o Google por isto e exercita TODOS os desfechos
 * — inclusive os que seriam impossíveis de provocar de propósito contra o
 * provedor real (falha de rede, provedor sem devolver e-mail).
 *
 * Nenhum teste da US2 fala com o Google de verdade. Isso é intencional: teste
 * que depende de rede e de conta externa é lento, frágil e não roda em CI.
 */
final class FakeIdentityProvider implements IdentityProvider
{
    private ?ExternalIdentity $identity = null;

    private ?IdentityProviderFailure $failure = null;

    /** @var list<string> */
    public array $receivedCodes = [];

    public function returning(string $email, string $providerUserId = 'google-123', ?string $name = 'Maria do Google'): self
    {
        $this->identity = new ExternalIdentity(
            provider: 'google',
            providerUserId: $providerUserId,
            email: Email::from($email),
            name: $name,
        );
        $this->failure = null;

        return $this;
    }

    /** Simula cancelamento, recusa, erro de rede ou provedor sem e-mail. */
    public function failing(string $reason = 'provedor recusou'): self
    {
        $this->failure = new IdentityProviderFailure($reason);
        $this->identity = null;

        return $this;
    }

    public function authorizationUrl(string $state): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?state='.$state;
    }

    public function identityFromCode(string $code): ExternalIdentity
    {
        $this->receivedCodes[] = $code;

        if ($this->failure !== null) {
            throw $this->failure;
        }

        if ($this->identity === null) {
            throw new IdentityProviderFailure('fake sem identidade configurada');
        }

        return $this->identity;
    }
}
