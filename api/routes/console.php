<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Tarefas agendadas
|--------------------------------------------------------------------------
*/

/*
 * Sessões vencidas viram lixo no banco. A janela é deslizante (D7), então token
 * de quem sumiu há mais de 30 dias nunca mais será renovado — some.
 *
 * O `--hours=24` dá folga: só apaga o que já está expirado há um dia, evitando
 * corrida com uma renovação em andamento.
 */
Schedule::command('sanctum:prune-expired --hours=24')->daily();

/*
 * Tokens de e-mail (verificação, união, redefinição) já usados ou vencidos não
 * têm mais função. Guardá-los indefinidamente só aumenta a superfície de um
 * eventual vazamento — mesmo sendo hash.
 */
Schedule::call(function () {
    \App\Models\TokenDeEmail::query()
        ->where(function ($q) {
            $q->whereNotNull('usado_em')->orWhere('expira_em', '<', now());
        })
        ->where('created_at', '<', now()->subDays(7))
        ->delete();
})->daily()->name('podar-tokens-de-email')->withoutOverlapping();
