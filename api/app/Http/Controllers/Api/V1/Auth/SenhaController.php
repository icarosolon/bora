<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Account\PoliticaDeSenha;
use App\Http\Controllers\Controller;
use App\UseCases\Account\DefinirSenha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Senha da conta.
 *
 * `POST /api/v1/senha` -- define a PRIMEIRA senha de uma conta nascida no
 * Google (US2-5). Exige sessao ativa: e ela que faz as vezes de confirmacao do
 * titular (decisao D1, direcao inversa).
 *
 * Os fluxos de "esqueci minha senha" e "redefinir" entram na US4.
 */
class SenhaController extends Controller
{
    public function store(Request $request, DefinirSenha $definir): JsonResponse
    {
        $minimo = app(PoliticaDeSenha::class)->comprimentoMinimo;

        $dados = $request->validate([
            'senha' => ['required', 'string', 'min:'.$minimo, 'max:255'],
        ], [
            'senha.required' => 'Escolha uma senha.',
            'senha.min' => "A senha precisa de pelo menos {$minimo} caracteres.",
        ]);

        $definir->executar($request->user(), $dados['senha']);

        return response()->json([
            'message' => 'Senha definida. Agora voce tambem entra com e-mail e senha.',
        ]);
    }
}
