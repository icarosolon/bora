<?php

declare(strict_types=1);

namespace App\UseCases\Account;

use App\Jobs\EnviarEmailTransacional;
use App\Models\TokenDeEmail;
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
final class EmitirTokenDeEmail
{
    /** @param array<string, mixed> $dados */
    public function executar(
        User $conta,
        string $finalidade,
        string $assunto,
        string $template,
        array $dados = [],
        array $variaveisExtras = [],
    ): string {
        // Invalida os anteriores desta finalidade.
        $conta->tokensDeEmail()
            ->where('finalidade', $finalidade)
            ->whereNull('usado_em')
            ->update(['usado_em' => now()]);

        $emClaro = bin2hex(random_bytes(32));

        $conta->tokensDeEmail()->create([
            'finalidade' => $finalidade,
            'token_hash' => hash('sha256', $emClaro),
            'expira_em' => now()->addMinutes($this->validadeEmMinutos($finalidade)),
            'dados' => $dados ?: null,
        ]);

        EnviarEmailTransacional::dispatch(
            $conta->email,
            $assunto,
            $template,
            [
                'nome' => $conta->name,
                'token' => $emClaro,
                'url' => $this->urlDoFrontend($finalidade, $emClaro),
                ...$variaveisExtras,
            ],
        );

        return $emClaro;
    }

    /**
     * Resolve o token em claro para o registro correspondente.
     *
     * Compara pelo hash: o valor em claro nunca é procurado no banco porque
     * nunca está lá.
     */
    public function resolver(string $emClaro, string $finalidade): ?TokenDeEmail
    {
        return TokenDeEmail::query()
            ->where('token_hash', hash('sha256', $emClaro))
            ->where('finalidade', $finalidade)
            ->valido()
            ->first();
    }

    private function validadeEmMinutos(string $finalidade): int
    {
        return (int) config("bora.tokens_de_email.{$finalidade}", 60);
    }

    /**
     * O link aponta para o `web/`, não para a API: quem tem tela é o front
     * (Princípio XI). A API só emite o token.
     */
    private function urlDoFrontend(string $finalidade, string $token): string
    {
        $base = rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/');

        $caminho = match ($finalidade) {
            TokenDeEmail::VERIFICACAO_EMAIL => '/verificar-email',
            TokenDeEmail::REDEFINICAO_SENHA => '/redefinir-senha',
            TokenDeEmail::UNIAO_CREDENCIAIS => '/unir-contas/confirmar',
            default => '/',
        };

        return "{$base}{$caminho}?token={$token}";
    }
}
