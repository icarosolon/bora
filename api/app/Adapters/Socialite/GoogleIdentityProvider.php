<?php

declare(strict_types=1);

namespace App\Adapters\Socialite;

use App\Domain\Account\Email;
use App\Ports\IdentityProviderFailure;
use App\Ports\ExternalIdentity;
use App\Ports\IdentityProvider;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * Adapter do Socialite para o Google (Princípio VII — portas & adapters).
 *
 * **Este é o único arquivo do backend que importa o Socialite.** Casos de uso e
 * domínio falam com `IdentityProvider`; é isso que permite testar todos os
 * desfechos do login social sem rede nem conta externa.
 *
 * Modo `stateless`: não há sessão de servidor no fluxo (a API é sem estado,
 * Princípio IV). Em troca, o Socialite deixa de verificar o `state` — a
 * proteção contra CSRF do OAuth passa a ser nossa, em `AuthenticateWithGoogle`.
 */
final class GoogleIdentityProvider implements IdentityProvider
{
    public function authorizationUrl(string $state): string
    {
        return Socialite::driver('google')
            ->stateless()
            ->scopes(['openid', 'profile', 'email'])
            ->with(['state' => $state])
            ->redirect()
            ->getTargetUrl();
    }

    public function identityFromCode(string $code): ExternalIdentity
    {
        try {
            $driver = Socialite::driver('google')->stateless();

            // O Socialite lê o `code` da requisição corrente. Como aqui ele
            // chega pelo corpo de um POST do `web/` (e não como query de um
            // redirecionamento), monta-se uma requisição própria em vez de
            // mexer na global.
            $driver->setRequest(Request::create('/', 'GET', ['code' => $code]));

            $user = $driver->user();
        } catch (Throwable $e) {
            // Cancelamento, código expirado, rede fora, resposta inesperada:
            // tudo vira a mesma falha. O detalhe técnico fica no log, nunca na
            // tela (ver GoogleController).
            throw new IdentityProviderFailure($e->getMessage(), previous: $e);
        }

        $email = Email::tryFrom((string) $user->getEmail());

        if ($email === null) {
            // Sem e-mail não há identidade utilizável: é ele que amarra a conta
            // única (RN-PLAT-001). Acontece quando a pessoa nega o escopo.
            throw new IdentityProviderFailure('provedor não devolveu e-mail utilizável');
        }

        // Princípio III: só o que foi consentido e é necessário. Foto, lista de
        // contatos e o resto do payload são descartados aqui, e não trafegam
        // para dentro do domínio.
        return new ExternalIdentity(
            provider: 'google',
            providerUserId: (string) $user->getId(),
            email: $email,
            name: $user->getName() ?: null,
        );
    }
}
