<?php

use App\Http\Controllers\Api\V1\Auth\ContaController;
use App\Http\Controllers\Api\V1\Auth\SessaoController;
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
     * Autenticado — o `sessao.deslizante` renova o prazo a cada uso (D7).
     */
    Route::middleware(['auth:sanctum', 'sessao.deslizante'])->group(function () {
        Route::get('/eu', [EuController::class, 'show']);

        Route::delete('/sessoes/atual', [SessaoController::class, 'destroy']);

        Route::post('/email/verificar/reenviar', [VerificacaoEmailController::class, 'reenviar'])
            ->middleware('throttle:envio-de-email');
    });
});
