<?php

namespace Tests\Unit\Domain;

use App\Domain\Account\Email;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * RN-PLAT-001 / Princípio I — a normalização do e-mail é a primeira linha de
 * defesa da conta única. Se ela falhar, dá para criar duas contas para a mesma
 * pessoa só mudando a caixa das letras.
 *
 * Herda de PHPUnit\TestCase, não do TestCase do Laravel: o núcleo é testável
 * sem subir o framework (Princípio VII).
 */
class EmailTest extends TestCase
{
    #[Test]
    #[DataProvider('variationsOfTheSameEmail')]
    public function normalizes_variations_to_the_same_value(string $input): void
    {
        $this->assertSame('maria@exemplo.com', Email::from($input)->value);
    }

    /** @return array<string, array{string}> */
    public static function variationsOfTheSameEmail(): array
    {
        return [
            'já normalizado' => ['maria@exemplo.com'],
            'maiúsculas' => ['MARIA@EXEMPLO.COM'],
            'caixa mista' => ['Maria@Exemplo.Com'],
            'espaços nas pontas' => ['  maria@exemplo.com  '],
            'espaços e maiúsculas' => ['  Maria@Exemplo.COM '],
            'tabulação e quebra' => ["\tmaria@exemplo.com\n"],
        ];
    }

    #[Test]
    public function two_variations_are_considered_the_same_email(): void
    {
        $a = Email::from('  Maria@Gmail.com ');
        $b = Email::from('maria@gmail.com');

        $this->assertTrue($a->equals($b), 'Variação de caixa criaria conta paralela.');
    }

    #[Test]
    #[DataProvider('invalidEmails')]
    public function rejects_an_invalid_email(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        Email::from($input);
    }

    /** @return array<string, array{string}> */
    public static function invalidEmails(): array
    {
        return [
            'vazio' => [''],
            'só espaços' => ['   '],
            'sem arroba' => ['maria.exemplo.com'],
            'sem domínio' => ['maria@'],
            'sem parte local' => ['@exemplo.com'],
            'com espaço no meio' => ['maria silva@exemplo.com'],
            'dois arrobas' => ['maria@@exemplo.com'],
            'longo demais' => [str_repeat('a', 250).'@exemplo.com'],
        ];
    }

    #[Test]
    public function try_from_returns_null_instead_of_throwing(): void
    {
        $this->assertNull(Email::tryFrom('sem-arroba'));
        $this->assertNotNull(Email::tryFrom('maria@exemplo.com'));
    }

    #[Test]
    public function does_not_confuse_addresses_of_different_people(): void
    {
        // Ponto e sufixo "+" são regra de UM provedor. Tratá-los como iguais
        // recusaria contas legítimas em domínios que os distinguem.
        $this->assertFalse(
            Email::from('m.aria@exemplo.com')->equals(Email::from('maria@exemplo.com'))
        );
        $this->assertFalse(
            Email::from('maria+role@exemplo.com')->equals(Email::from('maria@exemplo.com'))
        );
    }

    #[Test]
    public function known_limitation_rejects_an_accent_in_the_address(): void
    {
        // Registro deliberado de uma limitação, não de um comportamento desejado.
        // filter_var() não aceita caractere não-ASCII na parte local nem domínio
        // internacionalizado sem punycode. Na prática isso não atinge o público
        // do Bora — Gmail, Outlook e os provedores brasileiros comuns não emitem
        // endereço com acento —, então fica assim de propósito.
        //
        // Se algum dia aparecer usuário real reprovado por isto, o lugar de
        // corrigir é aqui (idn_to_ascii antes de validar), e vira decisão do
        // Ícaro, não do implementador.
        $this->assertNull(Email::tryFrom('maria+rolê@exemplo.com'));
        $this->assertNull(Email::tryFrom('maria@açúcar.com.br'));
    }
}
