<?php

declare(strict_types=1);

namespace App\UseCases\Account;

use App\Domain\Account\PoliticaDeSessao;
use App\Domain\Account\SessaoAberta;
use App\Exceptions\CredenciaisInvalidas;
use App\Exceptions\TokenDeEmailInvalido;
use App\Models\TokenDeEmail;
use App\Models\User;
use App\Support\Auditoria;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Unir credenciais Google ↔ e-mail/senha (US3, RN-PLAT-002, decisão D1).
 *
 * É o único ponto do produto em que duas identidades se encontram — e portanto
 * o único em que uma conta paralela poderia nascer. A invariante do Princípio I
 * é dura: **em qualquer desfecho, uma conta só**.
 *
 * Dois caminhos de confirmação do titular, ambos provando posse da conta
 * existente:
 *
 * - **Senha** (principal): quem sabe a senha é dono da conta.
 * - **Link por e-mail** (plano B): quem lê o e-mail é dono da conta. Existe
 *   porque, sem ele, quem esqueceu a senha ficaria preso — entraria pelo
 *   Google, seria mandado confirmar com a senha que não lembra, e não teria
 *   saída.
 *
 * Os dois ficam válidos ao mesmo tempo de propósito: quem pede o link pode
 * lembrar a senha no meio do caminho.
 */
final readonly class UnirCredenciais
{
    private const PREFIXO = 'bora:uniao:';

    public function __construct(
        private PoliticaDeSessao $politicaDeSessao,
        private EmitirTokenDeEmail $emitirToken,
    ) {}

    /** Caminho principal: confirma com a senha da conta existente. */
    public function confirmarComSenha(string $uniaoToken, string $senha): SessaoAberta
    {
        $pendente = $this->lerPendente($uniaoToken);
        $conta = User::find($pendente['user_id']);

        if ($conta === null) {
            throw new TokenDeEmailInvalido('Entre com o Google de novo para recomeçar.');
        }

        if (! $conta->temSenha() || ! Hash::check($senha, $conta->password)) {
            // O token NÃO é consumido aqui: errar a digitação não pode obrigar
            // a pessoa a recomeçar o fluxo do Google inteiro. Quem protege
            // contra tentativa e erro é o rate limit da rota, o mesmo do login.
            throw new class extends HttpException
            {
                public function __construct()
                {
                    parent::__construct(
                        401,
                        'Senha não confere. Tente de novo ou receba um link por e-mail.'
                    );
                }
            };
        }

        $this->consumir($uniaoToken);

        return $this->efetivar($conta, $pendente, 'senha');
    }

    /** Plano B, passo 1: envia o link de confirmação para o e-mail da conta. */
    public function enviarLink(string $uniaoToken): void
    {
        $pendente = $this->lerPendente($uniaoToken);
        $conta = User::find($pendente['user_id']);

        if ($conta === null) {
            throw new TokenDeEmailInvalido('Entre com o Google de novo para recomeçar.');
        }

        $this->emitirToken->executar(
            conta: $conta,
            finalidade: TokenDeEmail::UNIAO_CREDENCIAIS,
            assunto: 'Confirme a união das suas formas de entrar no Bora',
            template: 'emails.uniao-credenciais',
            // O contexto do vínculo viaja com o token: o link precisa saber
            // QUAL identidade externa está sendo unida.
            dados: [
                'provedor' => $pendente['provedor'],
                'provedor_user_id' => $pendente['provedor_user_id'],
                'email_no_provedor' => $pendente['email_no_provedor'],
            ],
        );
    }

    /** Plano B, passo 2: confirma pelo link recebido. */
    public function confirmarComLink(string $token): SessaoAberta
    {
        $registro = $this->emitirToken->resolver($token, TokenDeEmail::UNIAO_CREDENCIAIS);

        if ($registro === null) {
            throw new TokenDeEmailInvalido('Entre com o Google de novo para recomeçar.');
        }

        $registro->forceFill(['usado_em' => now()])->save();

        return $this->efetivar($registro->user, $registro->dados ?? [], 'link');
    }

    /**
     * @return array{user_id:int, provedor:string, provedor_user_id:string, email_no_provedor:?string}
     */
    private function lerPendente(string $uniaoToken): array
    {
        $pendente = Cache::get(self::PREFIXO.hash('sha256', $uniaoToken));

        if (! is_array($pendente)) {
            throw new TokenDeEmailInvalido('Entre com o Google de novo para recomeçar.');
        }

        return $pendente;
    }

    private function consumir(string $uniaoToken): void
    {
        Cache::forget(self::PREFIXO.hash('sha256', $uniaoToken));
    }

    /**
     * Grava o vínculo e abre a sessão.
     *
     * `firstOrCreate` e não `create`: se os dois caminhos forem usados, o
     * segundo encontra o vínculo já existente em vez de esbarrar no índice
     * único. O índice continua lá como última linha de defesa.
     *
     * Repare no que NÃO acontece aqui: nome e e-mail da conta permanecem
     * intactos. Um provedor externo não redefine a identidade de uma conta que
     * já existia.
     *
     * @param  array<string, mixed>  $pendente
     */
    private function efetivar(User $conta, array $pendente, string $via): SessaoAberta
    {
        DB::transaction(function () use ($conta, $pendente) {
            $conta->contasSociais()->firstOrCreate(
                [
                    'provedor' => $pendente['provedor'] ?? 'google',
                    'provedor_user_id' => $pendente['provedor_user_id'] ?? '',
                ],
                [
                    'email_no_provedor' => $pendente['email_no_provedor'] ?? null,
                    'vinculado_em' => now(),
                ],
            );
        });

        Auditoria::registrar(
            Auditoria::CREDENCIAIS_UNIDAS,
            sobre: $conta,
            autor: $conta,
            propriedades: [
                'provedor' => $pendente['provedor'] ?? 'google',
                'confirmado_via' => $via,
            ],
        );

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
