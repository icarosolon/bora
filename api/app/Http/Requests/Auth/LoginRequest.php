<?php

namespace App\Http\Requests\Auth;

use App\Domain\Account\Email;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Entrada do login.
 *
 * Repare no que NÃO tem aqui: nenhuma regra de comprimento mínimo de senha. Na
 * entrada, a senha é comparada, não avaliada — exigir 8 caracteres para tentar
 * entrar entregaria a política de senha a quem está sondando e recusaria, com
 * mensagem diferente, quem tem senha antiga mais curta.
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'senha' => ['required', 'string', 'max:255'],
            'dispositivo' => ['nullable', 'string', 'max:60'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'email.required' => 'Digite seu e-mail.',
            'email.email' => 'Digite um e-mail válido, como nome@exemplo.com.',
            'senha.required' => 'Digite sua senha.',
        ];
    }

    public function emailNormalizado(): Email
    {
        return Email::de($this->validated('email'));
    }
}
