<?php

declare(strict_types=1);

namespace App\UseCases\Account;

use App\Domain\Account\SessionPolicy;
use App\Domain\Account\OpenSession;
use App\Exceptions\InvalidCredentials;
use App\Exceptions\InvalidEmailToken;
use App\Models\EmailToken;
use App\Models\User;
use App\Support\AuditLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Unir credenciais Google ↔ e-mail/senha (US3, RN-PLAT-002, decisão D1).
 *
 * É o único ponto do produto em que duas identidades se encontram — e portanto
 * o único em que uma conta paralela poderia nascer. A invariante do Princípio I
 * é dura: **em qualquer desfecho, uma conta só**.
 *
 * Dois caminhos de confirmação do titular, ambos provando posse da conta
 * existente:
 *
 * - **Senha** (principal): quem sabe a senha é dono da conta.
 * - **Link por e-mail** (plano B): quem lê o e-mail é dono da conta. Existe
 *   porque, sem ele, quem esqueceu a senha ficaria preso — entraria pelo
 *   Google, seria mandado confirmar com a senha que não lembra, e não teria
 *   saída.
 *
 * Os dois ficam válidos ao mesmo tempo de propósito: quem pede o link pode
 * lembrar a senha no meio do caminho.
 */
final readonly class MergeCredentials
{
    private const PREFIX = 'bora:merge:';

    public function __construct(
        private SessionPolicy $sessionPolicy,
        private IssueEmailToken $issueToken,
    ) {}

    /** Caminho principal: confirma com a senha da conta existente. */
    public function confirmWithPassword(string $mergeToken, string $password): OpenSession
    {
        $pending = $this->readPending($mergeToken);
        $account = User::find($pending['user_id']);

        if ($account === null) {
            throw new InvalidEmailToken('Entre com o Google de novo para recomeçar.');
        }

        if (! $account->hasPassword() || ! Hash::check($password, $account->password)) {
            // O token NÃO é consumido aqui: errar a digitação não pode obrigar
            // a pessoa a recomeçar o fluxo do Google inteiro. Quem protege
            // contra tentativa e erro é o rate limit da rota, o mesmo do login.
            throw new class extends HttpException
            {
                public function __construct()
                {
                    parent::__construct(
                        401,
                        'Senha não confere. Tente de novo ou receba um link por e-mail.'
                    );
                }
            };
        }

        $this->consume($mergeToken);

        return $this->apply($account, $pending, 'password');
    }

    /** Plano B, passo 1: envia o link de confirmação para o e-mail da conta. */
    public function sendLink(string $mergeToken): void
    {
        $pending = $this->readPending($mergeToken);
        $account = User::find($pending['user_id']);

        if ($account === null) {
            throw new InvalidEmailToken('Entre com o Google de novo para recomeçar.');
        }

        $this->issueToken->execute(
            account: $account,
            purpose: EmailToken::CREDENTIAL_MERGE,
            subject: 'Confirme a união das suas formas de entrar no Bora',
            template: 'emails.credential-merge',
            // O contexto do vínculo viaja com o token: o link precisa saber
            // QUAL identidade externa está sendo unida.
            payload: [
                'provider' => $pending['provider'],
                'provider_user_id' => $pending['provider_user_id'],
                'provider_email' => $pending['provider_email'],
            ],
        );
    }

    /** Plano B, passo 2: confirma pelo link recebido. */
    public function confirmWithLink(string $token): OpenSession
    {
        $record = $this->issueToken->resolve($token, EmailToken::CREDENTIAL_MERGE);

        if ($record === null) {
            throw new InvalidEmailToken('Entre com o Google de novo para recomeçar.');
        }

        $record->forceFill(['used_at' => now()])->save();

        return $this->apply($record->user, $record->payload ?? [], 'link');
    }

    /**
     * @return array{user_id:int, provider:string, provider_user_id:string, provider_email:?string}
     */
    private function readPending(string $mergeToken): array
    {
        $pending = Cache::get(self::PREFIX.hash('sha256', $mergeToken));

        if (! is_array($pending)) {
            throw new InvalidEmailToken('Entre com o Google de novo para recomeçar.');
        }

        return $pending;
    }

    private function consume(string $mergeToken): void
    {
        Cache::forget(self::PREFIX.hash('sha256', $mergeToken));
    }

    /**
     * Grava o vínculo e abre a sessão.
     *
     * `firstOrCreate` e não `create`: se os dois caminhos forem usados, o
     * segundo encontra o vínculo já existente em vez de esbarrar no índice
     * único. O índice continua lá como última linha de defesa.
     *
     * Repare no que NÃO acontece aqui: nome e e-mail da conta permanecem
     * intactos. Um provedor externo não redefine a identidade de uma conta que
     * já existia.
     *
     * @param  array<string, mixed>  $pending
     */
    private function apply(User $account, array $pending, string $via): OpenSession
    {
        DB::transaction(function () use ($account, $pending) {
            $account->socialAccounts()->firstOrCreate(
                [
                    'provider' => $pending['provider'] ?? 'google',
                    'provider_user_id' => $pending['provider_user_id'] ?? '',
                ],
                [
                    'provider_email' => $pending['provider_email'] ?? null,
                    'linked_at' => now(),
                ],
            );
        });

        AuditLog::record(
            AuditLog::CREDENTIALS_MERGED,
            subject: $account,
            causer: $account,
            properties: [
                'provider' => $pending['provider'] ?? 'google',
                'confirmed_via' => $via,
            ],
        );

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
