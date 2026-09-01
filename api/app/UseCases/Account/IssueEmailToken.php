<?php

declare(strict_types=1);

namespace App\UseCases\Account;

use App\Jobs\SendTransactionalEmail;
use App\Models\EmailToken;
use App\Models\User;

/**
 * Emite um link de uso único e o enfileira para envio.
 *
 * Concentra três regras que valem para as TRÊS finalidades (verificação, união,
 * redefinição), para nenhuma delas esquecer uma:
 *
 * 1. O valor em claro existe só aqui e no e-mail — o banco guarda o hash.
 * 2. Emitir um novo INVALIDA os anteriores da mesma finalidade. Dois links
 *    válidos ao mesmo tempo dobram a janela de ataque sem ganho nenhum: a
 *    pessoa vai usar o último que recebeu.
 * 3. O envio vai para a fila (Princípio VI) e a falha dele não derruba a
 *    operação que o originou (D5).
 */
final class IssueEmailToken
{
    /** @param array<string, mixed> $payload */
    public function execute(
        User $account,
        string $purpose,
        string $subject,
        string $template,
        array $payload = [],
        array $extraVariables = [],
    ): string {
        // Invalida os anteriores desta finalidade.
        $account->emailTokens()
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $plainText = bin2hex(random_bytes(32));

        $account->emailTokens()->create([
            'purpose' => $purpose,
            'token_hash' => hash('sha256', $plainText),
            'expires_at' => now()->addMinutes($this->lifetimeInMinutes($purpose)),
            'payload' => $payload ?: null,
        ]);

        SendTransactionalEmail::dispatch(
            $account->email,
            $subject,
            $template,
            [
                'name' => $account->name,
                'token' => $plainText,
                'url' => $this->frontendUrl($purpose, $plainText),
                ...$extraVariables,
            ],
        );

        return $plainText;
    }

    /**
     * Resolve o token em claro para o registro correspondente.
     *
     * Compara pelo hash: o valor em claro nunca é procurado no banco porque
     * nunca está lá.
     */
    public function resolve(string $plainText, string $purpose): ?EmailToken
    {
        return EmailToken::query()
            ->where('token_hash', hash('sha256', $plainText))
            ->where('purpose', $purpose)
            ->valid()
            ->first();
    }

    private function lifetimeInMinutes(string $purpose): int
    {
        return (int) config("bora.email_tokens.{$purpose}", 60);
    }

    /**
     * O link aponta para o `web/`, não para a API: quem tem tela é o front
     * (Princípio XI). A API só emite o token.
     */
    private function frontendUrl(string $purpose, string $token): string
    {
        $base = rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/');

        $path = match ($purpose) {
            EmailToken::EMAIL_VERIFICATION => '/verificar-email',
            EmailToken::PASSWORD_RESET => '/redefinir-senha',
            EmailToken::CREDENTIAL_MERGE => '/unir-contas/confirmar',
            default => '/',
        };

        return "{$base}{$path}?token={$token}";
    }
}
