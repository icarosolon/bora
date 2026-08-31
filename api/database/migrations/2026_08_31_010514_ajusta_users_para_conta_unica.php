<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Spec 001 — RN-PLAT-001 (conta única multi-papel).
 *
 * Duas mudanças em `users`:
 *
 * 1. `password` passa a NULLABLE. Conta que nasce pelo login com Google não tem
 *    senha, e forçar uma senha aleatória para preencher a coluna criaria uma
 *    credencial que ninguém conhece e que o "esqueci minha senha" trataria como
 *    válida. Ver data-model.md.
 * 2. `ultimo_acesso_em` — alimenta a política de sessão (D7) e o suporte.
 *    Não é dado sensível.
 *
 * O índice único de `email` JÁ EXISTE (users_email_unique, verificado no banco)
 * e é a última linha de defesa da invariante de conta única. Não recriar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
            $table->timestamp('ultimo_acesso_em')->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ultimo_acesso_em');
            // Atenção ao reverter: contas criadas via Google têm password NULL e
            // violariam o NOT NULL. Este down() só é seguro antes de existir
            // qualquer conta social.
            $table->string('password')->nullable(false)->change();
        });
    }
};
