<?php

declare(strict_types=1);

namespace App\Domain\Account;

use InvalidArgumentException;

/**
 * O e-mail de uma conta, sempre normalizado.
 *
 * Esta classe é o que sustenta a invariante do Princípio I contra variação de
 * escrita: "  Maria@Gmail.com " e "maria@gmail.com" são a MESMA conta. O índice
 * único no banco é a última linha de defesa; a normalização aqui é a primeira,
 * e precisa acontecer antes de qualquer consulta ou gravação.
 *
 * Núcleo de domínio: não importa nada do framework (Princípio VII).
 */
final readonly class Email
{
    private function __construct(public string $valor) {}

    public static function de(string $entrada): self
    {
        $normalizado = self::normalizar($entrada);

        if (! self::pareceValido($normalizado)) {
            throw new InvalidArgumentException('E-mail inválido.');
        }

        return new self($normalizado);
    }

    /** Tenta criar; devolve null em vez de lançar. Útil em borda de validação. */
    public static function tentar(string $entrada): ?self
    {
        try {
            return self::de($entrada);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Minúsculas e sem espaços nas pontas.
     *
     * Deliberadamente NÃO se mexe em pontos nem em sufixos "+algo" do Gmail:
     * tratar "m.a.r.i.a@gmail.com" como "maria@gmail.com" é regra de UM provedor,
     * e aplicá-la a todos recusaria contas legítimas em domínios que distinguem.
     */
    public static function normalizar(string $entrada): string
    {
        return mb_strtolower(trim($entrada));
    }

    private static function pareceValido(string $valor): bool
    {
        return $valor !== ''
            && mb_strlen($valor) <= 255
            && filter_var($valor, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function equals(self $outro): bool
    {
        return $this->valor === $outro->valor;
    }

    public function __toString(): string
    {
        return $this->valor;
    }
}
