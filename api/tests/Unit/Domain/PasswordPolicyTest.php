<?php

namespace Tests\Unit\Domain;

use App\Domain\Account\PasswordPolicy;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Spec 001 — caso de limite declarado explicitamente: senha no comprimento
 * mínimo exato é aceita; um caractere a menos é recusada.
 */
class PasswordPolicyTest extends TestCase
{
    #[Test]
    public function accepts_a_password_at_the_exact_minimum(): void
    {
        $policy = new PasswordPolicy(minimumLength: 8);

        $this->assertTrue($policy->accepts('12345678'));
    }

    #[Test]
    public function rejects_one_character_below_the_minimum(): void
    {
        $policy = new PasswordPolicy(minimumLength: 8);

        $this->assertFalse($policy->accepts('1234567'));
    }

    #[Test]
    public function the_minimum_is_a_parameter_not_a_fixed_value(): void
    {
        // Princípio VII: mudar a exigência é mudar configuração, não código.
        $this->assertTrue((new PasswordPolicy(4))->accepts('abcd'));
        $this->assertFalse((new PasswordPolicy(12))->accepts('abcdefgh'));
    }

    #[Test]
    public function counts_characters_not_bytes(): void
    {
        // "coração" tem 7 caracteres e 9 bytes em UTF-8. Contar bytes deixaria
        // passar senha curta com acento — e reprovaria senha válida no limite.
        $policy = new PasswordPolicy(minimumLength: 8);

        $this->assertFalse($policy->accepts('coração'));
        $this->assertTrue($policy->accepts('coração1'));
    }

    #[Test]
    public function the_error_message_says_what_to_do(): void
    {
        // ux-requirements.md: erro em linguagem humana dizendo o que corrigir.
        $message = (new PasswordPolicy(8))->errorMessage();

        $this->assertStringContainsString('8', $message);
        $this->assertStringNotContainsString('inválid', mb_strtolower($message));
    }
}
