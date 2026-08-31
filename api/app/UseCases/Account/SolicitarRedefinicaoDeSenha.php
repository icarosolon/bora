<?php

declare(strict_types=1);

namespace App\UseCases\Account;

use App\Domain\Account\Email;
use App\Jobs\EnviarEmailTransacional;
use App\Models\TokenDeEmail;
use App\Models\User;

/**
 * "Esqueci minha senha", passo 1 (US4, decisão D6).
 *
 * A regra que manda aqui é a **não-enumeração**: quem chama este caso de uso
 * nunca descobre se a conta existe. O método não devolve nada e não lança nada
 * — todos os caminhos terminam em silêncio, e a borda responde sempre a mesma
 * mensagem condicional.
 *
 * Três desfechos internos, invisíveis de fora:
 *
 * 1. **Conta com senha** → link de redefinição.
 * 2. **Conta só do Google** → e-mail explicando que ali não há senha para
 *    redefinir. Sem isso, a pessoa ficaria esperando um link que nunca vem.
 * 3. **Não existe conta** → nada. Mandar e-mail para quem não se cadastrou
 *    seria spam saindo do nosso domínio.
 */
final readonly class SolicitarRedefinicaoDeSenha
{
    public function __construct(private EmitirTokenDeEmail $emitirToken) {}

    public function executar(Email $email): void
    {
        $conta = User::where('email', $email->valor)->first();

        if ($conta === null) {
            return;
        }

        if (! $conta->temSenha()) {
            $this->avisarQueEntraPeloProvedor($conta);

            return;
        }

        $this->emitirToken->executar(
            conta: $conta,
            finalidade: TokenDeEmail::REDEFINICAO_SENHA,
            assunto: 'Redefinir sua senha do Bora',
            template: 'emails.redefinicao-senha',
        );
    }

    /**
     * Não passa pelo emissor de token de propósito: não há token nenhum a
     * emitir, e criar um seria criar uma credencial sem uso.
     */
    private function avisarQueEntraPeloProvedor(User $conta): void
    {
        $provedor = $conta->contasSociais()->value('provedor') ?? 'google';

        EnviarEmailTransacional::dispatch(
            $conta->email,
            'Sobre a sua conta no Bora',
            'emails.senha-conta-google',
            [
                'nome' => $conta->name,
                'provedor' => $provedor === 'google' ? 'Google' : ucfirst($provedor),
                'url' => rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/').'/entrar',
            ],
        );
    }
}
