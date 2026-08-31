<?php

declare(strict_types=1);

namespace App\Rules;

use App\Domain\Account\Email;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * PROVA DO PRINCÍPIO I na borda de entrada: e-mail já cadastrado não vira conta
 * paralela — vira orientação para entrar.
 *
 * A regra compara o e-mail NORMALIZADO, senão bastaria trocar a caixa das
 * letras para criar uma segunda conta da mesma pessoa.
 *
 * A mensagem muda conforme o meio de entrada que a conta existente já tem. Isso
 * é UX, não regra: dizer "use sua senha" para quem entrou pelo Google mandaria
 * a pessoa procurar uma senha que nunca existiu (US2-4).
 */
final class EmailDisponivelParaCadastro implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $email = Email::tentar((string) $value);

        if ($email === null) {
            return; // formato inválido já é problema de outra regra
        }

        $conta = User::where('email', $email->valor)->first();

        if ($conta === null) {
            return;
        }

        if (! $conta->temSenha()) {
            $provedor = $conta->contasSociais()->value('provedor');

            $fail($provedor === 'google'
                ? 'Este e-mail já entra com o Google. Toque em "Entrar com Google".'
                : 'Este e-mail já tem conta no Bora. Toque em "Entrar".');

            return;
        }

        $fail('Este e-mail já tem conta. Entre com sua senha ou use "Esqueci minha senha".');
    }
}
