<?php

declare(strict_types=1);

namespace App\UseCases\Account;

use App\Domain\Account\Email;
use App\Domain\Account\PoliticaDeSessao;
use App\Domain\Account\SessaoAberta;
use App\Models\TokenDeEmail;
use App\Models\User;
use App\Support\Auditoria;
use Illuminate\Support\Facades\DB;

/**
 * Criar conta com e-mail e senha (US1, RN-PLAT-001).
 *
 * Invariantes que este caso de uso sustenta:
 *
 * - **Uma conta por e-mail normalizado.** A checagem de duplicata acontece na
 *   validação da borda (regra EmailNaoCadastrado), e o índice único do banco é
 *   a última linha de defesa. Aqui a garantia é a TRANSAÇÃO: conta, papel e
 *   token de verificação nascem juntos ou não nascem.
 * - **A conta nasce utilizável** (Princípio II): papel de rolezeiro, sem plano,
 *   sem paywall, sem etapa de pagamento.
 * - **Verificação não bloqueia** (D5): o e-mail sai por Job; se o envio falhar,
 *   a conta continua de pé.
 */
final readonly class RegistrarConta
{
    public function __construct(
        private PoliticaDeSessao $politicaDeSessao,
        private EmitirTokenDeEmail $emitirToken,
    ) {}

    public function executar(
        string $nome,
        Email $email,
        string $senha,
        ?string $dispositivo = null,
    ): SessaoAberta {
        $conta = DB::transaction(function () use ($nome, $email, $senha) {
            $conta = User::create([
                'name' => $nome,
                'email' => $email->valor,
                'password' => $senha,
            ]);

            $conta->assignRole(config('bora.conta.papel_inicial'));

            return $conta;
        });

        // Fora da transação: enfileirar e-mail dentro dela correria o risco de
        // o Job rodar antes do commit e não encontrar a conta.
        $this->emitirToken->executar(
            conta: $conta,
            finalidade: TokenDeEmail::VERIFICACAO_EMAIL,
            assunto: 'Confirme seu e-mail no Bora',
            template: 'emails.verificacao',
        );

        Auditoria::registrar(
            Auditoria::CONTA_CRIADA,
            sobre: $conta,
            autor: $conta,
            propriedades: ['origem' => 'cadastro_email_senha'],
        );

        $expiraEm = $this->politicaDeSessao->novoVencimento();

        $token = $conta->createToken($dispositivo ?? 'navegador', ['*'], $expiraEm);

        $conta->forceFill(['ultimo_acesso_em' => now()])->saveQuietly();

        return new SessaoAberta(
            conta: $conta->fresh(['roles', 'contasSociais']),
            tokenEmClaro: $token->plainTextToken,
            expiraEm: $expiraEm,
        );
    }
}
