<?php

declare(strict_types=1);

namespace App\Ports;

use App\Domain\Account\Email;

/**
 * Porta para e-mail transacional (Princípio VII — portas & adapters).
 *
 * O domínio pede "envie este link para esta pessoa"; quem conhece Resend, SMTP
 * ou o log de desenvolvimento é o adapter em app/Adapters/Email/. Trocar de
 * provedor (decisão D8) não pode tocar caso de uso.
 *
 * O envio real acontece em Job de fila (Princípio VI) — nunca no ciclo da
 * request. Falha de envio não derruba a operação que a originou (D5).
 */
interface EmailSender
{
    /**
     * @param  array<string, string>  $variables  dados do template. NUNCA inclui
     *         senha, hash de senha ou token de sessão (Princípio V).
     */
    public function send(
        Email $recipient,
        string $subject,
        string $template,
        array $variables = [],
    ): void;
}
