<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Entrada da confirmacao de uniao por senha (Principio V).
 *
 * Repare no que NAO tem aqui: comprimento minimo de senha. A senha esta sendo
 * COMPARADA com a que ja existe, nao avaliada -- exigir 8 caracteres para
 * confirmar entregaria a politica a quem sonda e recusaria, com mensagem
 * diferente, quem tem senha antiga mais curta. Mesma razao do LoginRequest.
 */
class MergeCredentialsRequest extends FormRequest
{
    /** Publico: quem chega aqui ainda nao tem sessao -- e o que ele vem obter. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'merge_token' => ['required', 'string'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'merge_token.required' => 'Este pedido expirou. Entre com o Google de novo.',
            'password.required' => 'Digite a senha da sua conta do Bora.',
        ];
    }
}
