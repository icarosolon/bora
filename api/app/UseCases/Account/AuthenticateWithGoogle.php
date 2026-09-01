<?php

declare(strict_types=1);

namespace App\UseCases\Account;

use App\Domain\Account\SessionPolicy;
use App\Domain\Account\OpenSession;
use App\Domain\Account\PendingMerge;
use App\Models\SocialAccount;
use App\Models\User;
use App\Ports\ExternalIdentity;
use App\Ports\IdentityProvider;
use App\Support\AuditLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Entrar com Google (US2, RN-PLAT-002).
 *
 * Três desfechos possíveis, e a decisão entre eles é a regra desta feature:
 *
 * 1. **Já existe vínculo** com aquele identificador do provedor → entra na
 *    mesma conta. Casa pelo `provider_user_id`, não pelo e-mail: quem troca o
 *    e-mail no Google não perde a conta nem ganha uma segunda.
 * 2. **E-mail inédito** → cria a conta única, já com e-mail verificado (o
 *    Google verificou) e papel de rolezeiro.
 * 3. **E-mail já tem conta** (criada por e-mail/senha) → **não cria nada** e
 *    devolve união pendente. Unir é a US3; aqui só se constata (Princípio I).
 */
final readonly class AuthenticateWithGoogle
{
    private const STATE_PREFIX = 'bora:oauth:state:';

    public function __construct(
        private IdentityProvider $provider,
        private SessionPolicy $sessionPolicy,
    ) {}

    /**
     * Passo 1 — a URL para onde o navegador deve ir.
     *
     * @return array{url: string, state: string}
     */
    public function start(): array
    {
        $state = bin2hex(random_bytes(24));

        // O `state` vive no cache, não em sessão: a API é sem estado. O prazo
        // curto limita a janela em que um valor capturado teria serventia.
        Cache::put(
            self::STATE_PREFIX.$state,
            true,
            now()->addMinutes((int) config('bora.merge.lifetime_minutes'))
        );

        return [
            'url' => $this->provider->authorizationUrl($state),
            'state' => $state,
        ];
    }

    /**
     * Passo 2 — troca o `code` por sessão.
     *
     * @throws \Illuminate\Validation\ValidationException quando o `state` não confere
     * @throws \App\Ports\IdentityProviderFailure
     */
    public function complete(string $code, string $state): OpenSession|PendingMerge
    {
        $this->consumeState($state);

        $identity = $this->provider->identityFromCode($code);

        $link = SocialAccount::where('provider', $identity->provider)
            ->where('provider_user_id', $identity->providerUserId)
            ->first();

        if ($link !== null) {
            return $this->openSession($link->user);
        }

        $existingAccount = User::where('email', $identity->email->value)->first();

        if ($existingAccount !== null) {
            return $this->pendingMerge($existingAccount, $identity);
        }

        return $this->openSession($this->createAccount($identity));
    }

    /**
     * Uso único: um `state` capturado não pode servir duas vezes. É consumido
     * ANTES de falar com o provedor — se sobrevivesse a uma tentativa
     * malsucedida, deixaria de ser proteção.
     */
    private function consumeState(string $state): void
    {
        $key = self::STATE_PREFIX.$state;

        if (! Cache::pull($key)) {
            \App\Exceptions\InvalidState::raise();
        }
    }

    private function createAccount(ExternalIdentity $identity): User
    {
        $account = DB::transaction(function () use ($identity) {
            $account = User::create([
                'name' => $identity->name ?: (string) $identity->email,
                'email' => $identity->email->value,
                // Sem senha: a pessoa pode definir uma depois, autenticada (D1).
                'password' => null,
            ]);

            // `email_verified_at` fica FORA do `#[Fillable]` de propósito
            // (Princípio V): se fosse atribuível em massa, um payload de
            // cadastro poderia marcar a própria conta como verificada. Aqui a
            // marcação é deliberada e confiável — o Google já verificou este
            // e-mail, e pedir confirmação de novo seria fricção sem ganho.
            $account->forceFill(['email_verified_at' => now()])->save();

            $account->assignRole(config('bora.account.initial_role'));

            $account->socialAccounts()->create([
                'provider' => $identity->provider,
                'provider_user_id' => $identity->providerUserId,
                'provider_email' => $identity->email->value,
                'linked_at' => now(),
            ]);

            return $account;
        });

        AuditLog::record(
            AuditLog::ACCOUNT_CREATED,
            subject: $account,
            causer: $account,
            properties: ['origin' => 'google_signup'],
        );

        return $account;
    }

    private function pendingMerge(User $account, ExternalIdentity $identity): PendingMerge
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = now()->addMinutes((int) config('bora.merge.lifetime_minutes'));

        // Guardado no cache, não no banco: é efêmero e nada foi decidido ainda.
        // Só o hash — o valor em claro existe no caminho de volta da request.
        Cache::put(
            'bora:merge:'.hash('sha256', $token),
            [
                'user_id' => $account->id,
                'provider' => $identity->provider,
                'provider_user_id' => $identity->providerUserId,
                'provider_email' => $identity->email->value,
            ],
            $expiresAt,
        );

        return new PendingMerge(
            email: $identity->email,
            token: $token,
            expiresAt: $expiresAt,
        );
    }

    private function openSession(User $account): OpenSession
    {
        $expiresAt = $this->sessionPolicy->newExpiry();

        $token = $account->createToken('browser', ['*'], $expiresAt);

        $account->forceFill(['last_seen_at' => now()])->saveQuietly();

        return new OpenSession(
            account: $account->fresh(['roles', 'socialAccounts']),
            plainTextToken: $token->plainTextToken,
            expiresAt: $expiresAt,
        );
    }
}
