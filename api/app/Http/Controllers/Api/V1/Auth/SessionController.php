<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\SessionResource;
use App\Support\AuditLog;
use App\UseCases\Account\AuthenticateWithPassword;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Sessões — entrar (`POST /api/v1/sessoes`) e sair
 * (`DELETE /api/v1/sessoes/atual`).
 */
class SessionController extends Controller
{
    public function store(LoginRequest $request, AuthenticateWithPassword $authenticate): JsonResponse
    {
        $session = $authenticate->execute(
            email: $request->normalizedEmail(),
            password: $request->validated('password'),
            device: $request->validated('device'),
        );

        return SessionResource::make($session)->response();
    }

    /**
     * Encerra a sessão DESTE aparelho.
     *
     * Revoga só o token da request: sair no celular não pode deslogar o
     * computador (FR-008).
     */
    public function destroy(Request $request): Response
    {
        $account = $request->user();

        $request->user()->currentAccessToken()->delete();

        AuditLog::record(AuditLog::SESSION_ENDED, subject: $account, causer: $account);

        return response()->noContent();
    }
}
