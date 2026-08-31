<?php

declare(strict_types=1);

namespace App\Ports;

use RuntimeException;

/**
 * O provedor externo não concluiu a autenticação.
 *
 * Cobre os quatro desfechos que a US2-3 trata igual: a pessoa cancelou, o
 * provedor recusou, houve falha de rede, ou o provedor não devolveu e-mail.
 * Em todos, a resposta é a mesma mensagem humana e NENHUMA conta é criada —
 * por isso um tipo só, e não quatro.
 */
final class FalhaDoProvedorDeIdentidade extends RuntimeException {}
