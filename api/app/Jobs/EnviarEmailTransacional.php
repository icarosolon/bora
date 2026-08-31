<?php

namespace App\Jobs;

use App\Domain\Account\Email;
use App\Ports\EnviadorDeEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Envio de e-mail transacional — sempre em fila (Princípio VI).
 *
 * Nunca no ciclo da request: o tempo de resposta da API não pode depender da
 * latência do provedor de e-mail. E, pela decisão D5, falha de envio NÃO derruba
 * a operação que a originou — a conta é criada mesmo que o e-mail de verificação
 * não saia; o Job tenta de novo sozinho.
 */
class EnviarEmailTransacional implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** Espera crescente entre tentativas: 10s, 30s, 2min, 5min. */
    public array $backoff = [10, 30, 120, 300];

    /**
     * @param  array<string, string>  $variaveis  NUNCA contém senha nem token de
     *         sessão. O payload do Job fica no Redis em texto — o link de uso
     *         único é aceitável ali (ele expira e vale uma vez); credencial, não.
     */
    public function __construct(
        private readonly string $destinatario,
        private readonly string $assunto,
        private readonly string $template,
        private readonly array $variaveis = [],
    ) {}

    public function handle(EnviadorDeEmail $enviador): void
    {
        $enviador->enviar(
            Email::de($this->destinatario),
            $this->assunto,
            $this->template,
            $this->variaveis,
        );
    }

    public function failed(?Throwable $e): void
    {
        // Sem dado pessoal no log (Princípio V): registra o template e o motivo,
        // nunca o destinatário nem as variáveis (que carregam o link).
        Log::warning('Falha ao enviar e-mail transacional.', [
            'template' => $this->template,
            'motivo' => $e?->getMessage(),
        ]);
    }
}
