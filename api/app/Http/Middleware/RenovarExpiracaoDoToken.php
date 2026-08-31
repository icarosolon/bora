<?php

namespace App\Http\Middleware;

use App\Domain\Account\PoliticaDeSessao;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Janela deslizante da sessão — decisão D7.
 *
 * O Sanctum NÃO tem isso. Sua opção `expiration` (config/sanctum.php) é um
 * prazo absoluto contado da criação do token: com ela, quem usa o app todo dia
 * seria deslogado no 30º dia mesmo assim. A D7 pediu 30 dias de INATIVIDADE.
 *
 * Então: a cada request autenticada, empurramos `expires_at` para agora + prazo.
 * Por isso `expiration` precisa continuar `null` — se receber valor, ele
 * sobrepõe o `expires_at` por token e este middleware vira decoração.
 *
 * Ver: specs/001-contas-autenticacao/research.md §1
 */
class RenovarExpiracaoDoToken
{
    public function __construct(private readonly PoliticaDeSessao $politica) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $novoVencimento = $this->politica->novoVencimento();

            // Grava direto, sem tocar em `updated_at` nem disparar eventos: isto
            // roda em TODA request autenticada e não pode custar caro nem poluir
            // a auditoria com ruído de renovação.
            PersonalAccessToken::withoutTimestamps(
                fn () => $token->forceFill(['expires_at' => $novoVencimento])->saveQuietly()
            );

            $request->user()->forceFill(['ultimo_acesso_em' => now()])->saveQuietly();
        }

        return $next($request);
    }
}
