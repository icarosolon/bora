<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\SessaoResource;
use App\Support\Auditoria;
use App\UseCases\Account\AutenticarPorSenha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Sessões — entrar (`POST /api/v1/sessoes`) e sair
 * (`DELETE /api/v1/sessoes/atual`).
 */
class SessaoController extends Controller
{
    public function store(LoginRequest $request, AutenticarPorSenha $autenticar): JsonResponse
    {
        $sessao = $autenticar->executar(
            email: $request->emailNormalizado(),
            senha: $request->validated('senha'),
            dispositivo: $request->validated('dispositivo'),
        );

        return SessaoResource::make($sessao)->response();
    }

    /**
     * Encerra a sessão DESTE aparelho.
     *
     * Revoga só o token da request: sair no celular não pode deslogar o
     * computador (FR-008).
     */
    public function destroy(Request $request): Response
    {
        $conta = $request->user();

        $request->user()->currentAccessToken()->delete();

        Auditoria::registrar(Auditoria::SESSAO_ENCERRADA, sobre: $conta, autor: $conta);

        return response()->noContent();
    }
}
