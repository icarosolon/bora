<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CriarContaRequest;
use App\Http\Resources\SessaoResource;
use App\UseCases\Account\RegistrarConta;
use Illuminate\Http\JsonResponse;

/**
 * Criação de conta — `POST /api/v1/contas`.
 *
 * Controller fino (Princípio VII): valida pela FormRequest, delega ao caso de
 * uso, responde por Resource. Nenhuma regra mora aqui.
 */
class ContaController extends Controller
{
    public function store(CriarContaRequest $request, RegistrarConta $registrar): JsonResponse
    {
        $sessao = $registrar->executar(
            nome: $request->validated('nome'),
            email: $request->emailNormalizado(),
            senha: $request->validated('senha'),
            dispositivo: $request->validated('dispositivo'),
        );

        return SessaoResource::make($sessao)
            ->additional(['message' => 'Conta criada! Boas-vindas ao Bora.'])
            ->response()
            ->setStatusCode(201);
    }
}
