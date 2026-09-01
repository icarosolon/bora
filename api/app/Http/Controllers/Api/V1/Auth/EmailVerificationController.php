<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\UseCases\Account\VerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Verificação de e-mail (decisão D5).
 *
 * `POST /api/v1/email/verificar` é PÚBLICO de propósito — a pessoa abre o link
 * no e-mail, com frequência noutro aparelho, sem sessão.
 * `POST /api/v1/email/verificar/reenviar` exige sessão: só quem já está na
 * conta pede outro link para si.
 */
class EmailVerificationController extends Controller
{
    public function store(Request $request, VerifyEmail $verifyEmail): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
        ], [
            'token.required' => 'Link inválido. Abra o link direto do e-mail que enviamos.',
        ]);

        $verifyEmail->execute($data['token']);

        return response()->json(['message' => 'E-mail confirmado. Obrigado!']);
    }

    public function resend(Request $request, VerifyEmail $verifyEmail): JsonResponse
    {
        $account = $request->user();

        if ($account->email_verified_at !== null) {
            throw ValidationException::withMessages([
                'email' => 'Seu e-mail já está confirmado.',
            ]);
        }

        $verifyEmail->resend($account);

        return response()->json(['message' => 'Enviamos um novo link para o seu e-mail.']);
    }
}
