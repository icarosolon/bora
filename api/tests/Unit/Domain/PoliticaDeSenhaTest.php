<?php

namespace Tests\Unit\Domain;

use App\Domain\Account\PoliticaDeSenha;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Spec 001 — caso de limite declarado explicitamente: senha no comprimento
 * mínimo exato é aceita; um caractere a menos é recusada.
 */
class PoliticaDeSenhaTest extends TestCase
{
    #[Test]
    public function aceita_senha_no_minimo_exato(): void
    {
        $politica = new PoliticaDeSenha(comprimentoMinimo: 8);

        $this->assertTrue($politica->aceita('12345678'));
    }

    #[Test]
    public function recusa_um_caractere_abaixo_do_minimo(): void
    {
        $politica = new PoliticaDeSenha(comprimentoMinimo: 8);

        $this->assertFalse($politica->aceita('1234567'));
    }

    #[Test]
    public function o_minimo_e_parametro_nao_valor_fixo(): void
    {
        // Princípio VII: mudar a exigência é mudar configuração, não código.
        $this->assertTrue((new PoliticaDeSenha(4))->aceita('abcd'));
        $this->assertFalse((new PoliticaDeSenha(12))->aceita('abcdefgh'));
    }

    #[Test]
    public function conta_caracteres_e_nao_bytes(): void
    {
        // "coração" tem 7 caracteres e 9 bytes em UTF-8. Contar bytes deixaria
        // passar senha curta com acento — e reprovaria senha válida no limite.
        $politica = new PoliticaDeSenha(comprimentoMinimo: 8);

        $this->assertFalse($politica->aceita('coração'));
        $this->assertTrue($politica->aceita('coração1'));
    }

    #[Test]
    public function mensagem_de_erro_diz_o_que_fazer(): void
    {
        // ux-requirements.md: erro em linguagem humana dizendo o que corrigir.
        $mensagem = (new PoliticaDeSenha(8))->mensagemDeErro();

        $this->assertStringContainsString('8', $mensagem);
        $this->assertStringNotContainsString('inválid', mb_strtolower($mensagem));
    }
}
