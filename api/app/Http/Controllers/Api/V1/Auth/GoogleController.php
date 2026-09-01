<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Account\PendingMerge;
use App\Http\Controllers\Controller;
use App\Http\Resources\SessionResource;
use App\Ports\IdentityProviderFailure;
use App\UseCases\Account\AuthenticateWithGoogle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Entrar com Google (US2).
 *
 * O fluxo tem dois passos e **o token nunca passa pela URL**: a API devolve a
 * URL de autorização, o Google redireciona para uma página do `web/`, e o
 * `web/` troca o `code` por sessão neste POST. Se a API recebesse o retorno
 * direto e redirecionasse com o token, ele acabaria no histórico do navegador
 * e no log de servidor.
 */
class GoogleController extends Controller
{
    public function url(AuthenticateWithGoogle $authenticate): JsonResponse
    {
        return response()->json(['data' => $authenticate->start()]);
    }

    public function store(Request $request, AuthenticateWithGoogle $authenticate): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
            'state' => ['required', 'string'],
        ], [
            'code.required' => 'Não deu para concluir a entrada com o Google. Tente de novo.',
            'state.required' => 'Este pedido expirou. Toque em "Entrar com Google" de novo.',
        ]);

        try {
            $result = $authenticate->complete($data['code'], $data['state']);
        } catch (IdentityProviderFailure $e) {
            // O detalhe técnico fica no log; a tela recebe linguagem humana com
            // uma saída (ux-requirements.md). Sem dado pessoal no log
            // (Princípio V) — só o motivo devolvido pelo provedor.
            Log::info('Falha no login com Google.', ['motivo' => $e->getMessage()]);

            return response()->json([
                'message' => 'Não deu para entrar com o Google agora. Tente de novo ou use seu e-mail e senha.',
            ], 401);
        }

        if ($result instanceof PendingMerge) {
            // 409, não erro: existe conta com este e-mail e falta a confirmação
            // do titular. NADA foi gravado (Princípio I). A união é a US3.
            return response()->json([
                'message' => 'Você já tem conta no Bora com este e-mail. Confirme para unir e entrar com o Google também.',
                'data' => [
                    'status' => 'merge_required',
                    'email' => (string) $result->email,
                    'merge_token' => $result->token,
                    'expires_at' => $result->expiresAt->format(DATE_ATOM),
                ],
            ], 409);
        }

        return SessionResource::make($result)->response();
    }
}
