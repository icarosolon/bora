<?php

declare(strict_types=1);

namespace App\Adapters\Email;

use App\Domain\Account\Email;
use App\Ports\EmailSender;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Message;

/**
 * Adapter da porta EmailSender sobre o Mailer do Laravel.
 *
 * O provedor concreto é escolhido por configuração (MAIL_MAILER), não por
 * código: `log` em desenvolvimento — os e-mails caem em storage/logs — e
 * `resend` em produção (decisão D8). Trocar de provedor não toca caso de uso.
 *
 * Este é o único ponto do backend que conhece o Mailer para e-mails de conta.
 */
final readonly class MailerEmailSender implements EmailSender
{
    public function __construct(private Mailer $mailer) {}

    /** @param array<string, string> $variables */
    public function send(
        Email $recipient,
        string $subject,
        string $template,
        array $variables = [],
    ): void {
        $this->mailer->send(
            $template,
            $variables,
            function (Message $message) use ($recipient, $subject) {
                $message->to($recipient->value)->subject($subject);
            }
        );
    }
}
