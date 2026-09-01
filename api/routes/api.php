<?php

use App\Http\Controllers\Api\V1\Auth\AccountController;
use App\Http\Controllers\Api\V1\Auth\GoogleController;
use App\Http\Controllers\Api\V1\Auth\PasswordController;
use App\Http\Controllers\Api\V1\Auth\SessionController;
use App\Http\Controllers\Api\V1\Auth\CredentialMergeController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\MeController;
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
     * Público — cadastro, entrada e confirmação por link.
     *
     * O `throttle:authentication` protege as duas portas que recebem senha
     * (FR-007). A verificação por link é pública de propósito: a pessoa abre o
     * e-mail com frequência noutro aparelho, sem sessão.
     */
    Route::post('/contas', [AccountController::class, 'store'])
        ->middleware('throttle:authentication');

    Route::post('/sessoes', [SessionController::class, 'store'])
        ->middleware('throttle:authentication');

    Route::post('/email/verificar', [EmailVerificationController::class, 'store']);

    /*
     * Google (US2). A URL de autorização é pública — é o primeiro passo de quem
     * ainda não tem conta. A troca do `code` leva o mesmo limite das outras
     * portas de entrada.
     */
    Route::get('/auth/google/url', [GoogleController::class, 'url']);

    Route::post('/auth/google/sessoes', [GoogleController::class, 'store'])
        ->middleware('throttle:authentication');

    /*
     * União de credenciais (US3). Público: quem chega aqui ainda não tem sessão
     * — é o que vem obter.
     *
     * A confirmação por senha leva o MESMO limitador do login. Sem isso, a
     * união viraria a porta livre para adivinhar a senha de uma conta,
     * contornando o bloqueio da tela de entrar.
     */
    Route::post('/uniao-credenciais', [CredentialMergeController::class, 'store'])
        ->middleware('throttle:authentication');

    Route::post('/uniao-credenciais/link', [CredentialMergeController::class, 'sendLink'])
        ->middleware('throttle:email-sending');

    Route::post('/uniao-credenciais/link/confirmar', [CredentialMergeController::class, 'confirmLink']);

    /*
     * Recuperação de senha (US4). Pública por necessidade: quem esqueceu a
     * senha não consegue entrar — exigir sessão aqui fecharia a única saída.
     *
     * O pedido leva o limitador de envio de e-mail: cada acerto custa um e-mail
     * de verdade, e sem limite isto vira ferramenta de varredura e de spam.
     */
    Route::post('/senha/esqueci', [PasswordController::class, 'forgot'])
        ->middleware('throttle:email-sending');

    Route::post('/senha/redefinir', [PasswordController::class, 'reset']);

    /*
     * Autenticado — o `sliding-session` renova o prazo a cada uso (D7).
     */
    Route::middleware(['auth:sanctum', 'sliding-session'])->group(function () {
        Route::get('/eu', [MeController::class, 'show']);

        Route::delete('/sessoes/atual', [SessionController::class, 'destroy']);

        // Primeira senha de conta nascida no Google (US2-5, D1 inversa).
        Route::post('/senha', [PasswordController::class, 'store']);

        Route::post('/email/verificar/reenviar', [EmailVerificationController::class, 'resend'])
            ->middleware('throttle:email-sending');
    });
});
