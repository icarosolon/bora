<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * A conta existe, mas não tem senha — ela entra por um provedor externo.
 *
 * Exceção à regra da mensagem única (ver CredenciaisInvalidas), e de propósito:
 * repetir "e-mail ou senha não conferem" para quem nunca teve senha deixaria a
 * pessoa tentando adivinhar algo que não existe. O que se revela aqui é o
 * MÉTODO de entrada, não a existência da conta — e só depois de a pessoa já ter
 * afirmado conhecer aquele e-mail.
 */
final class ContaEntraPorProvedor extends HttpException
{
    public function __construct(string $provedor = 'google')
    {
        $nome = match ($provedor) {
            'google' => 'Google',
            default => ucfirst($provedor),
        };

        parent::__construct(
            401,
            "Esta conta entra com o {$nome}. Toque em \"Entrar com {$nome}\"."
        );
    }
}
