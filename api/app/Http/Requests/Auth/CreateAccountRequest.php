<?php

namespace App\Http\Requests\Auth;

use App\Domain\Account\Email;
use App\Domain\Account\PasswordPolicy;
use App\Rules\EmailAvailableForSignup;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Entrada do cadastro (Princípio V: FormRequest com authorize() e rules(),
 * sempre validated(), nunca $request->all()).
 *
 * As mensagens são as que a pessoa LÊ na tela, então são escritas em português
 * do dia a dia e dizem o que corrigir — "campo inválido" reprova no
 * ux-requirements.md.
 */
class CreateAccountRequest extends FormRequest
{
    /** Cadastro é público: qualquer pessoa pode criar conta (plataforma aberta). */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', new EmailAvailableForSignup],
            'password' => [
                'required',
                'string',
                'min:'.app(PasswordPolicy::class)->minimumLength,
                'max:255',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $minimum = app(PasswordPolicy::class)->minimumLength;

        return [
            'name.required' => 'Digite seu nome.',
            'name.min' => 'Digite seu nome completo ou como você quer ser chamado.',
            'email.required' => 'Digite seu e-mail.',
            'email.email' => 'Digite um e-mail válido, como nome@exemplo.com.',
            'password.required' => 'Escolha uma senha.',
            'password.min' => "A senha precisa de pelo menos {$minimum} caracteres.",
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'nome', 'email' => 'e-mail', 'password' => 'senha'];
    }

    public function normalizedEmail(): Email
    {
        return Email::from($this->validated('email'));
    }
}
