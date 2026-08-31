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
        Schema::create('tokens_de_email', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            // verificacao_email | uniao_credenciais | redefinicao_senha
            $table->string('finalidade', 32);
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expira_em');
            // Preenchido no primeiro uso — é o que garante o uso único.
            $table->timestamp('usado_em')->nullable();
            // Contexto do fluxo (ex.: provedor a vincular, na união).
            $table->json('dados')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'finalidade']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tokens_de_email');
    }
};
