<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Account\Email;
use App\Domain\Account\PoliticaDeSenha;
use App\Http\Controllers\Controller;
use App\UseCases\Account\DefinirSenha;
use App\UseCases\Account\RedefinirSenha;
use App\UseCases\Account\SolicitarRedefinicaoDeSenha;
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

    /**
     * "Esqueci minha senha", passo 1 (US4).
     *
     * A resposta e SEMPRE a mesma, exista ou nao a conta. Diferenciar aqui
     * transformaria a tela num verificador de quais e-mails tem conta no Bora
     * (FR-014, Principio V).
     */
    public function esqueci(Request $request, SolicitarRedefinicaoDeSenha $solicitar): JsonResponse
    {
        $dados = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
        ], [
            'email.required' => 'Digite seu e-mail.',
            'email.email' => 'Digite um e-mail valido, como nome@exemplo.com.',
        ]);

        $solicitar->executar(Email::de($dados['email']));

        return response()->json([
            'message' => 'Se este e-mail estiver cadastrado, voce recebera um link para redefinir a senha.',
        ]);
    }

    /** "Esqueci minha senha", passo 2: define a senha nova pelo link. */
    public function redefinir(Request $request, RedefinirSenha $redefinir): JsonResponse
    {
        $minimo = app(PoliticaDeSenha::class)->comprimentoMinimo;

        $dados = $request->validate([
            'token' => ['required', 'string'],
            'senha' => ['required', 'string', 'min:'.$minimo, 'max:255'],
        ], [
            'token.required' => 'Link invalido. Abra o link direto do e-mail que enviamos.',
            'senha.required' => 'Escolha uma senha.',
            'senha.min' => "A senha precisa de pelo menos {$minimo} caracteres.",
        ]);

        $redefinir->executar($dados['token'], $dados['senha']);

        return response()->json([
            'message' => 'Senha alterada. Voce ja pode entrar com ela.',
        ]);
    }
}
