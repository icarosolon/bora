<?php

/*
|--------------------------------------------------------------------------
| CORS — spec 001, decisao D2
|--------------------------------------------------------------------------
|
| A area logada usa token Bearer (nao cookie de sessao), entao:
|
| - 'supports_credentials' fica FALSE. Nenhum cookie atravessa origem; quem
|   autoriza e o header Authorization. Ligar isso reabriria o conflito que o
|   spike BORA-32 registrou: '*' nao convive com credenciais.
| - 'allowed_origins' e EXPLICITO. O default do framework era '*', que
|   deixaria qualquer origem chamar a API. Origem declarada e o minimo do
|   Principio V (seguranca por padrao).
| - 'sanctum/csrf-cookie' saiu de 'paths': essa rota so serve ao modo SPA
|   stateful, que esta feature nao usa.
|
| Ver: specs/001-contas-autenticacao/research.md §4
*/

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_filter(explode(',', (string) env('FRONTEND_URLS', 'http://localhost:3000'))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'X-Requested-With'],

    // Retry-After nao e header seguro por padrao no CORS: sem expor aqui, o
    // JavaScript da tela nao consegue le-lo e nao tem como dizer a pessoa
    // quanto tempo falta apos um 429 (contrato de erro da spec 001).
    'exposed_headers' => ['Retry-After'],

    'max_age' => 0,

    'supports_credentials' => false,

];
