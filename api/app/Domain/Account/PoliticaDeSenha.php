<?php

declare(strict_types=1);

namespace App\Domain\Account;

/**
 * Regra de senha aceitável.
 *
 * Princípio VII: a POLÍTICA vive aqui; o PARÂMETRO (o número) entra pelo
 * construtor e vem de config/bora.php, amarrado no AppServiceProvider. Assim
 * mudar o mínimo é mudar configuração, não código — e esta classe se testa sem
 * subir o framework.
 */
final readonly class PoliticaDeSenha
{
    public function __construct(public int $comprimentoMinimo) {}

    public function aceita(string $senha): bool
    {
        return mb_strlen($senha) >= $this->comprimentoMinimo;
    }

    /** Mensagem em linguagem humana, dizendo o que fazer (ux-requirements.md). */
    public function mensagemDeErro(): string
    {
        return "A senha precisa de pelo menos {$this->comprimentoMinimo} caracteres.";
    }
}
