<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

/*
|--------------------------------------------------------------------------
| Documentacao da API (decisao D3)
|--------------------------------------------------------------------------
|
| OpenAPI gerado a partir do proprio codigo — FormRequests e Resources —, para
| a doc nao poder ficar velha sem que o codigo mude. E o que sustenta a regra
| "documentacao no mesmo commit".
|
| O contrato revisado a mao vive em
| specs/001-contas-autenticacao/contracts/auth-api.md. Divergencia entre os dois
| e defeito, nao questao de gosto.
*/

return [
    'api_path' => 'api/v1',

    'info' => [
        'version' => '1.0.0',
        'description' => 'API publica do Bora. E a mesma API que o site e o futuro '
            .'app mobile consomem — nao existe endpoint privado "so do site" '
            .'(Principio IV da constituicao).',
    ],

    'ui' => [
        'title' => 'Bora — API v1',
        'theme' => 'light',
        'hide_try_it' => false,
    ],

    'servers' => null,

    // Em producao a doc so abre para quem tem acesso; em local, aberta.
    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],

    'extensions' => [],
];
