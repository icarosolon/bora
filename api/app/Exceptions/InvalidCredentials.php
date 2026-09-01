<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * E-mail ou senha não conferem (FR-006).
 *
 * A mensagem é DELIBERADAMENTE única e vaga sobre qual campo falhou. Dizer
 * "esse e-mail não existe" transformaria a tela de entrar num verificador de
 * quais e-mails têm conta no Bora.
 *
 * A oferta de recuperação vai junto porque, do ponto de vista de quem errou de
 * verdade, "não conferem" sem saída é beco sem fim (ux-requirements.md).
 */
final class InvalidCredentials extends HttpException
{
    public function __construct()
    {
        parent::__construct(
            401,
            'E-mail ou senha não conferem. Confira e tente de novo, ou use "Esqueci minha senha".'
        );
    }
}
