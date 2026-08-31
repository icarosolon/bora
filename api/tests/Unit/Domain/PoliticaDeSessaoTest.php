<?php

namespace Tests\Unit\Domain;

use App\Domain\Account\PoliticaDeSessao;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Decisão D7 — 30 dias de INATIVIDADE, renovados a cada uso.
 *
 * O "agora" entra por parâmetro, então o teste é determinístico sem precisar
 * congelar o relógio do framework.
 */
class PoliticaDeSessaoTest extends TestCase
{
    #[Test]
    public function vencimento_e_agora_mais_o_prazo_configurado(): void
    {
        $politica = new PoliticaDeSessao(validadeEmDias: 30);
        $agora = new DateTimeImmutable('2026-08-30 12:00:00');

        $this->assertSame(
            '2026-09-29 12:00:00',
            $politica->novoVencimento($agora)->format('Y-m-d H:i:s')
        );
    }

    #[Test]
    public function cada_uso_empurra_o_vencimento_para_frente(): void
    {
        // É isto que diferencia janela deslizante de prazo absoluto: usar no
        // dia 20 estende para o dia 50, em vez de manter o vencimento original.
        $politica = new PoliticaDeSessao(30);

        $primeiroUso = $politica->novoVencimento(new DateTimeImmutable('2026-08-30'));
        $usoPosterior = $politica->novoVencimento(new DateTimeImmutable('2026-09-19'));

        $this->assertGreaterThan($primeiroUso, $usoPosterior);
    }

    #[Test]
    public function expirou_quando_o_vencimento_ficou_no_passado(): void
    {
        $politica = new PoliticaDeSessao(30);
        $agora = new DateTimeImmutable('2026-08-30 12:00:00');

        $this->assertTrue($politica->expirou(new DateTimeImmutable('2026-08-30 11:59:59'), $agora));
        $this->assertFalse($politica->expirou(new DateTimeImmutable('2026-08-30 12:00:01'), $agora));
    }

    #[Test]
    public function token_sem_vencimento_nao_expira_por_tempo(): void
    {
        $this->assertFalse((new PoliticaDeSessao(30))->expirou(null));
    }

    #[Test]
    public function o_prazo_e_parametro_nao_valor_fixo(): void
    {
        $agora = new DateTimeImmutable('2026-08-30 00:00:00');

        $this->assertSame(
            '2026-09-06',
            (new PoliticaDeSessao(7))->novoVencimento($agora)->format('Y-m-d')
        );
    }
}
