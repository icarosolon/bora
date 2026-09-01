<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\MergeCredentialsRequest;
use App\Http\Resources\SessionResource;
use App\UseCases\Account\MergeCredentials;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * União de credenciais (US3, decisão D1).
 *
 * Chega-se aqui pelo 409 do login com Google: existe conta com aquele e-mail e
 * falta a confirmação do titular. Até uma destas rotas concluir, **nada foi
 * gravado** — é o que sustenta a invariante do Princípio I.
 */
class CredentialMergeController extends Controller
{
    /** Confirma com a senha da conta existente. */
    public function store(MergeCredentialsRequest $request, MergeCredentials $merge): JsonResponse
    {
        $session = $merge->confirmWithPassword(
            $request->validated('merge_token'),
            $request->validated('password'),
        );

        return SessionResource::make($session)
            ->additional([
                'message' => 'Pronto — agora você pode entrar com Google ou com sua senha.',
            ])
            ->response();
    }

    /** Plano B: envia o link de confirmação para o e-mail da conta. */
    public function sendLink(Request $request, MergeCredentials $merge): JsonResponse
    {
        $data = $request->validate([
            'merge_token' => ['required', 'string'],
        ], [
            'merge_token.required' => 'Este pedido expirou. Entre com o Google de novo.',
        ]);

        $merge->sendLink($data['merge_token']);

        return response()->json([
            'message' => 'Enviamos um link para o seu e-mail. Ele vale por 1 hora.',
        ]);
    }

    /** Plano B: conclui a união pelo link recebido. */
    public function confirmLink(Request $request, MergeCredentials $merge): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
        ], [
            'token.required' => 'Link inválido. Abra o link direto do e-mail que enviamos.',
        ]);

        $session = $merge->confirmWithLink($data['token']);

        return SessionResource::make($session)
            ->additional([
                'message' => 'Pronto — agora você pode entrar com Google ou com sua senha.',
            ])
            ->response();
    }
}
