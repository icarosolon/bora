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
final readonly class PasswordPolicy
{
    public function __construct(public int $minimumLength) {}

    public function accepts(string $password): bool
    {
        return mb_strlen($password) >= $this->minimumLength;
    }

    /** Mensagem em linguagem humana, dizendo o que fazer (ux-requirements.md). */
    public function errorMessage(): string
    {
        return "A senha precisa de pelo menos {$this->minimumLength} caracteres.";
    }
}
