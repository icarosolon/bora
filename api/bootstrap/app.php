<?php

use App\Http\Middleware\RenovarExpiracaoDoToken;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Janela deslizante da sessão (D7). Ver RenovarExpiracaoDoToken.
        $middleware->alias([
            'sessao.deslizante' => RenovarExpiracaoDoToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        /*
         * Envelope de erro da constituição (Princípio IV):
         *   422 -> `errors` por campo + `message`
         *   401/403/409/410/429 -> `message`
         *   5xx -> `message` genérica; detalhe interno NUNCA chega à tela.
         *
         * As mensagens são as que a pessoa lê (ux-requirements.md: linguagem
         * humana dizendo o que fazer), por isso ficam em português aqui e não
         * em inglês do framework.
         */
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => 'Confira os campos destacados.',
                    'errors' => $e->errors(),
                ], 422);
            }

            if ($e instanceof ThrottleRequestsException) {
                $segundos = (int) ($e->getHeaders()['Retry-After'] ?? 60);
                $espera = $segundos >= 60
                    ? ceil($segundos / 60).' minuto'.($segundos >= 120 ? 's' : '')
                    : $segundos.' segundos';

                return response()->json([
                    'message' => "Muitas tentativas. Aguarde {$espera} e tente de novo.",
                ], 429, $e->getHeaders());
            }

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'message' => 'Faça login para continuar.',
                ], 401);
            }

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();

                if ($status >= 500) {
                    return response()->json([
                        'message' => 'Algo deu errado do nosso lado. Tente de novo em instantes.',
                    ], $status);
                }

                return response()->json([
                    'message' => $e->getMessage() !== ''
                        ? $e->getMessage()
                        : 'Não foi possível concluir essa ação.',
                ], $status, $e->getHeaders());
            }

            // Erro não previsto: em produção some o detalhe; em dev o Laravel
            // mostra o normal, para não atrapalhar a depuração.
            if (! config('app.debug')) {
                return response()->json([
                    'message' => 'Algo deu errado do nosso lado. Tente de novo em instantes.',
                ], 500);
            }

            return null;
        });
    })->create();
