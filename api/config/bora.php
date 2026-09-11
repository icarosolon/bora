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

    'account' => [
        // Comprimento minimo da senha. Valor inicial da spec 001.
        'minimum_password_length' => (int) env('BORA_MIN_PASSWORD_LENGTH', 8),

        // Papel atribuido a toda conta nova (Principio I: uma conta, N papeis).
        // "rolezeiro" e vocabulario do produto (docs/product/vision.md), nao
        // identificador de codigo — por isso continua em portugues.
        'initial_role' => 'rolezeiro',

        // Papel de operacao da plataforma: aprova e recusa reivindicacao de
        // local (spec 002, FR-008 e FR-027). NUNCA se autoatribui e nao nasce
        // de cadastro — so o comando `bora:grant-operator` concede.
        //
        // Em ingles de proposito: papel de operacao NAO e vocabulario do
        // produto, ao contrario de "rolezeiro" acima, e chave de config e
        // identificador (docs/architecture/naming-conventions.md).
        'operation_role' => 'operator',
    ],

    'session' => [
        // D7: 30 dias de INATIVIDADE, renovados a cada uso. A janela deslizante
        // e implementada pelo middleware RefreshTokenExpiration — o Sanctum nao
        // tem isso nativo, e 'expiration' em config/sanctum.php DEVE ficar null,
        // senao sobrepoe o expires_at por token e quebra o deslizamento.
        'lifetime_days' => (int) env('BORA_SESSION_LIFETIME_DAYS', 30),
    ],

    'attempts' => [
        // Limite por minuto, por e-mail + IP. Vale para login E para a
        // confirmacao de uniao — a spec exige o bloqueio nas duas portas.
        'per_minute' => (int) env('BORA_ATTEMPTS_PER_MINUTE', 5),
    ],

    'email_tokens' => [
        // Validade em minutos, por finalidade. As chaves batem com as
        // constantes de EmailToken — e o que permite resolver o prazo pela
        // propria finalidade, sem um match a mais.
        'email_verification' => (int) env('BORA_TOKEN_VERIFICATION_MIN', 60 * 24 * 7), // 7 dias
        'password_reset' => (int) env('BORA_TOKEN_PASSWORD_RESET_MIN', 60),
        'credential_merge' => (int) env('BORA_TOKEN_MERGE_MIN', 60),
    ],

    // Token curto devolvido no 409 do login com Google, que so serve para
    // concluir a uniao — nao autentica nada. Contrato: auth-api.md.
    'merge' => [
        'lifetime_minutes' => (int) env('BORA_MERGE_LIFETIME_MIN', 15),
    ],

];
