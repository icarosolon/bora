<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Account\UniaoPendente;
use App\Http\Controllers\Controller;
use App\Http\Resources\SessaoResource;
use App\Ports\FalhaDoProvedorDeIdentidade;
use App\UseCases\Account\AutenticarPorGoogle;
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
    public function url(AutenticarPorGoogle $autenticar): JsonResponse
    {
        return response()->json(['data' => $autenticar->iniciar()]);
    }

    public function store(Request $request, AutenticarPorGoogle $autenticar): JsonResponse
    {
        $dados = $request->validate([
            'code' => ['required', 'string'],
            'state' => ['required', 'string'],
        ], [
            'code.required' => 'Não deu para concluir a entrada com o Google. Tente de novo.',
            'state.required' => 'Este pedido expirou. Toque em "Entrar com Google" de novo.',
        ]);

        try {
            $resultado = $autenticar->concluir($dados['code'], $dados['state']);
        } catch (FalhaDoProvedorDeIdentidade $e) {
            // O detalhe técnico fica no log; a tela recebe linguagem humana com
            // uma saída (ux-requirements.md). Sem dado pessoal no log
            // (Princípio V) — só o motivo devolvido pelo provedor.
            Log::info('Falha no login com Google.', ['motivo' => $e->getMessage()]);

            return response()->json([
                'message' => 'Não deu para entrar com o Google agora. Tente de novo ou use seu e-mail e senha.',
            ], 401);
        }

        if ($resultado instanceof UniaoPendente) {
            // 409, não erro: existe conta com este e-mail e falta a confirmação
            // do titular. NADA foi gravado (Princípio I). A união é a US3.
            return response()->json([
                'message' => 'Você já tem conta no Bora com este e-mail. Confirme para unir e entrar com o Google também.',
                'data' => [
                    'situacao' => 'uniao_necessaria',
                    'email' => (string) $resultado->email,
                    'uniao_token' => $resultado->token,
                    'expira_em' => $resultado->expiraEm->format(DATE_ATOM),
                ],
            ], 409);
        }

        return SessaoResource::make($resultado)->response();
    }
}
