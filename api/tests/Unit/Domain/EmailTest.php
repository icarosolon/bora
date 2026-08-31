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
    #[DataProvider('variacoesDoMesmoEmail')]
    public function normaliza_variacoes_para_o_mesmo_valor(string $entrada): void
    {
        $this->assertSame('maria@exemplo.com', Email::de($entrada)->valor);
    }

    /** @return array<string, array{string}> */
    public static function variacoesDoMesmoEmail(): array
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
    public function duas_variacoes_sao_consideradas_o_mesmo_email(): void
    {
        $a = Email::de('  Maria@Gmail.com ');
        $b = Email::de('maria@gmail.com');

        $this->assertTrue($a->equals($b), 'Variação de caixa criaria conta paralela.');
    }

    #[Test]
    #[DataProvider('emailsInvalidos')]
    public function recusa_email_invalido(string $entrada): void
    {
        $this->expectException(InvalidArgumentException::class);

        Email::de($entrada);
    }

    /** @return array<string, array{string}> */
    public static function emailsInvalidos(): array
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
    public function tentar_devolve_nulo_em_vez_de_lancar(): void
    {
        $this->assertNull(Email::tentar('sem-arroba'));
        $this->assertNotNull(Email::tentar('maria@exemplo.com'));
    }

    #[Test]
    public function nao_confunde_enderecos_de_pessoas_diferentes(): void
    {
        // Ponto e sufixo "+" são regra de UM provedor. Tratá-los como iguais
        // recusaria contas legítimas em domínios que os distinguem.
        $this->assertFalse(
            Email::de('m.aria@exemplo.com')->equals(Email::de('maria@exemplo.com'))
        );
        $this->assertFalse(
            Email::de('maria+role@exemplo.com')->equals(Email::de('maria@exemplo.com'))
        );
    }

    #[Test]
    public function limitacao_conhecida_recusa_acento_no_endereco(): void
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
        $this->assertNull(Email::tentar('maria+rolê@exemplo.com'));
        $this->assertNull(Email::tentar('maria@açúcar.com.br'));
    }
}
