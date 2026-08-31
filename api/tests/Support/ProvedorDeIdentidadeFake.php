<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Account\Email;
use App\Ports\FalhaDoProvedorDeIdentidade;
use App\Ports\IdentidadeExterna;
use App\Ports\ProvedorDeIdentidade;

/**
 * Provedor de identidade falso para os testes da US2.
 *
 * É esta classe que justifica a porta `ProvedorDeIdentidade` existir
 * (Princípio VII): o teste troca o Google por isto e exercita TODOS os desfechos
 * — inclusive os que seriam impossíveis de provocar de propósito contra o
 * provedor real (falha de rede, provedor sem devolver e-mail).
 *
 * Nenhum teste da US2 fala com o Google de verdade. Isso é intencional: teste
 * que depende de rede e de conta externa é lento, frágil e não roda em CI.
 */
final class ProvedorDeIdentidadeFake implements ProvedorDeIdentidade
{
    private ?IdentidadeExterna $identidade = null;

    private ?FalhaDoProvedorDeIdentidade $falha = null;

    /** @var list<string> */
    public array $codigosRecebidos = [];

    public function devolvendo(string $email, string $provedorUserId = 'google-123', ?string $nome = 'Maria do Google'): self
    {
        $this->identidade = new IdentidadeExterna(
            provedor: 'google',
            provedorUserId: $provedorUserId,
            email: Email::de($email),
            nome: $nome,
        );
        $this->falha = null;

        return $this;
    }

    /** Simula cancelamento, recusa, erro de rede ou provedor sem e-mail. */
    public function falhando(string $motivo = 'provedor recusou'): self
    {
        $this->falha = new FalhaDoProvedorDeIdentidade($motivo);
        $this->identidade = null;

        return $this;
    }

    public function urlDeAutorizacao(string $state): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?state='.$state;
    }

    public function identidadeDoCodigo(string $code): IdentidadeExterna
    {
        $this->codigosRecebidos[] = $code;

        if ($this->falha !== null) {
            throw $this->falha;
        }

        if ($this->identidade === null) {
            throw new FalhaDoProvedorDeIdentidade('fake sem identidade configurada');
        }

        return $this->identidade;
    }
}
