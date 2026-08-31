<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UnirCredenciaisRequest;
use App\Http\Resources\SessaoResource;
use App\UseCases\Account\UnirCredenciais;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * União de credenciais (US3, decisão D1).
 *
 * Chega-se aqui pelo 409 do login com Google: existe conta com aquele e-mail e
 * falta a confirmação do titular. Até uma destas rotas concluir, **nada foi
 * gravado** — é o que sustenta a invariante do Princípio I.
 */
class UniaoCredenciaisController extends Controller
{
    /** Confirma com a senha da conta existente. */
    public function store(UnirCredenciaisRequest $request, UnirCredenciais $unir): JsonResponse
    {
        $sessao = $unir->confirmarComSenha(
            $request->validated('uniao_token'),
            $request->validated('senha'),
        );

        return SessaoResource::make($sessao)
            ->additional([
                'message' => 'Pronto — agora você pode entrar com Google ou com sua senha.',
            ])
            ->response();
    }

    /** Plano B: envia o link de confirmação para o e-mail da conta. */
    public function enviarLink(Request $request, UnirCredenciais $unir): JsonResponse
    {
        $dados = $request->validate([
            'uniao_token' => ['required', 'string'],
        ], [
            'uniao_token.required' => 'Este pedido expirou. Entre com o Google de novo.',
        ]);

        $unir->enviarLink($dados['uniao_token']);

        return response()->json([
            'message' => 'Enviamos um link para o seu e-mail. Ele vale por 1 hora.',
        ]);
    }

    /** Plano B: conclui a união pelo link recebido. */
    public function confirmarLink(Request $request, UnirCredenciais $unir): JsonResponse
    {
        $dados = $request->validate([
            'token' => ['required', 'string'],
        ], [
            'token.required' => 'Link inválido. Abra o link direto do e-mail que enviamos.',
        ]);

        $sessao = $unir->confirmarComLink($dados['token']);

        return SessaoResource::make($sessao)
            ->additional([
                'message' => 'Pronto — agora você pode entrar com Google ou com sua senha.',
            ])
            ->response();
    }
}
