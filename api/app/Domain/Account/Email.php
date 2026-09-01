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
    private function __construct(public string $value) {}

    public static function from(string $input): self
    {
        $normalized = self::normalize($input);

        if (! self::looksValid($normalized)) {
            throw new InvalidArgumentException('E-mail inválido.');
        }

        return new self($normalized);
    }

    /** Tenta criar; devolve null em vez de lançar. Útil em borda de validação. */
    public static function tryFrom(string $input): ?self
    {
        try {
            return self::from($input);
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
    public static function normalize(string $input): string
    {
        return mb_strtolower(trim($input));
    }

    private static function looksValid(string $value): bool
    {
        return $value !== ''
            && mb_strlen($value) <= 255
            && filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
