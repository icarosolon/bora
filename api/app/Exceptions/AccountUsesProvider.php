<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * A conta existe, mas não tem senha — ela entra por um provedor externo.
 *
 * Exceção à regra da mensagem única (ver InvalidCredentials), e de propósito:
 * repetir "e-mail ou senha não conferem" para quem nunca teve senha deixaria a
 * pessoa tentando adivinhar algo que não existe. O que se revela aqui é o
 * MÉTODO de entrada, não a existência da conta — e só depois de a pessoa já ter
 * afirmado conhecer aquele e-mail.
 */
final class AccountUsesProvider extends HttpException
{
    public function __construct(string $provider = 'google')
    {
        $name = match ($provider) {
            'google' => 'Google',
            default => ucfirst($provider),
        };

        parent::__construct(
            401,
            "Esta conta entra com o {$name}. Toque em \"Entrar com {$name}\"."
        );
    }
}
