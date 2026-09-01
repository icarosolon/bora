<?php

namespace Tests\Unit\Domain;

use App\Domain\Account\SessionPolicy;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Decisão D7 — 30 dias de INATIVIDADE, renovados a cada uso.
 *
 * O "agora" entra por parâmetro, então o teste é determinístico sem precisar
 * congelar o relógio do framework.
 */
class SessionPolicyTest extends TestCase
{
    #[Test]
    public function the_expiry_is_now_plus_the_configured_period(): void
    {
        $policy = new SessionPolicy(lifetimeInDays: 30);
        $now = new DateTimeImmutable('2026-08-30 12:00:00');

        $this->assertSame(
            '2026-09-29 12:00:00',
            $policy->newExpiry($now)->format('Y-m-d H:i:s')
        );
    }

    #[Test]
    public function each_use_pushes_the_expiry_forward(): void
    {
        // É isto que diferencia janela deslizante de prazo absoluto: usar no
        // dia 20 estende para o dia 50, em vez de manter o vencimento original.
        $policy = new SessionPolicy(30);

        $firstUse = $policy->newExpiry(new DateTimeImmutable('2026-08-30'));
        $laterUse = $policy->newExpiry(new DateTimeImmutable('2026-09-19'));

        $this->assertGreaterThan($firstUse, $laterUse);
    }

    #[Test]
    public function expired_when_the_expiry_is_in_the_past(): void
    {
        $policy = new SessionPolicy(30);
        $now = new DateTimeImmutable('2026-08-30 12:00:00');

        $this->assertTrue($policy->hasExpired(new DateTimeImmutable('2026-08-30 11:59:59'), $now));
        $this->assertFalse($policy->hasExpired(new DateTimeImmutable('2026-08-30 12:00:01'), $now));
    }

    #[Test]
    public function a_token_without_expiry_does_not_expire_by_time(): void
    {
        $this->assertFalse((new SessionPolicy(30))->hasExpired(null));
    }

    #[Test]
    public function the_period_is_a_parameter_not_a_fixed_value(): void
    {
        $now = new DateTimeImmutable('2026-08-30 00:00:00');

        $this->assertSame(
            '2026-09-06',
            (new SessionPolicy(7))->newExpiry($now)->format('Y-m-d')
        );
    }
}
