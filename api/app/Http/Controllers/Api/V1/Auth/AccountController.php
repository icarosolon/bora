<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CreateAccountRequest;
use App\Http\Resources\SessionResource;
use App\UseCases\Account\RegisterAccount;
use Illuminate\Http\JsonResponse;

/**
 * Criação de conta — `POST /api/v1/contas`.
 *
 * Controller fino (Princípio VII): valida pela FormRequest, delega ao caso de
 * uso, responde por Resource. Nenhuma regra mora aqui.
 */
class AccountController extends Controller
{
    public function store(CreateAccountRequest $request, RegisterAccount $register): JsonResponse
    {
        $session = $register->execute(
            name: $request->validated('name'),
            email: $request->normalizedEmail(),
            password: $request->validated('password'),
            device: $request->validated('device'),
        );

        return SessionResource::make($session)
            ->additional(['message' => 'Conta criada! Boas-vindas ao Bora.'])
            ->response()
            ->setStatusCode(201);
    }
}
