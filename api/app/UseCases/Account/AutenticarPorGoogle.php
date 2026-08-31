<?php

declare(strict_types=1);

namespace App\UseCases\Account;

use App\Domain\Account\PoliticaDeSessao;
use App\Domain\Account\SessaoAberta;
use App\Domain\Account\UniaoPendente;
use App\Models\ContaSocial;
use App\Models\User;
use App\Ports\IdentidadeExterna;
use App\Ports\ProvedorDeIdentidade;
use App\Support\Auditoria;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Entrar com Google (US2, RN-PLAT-002).
 *
 * Três desfechos possíveis, e a decisão entre eles é a regra desta feature:
 *
 * 1. **Já existe vínculo** com aquele identificador do provedor → entra na
 *    mesma conta. Casa pelo `provedor_user_id`, não pelo e-mail: quem troca o
 *    e-mail no Google não perde a conta nem ganha uma segunda.
 * 2. **E-mail inédito** → cria a conta única, já com e-mail verificado (o
 *    Google verificou) e papel de rolezeiro.
 * 3. **E-mail já tem conta** (criada por e-mail/senha) → **não cria nada** e
 *    devolve união pendente. Unir é a US3; aqui só se constata (Princípio I).
 */
final readonly class AutenticarPorGoogle
{
    private const PREFIXO_STATE = 'bora:oauth:state:';

    public function __construct(
        private ProvedorDeIdentidade $provedor,
        private PoliticaDeSessao $politicaDeSessao,
    ) {}

    /**
     * Passo 1 — a URL para onde o navegador deve ir.
     *
     * @return array{url: string, state: string}
     */
    public function iniciar(): array
    {
        $state = bin2hex(random_bytes(24));

        // O `state` vive no cache, não em sessão: a API é sem estado. O prazo
        // curto limita a janela em que um valor capturado teria serventia.
        Cache::put(
            self::PREFIXO_STATE.$state,
            true,
            now()->addMinutes((int) config('bora.uniao.validade_minutos'))
        );

        return [
            'url' => $this->provedor->urlDeAutorizacao($state),
            'state' => $state,
        ];
    }

    /**
     * Passo 2 — troca o `code` por sessão.
     *
     * @throws \Illuminate\Validation\ValidationException quando o `state` não confere
     * @throws \App\Ports\FalhaDoProvedorDeIdentidade
     */
    public function concluir(string $code, string $state): SessaoAberta|UniaoPendente
    {
        $this->consumirState($state);

        $identidade = $this->provedor->identidadeDoCodigo($code);

        $vinculo = ContaSocial::where('provedor', $identidade->provedor)
            ->where('provedor_user_id', $identidade->provedorUserId)
            ->first();

        if ($vinculo !== null) {
            return $this->abrirSessao($vinculo->user);
        }

        $contaExistente = User::where('email', $identidade->email->valor)->first();

        if ($contaExistente !== null) {
            return $this->uniaoPendente($contaExistente, $identidade);
        }

        return $this->abrirSessao($this->criarConta($identidade));
    }

    /**
     * Uso único: um `state` capturado não pode servir duas vezes. É consumido
     * ANTES de falar com o provedor — se sobrevivesse a uma tentativa
     * malsucedida, deixaria de ser proteção.
     */
    private function consumirState(string $state): void
    {
        $chave = self::PREFIXO_STATE.$state;

        if (! Cache::pull($chave)) {
            \App\Exceptions\StateInvalido::lancar();
        }
    }

    private function criarConta(IdentidadeExterna $identidade): User
    {
        $conta = DB::transaction(function () use ($identidade) {
            $conta = User::create([
                'name' => $identidade->nome ?: (string) $identidade->email,
                'email' => $identidade->email->valor,
                // Sem senha: a pessoa pode definir uma depois, autenticada (D1).
                'password' => null,
            ]);

            // `email_verified_at` fica FORA do `#[Fillable]` de propósito
            // (Princípio V): se fosse atribuível em massa, um payload de
            // cadastro poderia marcar a própria conta como verificada. Aqui a
            // marcação é deliberada e confiável — o Google já verificou este
            // e-mail, e pedir confirmação de novo seria fricção sem ganho.
            $conta->forceFill(['email_verified_at' => now()])->save();

            $conta->assignRole(config('bora.conta.papel_inicial'));

            $conta->contasSociais()->create([
                'provedor' => $identidade->provedor,
                'provedor_user_id' => $identidade->provedorUserId,
                'email_no_provedor' => $identidade->email->valor,
                'vinculado_em' => now(),
            ]);

            return $conta;
        });

        Auditoria::registrar(
            Auditoria::CONTA_CRIADA,
            sobre: $conta,
            autor: $conta,
            propriedades: ['origem' => 'cadastro_google'],
        );

        return $conta;
    }

    private function uniaoPendente(User $conta, IdentidadeExterna $identidade): UniaoPendente
    {
        $token = bin2hex(random_bytes(32));
        $expiraEm = now()->addMinutes((int) config('bora.uniao.validade_minutos'));

        // Guardado no cache, não no banco: é efêmero e nada foi decidido ainda.
        // Só o hash — o valor em claro existe no caminho de volta da request.
        Cache::put(
            'bora:uniao:'.hash('sha256', $token),
            [
                'user_id' => $conta->id,
                'provedor' => $identidade->provedor,
                'provedor_user_id' => $identidade->provedorUserId,
                'email_no_provedor' => $identidade->email->valor,
            ],
            $expiraEm,
        );

        return new UniaoPendente(
            email: $identidade->email,
            token: $token,
            expiraEm: $expiraEm,
        );
    }

    private function abrirSessao(User $conta): SessaoAberta
    {
        $expiraEm = $this->politicaDeSessao->novoVencimento();

        $token = $conta->createToken('navegador', ['*'], $expiraEm);

        $conta->forceFill(['ultimo_acesso_em' => now()])->saveQuietly();

        return new SessaoAberta(
            conta: $conta->fresh(['roles', 'contasSociais']),
            tokenEmClaro: $token->plainTextToken,
            expiraEm: $expiraEm,
        );
    }
}
