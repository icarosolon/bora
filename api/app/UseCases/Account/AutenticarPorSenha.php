<?php

declare(strict_types=1);

namespace App\UseCases\Account;

use App\Domain\Account\Email;
use App\Domain\Account\PoliticaDeSessao;
use App\Domain\Account\SessaoAberta;
use App\Exceptions\ContaEntraPorProvedor;
use App\Exceptions\CredenciaisInvalidas;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Entrar com e-mail e senha (US1-2, US1-6).
 */
final readonly class AutenticarPorSenha
{
    public function __construct(private PoliticaDeSessao $politicaDeSessao) {}

    public function executar(
        Email $email,
        string $senha,
        ?string $dispositivo = null,
    ): SessaoAberta {
        $conta = User::where('email', $email->valor)->first();

        if ($conta === null) {
            // Mesma exceção de senha errada: a resposta não pode diferenciar
            // "e-mail não existe" de "senha errada" (FR-006).
            //
            // O Hash::check em string fixa existe para gastar tempo parecido com
            // o do caminho válido — sem isso, a diferença de latência entregaria
            // quais e-mails têm conta, que é justamente o que a mensagem única
            // tenta esconder.
            Hash::check($senha, '$2y$12$'.str_repeat('x', 53));

            throw new CredenciaisInvalidas;
        }

        if (! $conta->temSenha()) {
            $provedor = $conta->contasSociais()->value('provedor') ?? 'google';

            throw new ContaEntraPorProvedor($provedor);
        }

        if (! Hash::check($senha, $conta->password)) {
            throw new CredenciaisInvalidas;
        }

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
