<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Support\AuditLog;
use Illuminate\Console\Command;

/**
 * Concede o papel de operacao da plataforma a uma conta, pelo e-mail.
 *
 * Por que comando e nao tela (spec 002, FR-027): conceder papel de operacao e
 * ato de plataforma, nao funcionalidade de usuario — o Principio XI cobra tela
 * para feature de produto, e esta nao e uma. A tela de gestao de papeis e
 * assunto da spec de operacao, quando houver.
 *
 * Por que comando e nao concessao a mao no banco: a T037 roda
 * `migrate:fresh --seed`, que apaga a concessao. Um comando idempotente
 * sobrevive a recriacao do esquema e deixa rastro; um UPDATE no banco nao faz
 * nem uma coisa nem outra.
 */
final class GrantOperatorRole extends Command
{
    protected $signature = 'bora:grant-operator {email : E-mail da conta que vai operar a plataforma}';

    protected $description = 'Concede o papel de operacao da plataforma a uma conta';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $role = (string) config('bora.account.operation_role');

        $account = User::where('email', $email)->first();

        if ($account === null) {
            // Mensagem de tela e prosa: portugues do dia a dia, dizendo o que
            // fazer (ux-requirements.md). Sem efeito colateral nenhum.
            $this->error("Nao existe conta com o e-mail {$email}. Confira o endereco ou crie a conta primeiro.");

            return self::FAILURE;
        }

        if ($account->hasRole($role)) {
            $this->info("A conta {$email} ja tem o papel de operacao. Nada a fazer.");

            return self::SUCCESS;
        }

        $account->assignRole($role);

        /*
         * Principio VIII: a concessao e escrita e precisa de rastro. O autor
         * fica NULO de proposito — a concessao vem do terminal, onde nao ha
         * sessao, e o proprio AuditLog manda registrar acao sem autor como tal
         * em vez de atribui-la a alguem errado. O `origin` diz por onde veio.
         */
        AuditLog::record(
            AuditLog::OPERATOR_ROLE_GRANTED,
            subject: $account,
            properties: ['origin' => 'console', 'role' => $role],
        );

        $this->info("Papel de operacao concedido a {$email}.");

        return self::SUCCESS;
    }
}
