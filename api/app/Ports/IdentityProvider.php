<?php

declare(strict_types=1);

namespace App\Ports;

/**
 * Porta para login social (Princípio VII — portas & adapters).
 *
 * O domínio fala com esta interface; quem conhece o Socialite é o adapter em
 * app/Adapters/Socialite/. Nenhum `use Laravel\Socialite\...` pode aparecer
 * fora de lá — é o que permite trocar o provedor (ou testar sem rede) sem tocar
 * em caso de uso.
 */
interface IdentityProvider
{
    /** URL para onde o navegador deve ir para autorizar. */
    public function authorizationUrl(string $state): string;

    /**
     * Troca o `code` recebido do provedor pelos dados da identidade.
     *
     * @throws \App\Ports\IdentityProviderFailure quando o provedor recusa,
     *         falha ou não devolve e-mail — casos que a US2-3 exige tratar com
     *         mensagem humana e SEM criar conta.
     */
    public function identityFromCode(string $code): ExternalIdentity;
}
