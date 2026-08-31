<?php

/*
|--------------------------------------------------------------------------
| Parametros do dominio Bora
|--------------------------------------------------------------------------
|
| Principio VII da constituicao: a POLITICA vive no dominio, o PARAMETRO e
| dado — nunca hardcoded. Os valores iniciais abaixo vieram da spec 001;
| trocar um numero aqui nao pode exigir mexer em caso de uso nem em teste.
|
| Ver: specs/001-contas-autenticacao/spec.md (Assumptions e D7)
*/

return [

    'conta' => [
        // Comprimento minimo da senha. Valor inicial da spec 001.
        'senha_minima' => (int) env('BORA_SENHA_MINIMA', 8),

        // Papel atribuido a toda conta nova (Principio I: uma conta, N papeis).
        'papel_inicial' => 'rolezeiro',
    ],

    'sessao' => [
        // D7: 30 dias de INATIVIDADE, renovados a cada uso. A janela deslizante
        // e implementada pelo middleware RenovarExpiracaoDoToken — o Sanctum nao
        // tem isso nativo, e 'expiration' em config/sanctum.php DEVE ficar null,
        // senao sobrepoe o expires_at por token e quebra o deslizamento.
        'validade_dias' => (int) env('BORA_SESSAO_VALIDADE_DIAS', 30),
    ],

    'tentativas' => [
        // Limite por minuto, por e-mail + IP. Vale para login E para a
        // confirmacao de uniao — a spec exige o bloqueio nas duas portas.
        'por_minuto' => (int) env('BORA_TENTATIVAS_POR_MINUTO', 5),
    ],

    'tokens_de_email' => [
        // Validade em minutos, por finalidade.
        'verificacao_email' => (int) env('BORA_TOKEN_VERIFICACAO_MIN', 60 * 24 * 7), // 7 dias
        'redefinicao_senha' => (int) env('BORA_TOKEN_REDEFINICAO_MIN', 60),
        'uniao_credenciais' => (int) env('BORA_TOKEN_UNIAO_MIN', 60),
    ],

    // Token curto devolvido no 409 do login com Google, que so serve para
    // concluir a uniao — nao autentica nada. Contrato: auth-api.md.
    'uniao' => [
        'validade_minutos' => (int) env('BORA_UNIAO_VALIDADE_MIN', 15),
    ],

];
