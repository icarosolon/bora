<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Account\Email;
use App\Domain\Account\PasswordPolicy;
use App\Http\Controllers\Controller;
use App\UseCases\Account\SetPassword;
use App\UseCases\Account\ResetPassword;
use App\UseCases\Account\RequestPasswordReset;
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
class PasswordController extends Controller
{
    public function store(Request $request, SetPassword $setPassword): JsonResponse
    {
        $minimum = app(PasswordPolicy::class)->minimumLength;

        $data = $request->validate([
            'password' => ['required', 'string', 'min:'.$minimum, 'max:255'],
        ], [
            'password.required' => 'Escolha uma senha.',
            'password.min' => "A senha precisa de pelo menos {$minimum} caracteres.",
        ]);

        $setPassword->execute($request->user(), $data['password']);

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
    public function forgot(Request $request, RequestPasswordReset $requestReset): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
        ], [
            'email.required' => 'Digite seu e-mail.',
            'email.email' => 'Digite um e-mail valido, como nome@exemplo.com.',
        ]);

        $requestReset->execute(Email::from($data['email']));

        return response()->json([
            'message' => 'Se este e-mail estiver cadastrado, voce recebera um link para redefinir a senha.',
        ]);
    }

    /** "Esqueci minha senha", passo 2: define a senha nova pelo link. */
    public function reset(Request $request, ResetPassword $resetPassword): JsonResponse
    {
        $minimum = app(PasswordPolicy::class)->minimumLength;

        $data = $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:'.$minimum, 'max:255'],
        ], [
            'token.required' => 'Link invalido. Abra o link direto do e-mail que enviamos.',
            'password.required' => 'Escolha uma senha.',
            'password.min' => "A senha precisa de pelo menos {$minimum} caracteres.",
        ]);

        $resetPassword->execute($data['token'], $data['password']);

        return response()->json([
            'message' => 'Senha alterada. Voce ja pode entrar com ela.',
        ]);
    }
}
