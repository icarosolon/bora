<?php

use App\Http\Controllers\Spike\EventoSpikeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
|
| Princípio IV: versionamento path-based. TUDO fica sob /api/v1 — não existe
| endpoint fora do prefixo. A rota /user que o `install:api` deixou em
| /api/user foi movida para /api/v1/eu na spec 001; ela estava fora do
| versionamento e o app mobile encontraria dois padrões.
|
| Contrato: specs/001-contas-autenticacao/contracts/auth-api.md
*/

Route::prefix('v1')->group(function () {

    /*
     * ANDAIME DESCARTÁVEL — spike BORA-32 (M0). Ver EventoSpikeController.
     * Sai junto com a página /eventos do web/ na tarefa T115 da spec 001.
     */
    Route::get('/eventos', [EventoSpikeController::class, 'index']);

    Route::middleware(['auth:sanctum', 'sessao.deslizante'])->group(function () {
        // Substitui a antiga GET /api/user. Ganha o controller próprio na T061.
        Route::get('/eu', fn (Request $request) => $request->user());
    });
});
