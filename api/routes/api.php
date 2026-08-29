<?php

use App\Http\Controllers\Spike\EventoSpikeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
 * ANDAIME DESCARTÁVEL — spike BORA-32 (M0). Ver EventoSpikeController.
 * Some junto com a página /eventos do web/ quando o spike for encerrado.
 */
Route::prefix('v1')->group(function () {
    Route::get('/eventos', [EventoSpikeController::class, 'index']);
});
