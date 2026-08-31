<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Link de e-mail expirado, já usado ou inexistente — 410 Gone.
 *
 * Os três casos dão a MESMA resposta de propósito: distinguir "expirou" de
 * "não existe" diria a um curioso quais tokens já existiram. E, para quem
 * clicou de boa-fé, o próximo passo é o mesmo — pedir um link novo.
 *
 * 410 e não 404: o recurso existiu (ou poderia ter existido) e não vale mais.
 * É o que permite à tela distinguir "link velho" de "endereço errado".
 */
final class TokenDeEmailInvalido extends HttpException
{
    public function __construct(string $acao = 'Peça um novo link para continuar.')
    {
        parent::__construct(410, "Este link expirou ou já foi usado. {$acao}");
    }
}
