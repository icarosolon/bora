<?php

declare(strict_types=1);

namespace App\UseCases\Account;

use App\Domain\Account\Email;
use App\Jobs\SendTransactionalEmail;
use App\Models\EmailToken;
use App\Models\User;

/**
 * "Esqueci minha senha", passo 1 (US4, decisão D6).
 *
 * A regra que manda aqui é a **não-enumeração**: quem chama este caso de uso
 * nunca descobre se a conta existe. O método não devolve nada e não lança nada
 * — todos os caminhos terminam em silêncio, e a borda responde sempre a mesma
 * mensagem condicional.
 *
 * Três desfechos internos, invisíveis de fora:
 *
 * 1. **Conta com senha** → link de redefinição.
 * 2. **Conta só do Google** → e-mail explicando que ali não há senha para
 *    redefinir. Sem isso, a pessoa ficaria esperando um link que nunca vem.
 * 3. **Não existe conta** → nada. Mandar e-mail para quem não se cadastrou
 *    seria spam saindo do nosso domínio.
 */
final readonly class RequestPasswordReset
{
    public function __construct(private IssueEmailToken $issueToken) {}

    public function execute(Email $email): void
    {
        $account = User::where('email', $email->value)->first();

        if ($account === null) {
            return;
        }

        if (! $account->hasPassword()) {
            $this->notifySignsInWithProvider($account);

            return;
        }

        $this->issueToken->execute(
            account: $account,
            purpose: EmailToken::PASSWORD_RESET,
            subject: 'Redefinir sua senha do Bora',
            template: 'emails.password-reset',
        );
    }

    /**
     * Não passa pelo emissor de token de propósito: não há token nenhum a
     * emitir, e criar um seria criar uma credencial sem uso.
     */
    private function notifySignsInWithProvider(User $account): void
    {
        $provider = $account->socialAccounts()->value('provider') ?? 'google';

        SendTransactionalEmail::dispatch(
            $account->email,
            'Sobre a sua conta no Bora',
            'emails.password-google-account',
            [
                'name' => $account->name,
                'provider' => $provider === 'google' ? 'Google' : ucfirst($provider),
                'url' => rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/').'/entrar',
            ],
        );
    }
}
