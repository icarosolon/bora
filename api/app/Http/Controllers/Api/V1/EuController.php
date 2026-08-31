<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContaResource;
use Illuminate\Http\Request;

/**
 * `GET /api/v1/eu` — a conta autenticada.
 *
 * Substitui a rota /api/user que o `install:api` deixou fora do versionamento
 * (Principio IV). E o endpoint que a tela usa para saber quem esta logado e se
 * o e-mail ja foi confirmado.
 */
class EuController extends Controller
{
    public function show(Request $request): ContaResource
    {
        return ContaResource::make(
            $request->user()->load(['roles', 'contasSociais'])
        );
    }
}
