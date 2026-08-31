<?php

declare(strict_types=1);

namespace App\Adapters\Email;

use App\Domain\Account\Email;
use App\Ports\EnviadorDeEmail;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Message;

/**
 * Adapter da porta EnviadorDeEmail sobre o Mailer do Laravel.
 *
 * O provedor concreto é escolhido por configuração (MAIL_MAILER), não por
 * código: `log` em desenvolvimento — os e-mails caem em storage/logs — e
 * `resend` em produção (decisão D8). Trocar de provedor não toca caso de uso.
 *
 * Este é o único ponto do backend que conhece o Mailer para e-mails de conta.
 */
final readonly class MailerEnviadorDeEmail implements EnviadorDeEmail
{
    public function __construct(private Mailer $mailer) {}

    /** @param array<string, string> $variaveis */
    public function enviar(
        Email $destinatario,
        string $assunto,
        string $template,
        array $variaveis = [],
    ): void {
        $this->mailer->send(
            $template,
            $variaveis,
            function (Message $mensagem) use ($destinatario, $assunto) {
                $mensagem->to($destinatario->valor)->subject($assunto);
            }
        );
    }
}
