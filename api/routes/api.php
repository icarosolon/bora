<?php

use App\Http\Controllers\Api\V1\Auth\ContaController;
use App\Http\Controllers\Api\V1\Auth\GoogleController;
use App\Http\Controllers\Api\V1\Auth\SenhaController;
use App\Http\Controllers\Api\V1\Auth\SessaoController;
use App\Http\Controllers\Api\V1\Auth\UniaoCredenciaisController;
use App\Http\Controllers\Api\V1\Auth\VerificacaoEmailController;
use App\Http\Controllers\Api\V1\EuController;
use App\Http\Controllers\Spike\EventoSpikeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
|
| Princípio IV: versionamento path-based. TUDO fica sob /api/v1 — não existe
| endpoint fora do prefixo. A rota /user que o `install:api` deixou em
| /api/user virou /api/v1/eu na spec 001.
|
| Contrato: specs/001-contas-autenticacao/contracts/auth-api.md
*/

Route::prefix('v1')->group(function () {

    /*
     * ANDAIME DESCARTÁVEL — spike BORA-32 (M0). Sai na T115 da spec 001.
     */
    Route::get('/eventos', [EventoSpikeController::class, 'index']);

    /*
     * Público — cadastro, entrada e confirmação por link.
     *
     * O `throttle:autenticacao` protege as duas portas que recebem senha
     * (FR-007). A verificação por link é pública de propósito: a pessoa abre o
     * e-mail com frequência noutro aparelho, sem sessão.
     */
    Route::post('/contas', [ContaController::class, 'store'])
        ->middleware('throttle:autenticacao');

    Route::post('/sessoes', [SessaoController::class, 'store'])
        ->middleware('throttle:autenticacao');

    Route::post('/email/verificar', [VerificacaoEmailController::class, 'store']);

    /*
     * Google (US2). A URL de autorização é pública — é o primeiro passo de quem
     * ainda não tem conta. A troca do `code` leva o mesmo limite das outras
     * portas de entrada.
     */
    Route::get('/auth/google/url', [GoogleController::class, 'url']);

    Route::post('/auth/google/sessoes', [GoogleController::class, 'store'])
        ->middleware('throttle:autenticacao');

    /*
     * União de credenciais (US3). Público: quem chega aqui ainda não tem sessão
     * — é o que vem obter.
     *
     * A confirmação por senha leva o MESMO limitador do login. Sem isso, a
     * união viraria a porta livre para adivinhar a senha de uma conta,
     * contornando o bloqueio da tela de entrar.
     */
    Route::post('/uniao-credenciais', [UniaoCredenciaisController::class, 'store'])
        ->middleware('throttle:autenticacao');

    Route::post('/uniao-credenciais/link', [UniaoCredenciaisController::class, 'enviarLink'])
        ->middleware('throttle:envio-de-email');

    Route::post('/uniao-credenciais/link/confirmar', [UniaoCredenciaisController::class, 'confirmarLink']);

    /*
     * Recuperação de senha (US4). Pública por necessidade: quem esqueceu a
     * senha não consegue entrar — exigir sessão aqui fecharia a única saída.
     *
     * O pedido leva o limitador de envio de e-mail: cada acerto custa um e-mail
     * de verdade, e sem limite isto vira ferramenta de varredura e de spam.
     */
    Route::post('/senha/esqueci', [SenhaController::class, 'esqueci'])
        ->middleware('throttle:envio-de-email');

    Route::post('/senha/redefinir', [SenhaController::class, 'redefinir']);

    /*
     * Autenticado — o `sessao.deslizante` renova o prazo a cada uso (D7).
     */
    Route::middleware(['auth:sanctum', 'sessao.deslizante'])->group(function () {
        Route::get('/eu', [EuController::class, 'show']);

        Route::delete('/sessoes/atual', [SessaoController::class, 'destroy']);

        // Primeira senha de conta nascida no Google (US2-5, D1 inversa).
        Route::post("/senha", [SenhaController::class, "store"]);

        Route::post('/email/verificar/reenviar', [VerificacaoEmailController::class, 'reenviar'])
            ->middleware('throttle:envio-de-email');
    });
});
