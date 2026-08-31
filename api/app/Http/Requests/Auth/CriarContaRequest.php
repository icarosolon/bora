<?php

namespace App\Http\Requests\Auth;

use App\Domain\Account\Email;
use App\Domain\Account\PoliticaDeSenha;
use App\Rules\EmailDisponivelParaCadastro;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Entrada do cadastro (Princípio V: FormRequest com authorize() e rules(),
 * sempre validated(), nunca $request->all()).
 *
 * As mensagens são as que a pessoa LÊ na tela, então são escritas em português
 * do dia a dia e dizem o que corrigir — "campo inválido" reprova no
 * ux-requirements.md.
 */
class CriarContaRequest extends FormRequest
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
            'nome' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', new EmailDisponivelParaCadastro],
            'senha' => [
                'required',
                'string',
                'min:'.app(PoliticaDeSenha::class)->comprimentoMinimo,
                'max:255',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $minimo = app(PoliticaDeSenha::class)->comprimentoMinimo;

        return [
            'nome.required' => 'Digite seu nome.',
            'nome.min' => 'Digite seu nome completo ou como você quer ser chamado.',
            'email.required' => 'Digite seu e-mail.',
            'email.email' => 'Digite um e-mail válido, como nome@exemplo.com.',
            'senha.required' => 'Escolha uma senha.',
            'senha.min' => "A senha precisa de pelo menos {$minimo} caracteres.",
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['nome' => 'nome', 'email' => 'e-mail', 'senha' => 'senha'];
    }

    public function emailNormalizado(): Email
    {
        return Email::de($this->validated('email'));
    }
}
