<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Spec 001 — um mecanismo único de link de e-mail para as três finalidades:
 * verificação de e-mail (D5), união de credenciais (D1) e redefinição de senha
 * (D6). Os três têm a mesma semântica — gerar, enviar, validar uma vez, expirar
 * — e manter um mecanismo só é mais fácil de auditar e testar que dois.
 *
 * A tabela `password_reset_tokens` do Laravel fica sem uso. Removê-la é decisão
 * do Ícaro; não se apaga tabela por conta própria.
 *
 * Regra de segurança materializada aqui: guarda-se apenas o HASH do token. O
 * valor em claro vai no e-mail e nunca é persistido — se o banco vazar, os
 * links não são reutilizáveis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            // email_verification | credential_merge | password_reset
            $table->string('purpose', 32);
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            // Preenchido no primeiro uso — é o que garante o uso único.
            $table->timestamp('used_at')->nullable();
            // Contexto do fluxo (ex.: provedor a vincular, na união).
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_tokens');
    }
};
