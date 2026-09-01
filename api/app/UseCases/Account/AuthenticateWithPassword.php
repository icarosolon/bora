<?php

declare(strict_types=1);

namespace App\UseCases\Account;

use App\Domain\Account\Email;
use App\Domain\Account\SessionPolicy;
use App\Domain\Account\OpenSession;
use App\Exceptions\AccountUsesProvider;
use App\Exceptions\InvalidCredentials;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Entrar com e-mail e senha (US1-2, US1-6).
 */
final readonly class AuthenticateWithPassword
{
    public function __construct(private SessionPolicy $sessionPolicy) {}

    public function execute(
        Email $email,
        string $password,
        ?string $device = null,
    ): OpenSession {
        $account = User::where('email', $email->value)->first();

        if ($account === null) {
            // Mesma exceção de senha errada: a resposta não pode diferenciar
            // "e-mail não existe" de "senha errada" (FR-006).
            //
            // O Hash::check em string fixa existe para gastar tempo parecido com
            // o do caminho válido — sem isso, a diferença de latência entregaria
            // quais e-mails têm conta, que é justamente o que a mensagem única
            // tenta esconder.
            Hash::check($password, '$2y$12$'.str_repeat('x', 53));

            throw new InvalidCredentials;
        }

        if (! $account->hasPassword()) {
            $provider = $account->socialAccounts()->value('provider') ?? 'google';

            throw new AccountUsesProvider($provider);
        }

        if (! Hash::check($password, $account->password)) {
            throw new InvalidCredentials;
        }

        $expiresAt = $this->sessionPolicy->newExpiry();

        $token = $account->createToken($device ?? 'browser', ['*'], $expiresAt);

        $account->forceFill(['last_seen_at' => now()])->saveQuietly();

        return new OpenSession(
            account: $account->fresh(['roles', 'socialAccounts']),
            plainTextToken: $token->plainTextToken,
            expiresAt: $expiresAt,
        );
    }
}
