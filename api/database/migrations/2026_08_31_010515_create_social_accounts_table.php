<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Spec 001 — RN-PLAT-002 (autenticação: Google ou e-mail/senha na mesma conta).
 *
 * Decisão de modelagem que importa: o vínculo é pelo `provider_user_id`, NÃO
 * pelo e-mail. Isso resolve o caso "a pessoa trocou o e-mail da conta Google":
 * ela continua entrando na mesma conta do Bora, porque o identificador do
 * provedor não muda. O `provider_email` fica só para diagnóstico.
 *
 * Os dois índices únicos são a garantia estrutural do Princípio I:
 * - (provider, provider_user_id): uma identidade externa aponta para no máximo
 *   uma conta — impede duas contas reivindicarem o mesmo Google.
 * - (user_id, provedor): uma conta tem no máximo um vínculo por provedor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            // Sem cascadeOnDelete: conta não se exclui, se inativa (Princípio X).
            $table->foreignId('user_id')->constrained();
            $table->string('provider', 32);
            $table->string('provider_user_id', 191);
            $table->string('provider_email')->nullable();
            $table->timestamp('linked_at');
            $table->timestamps();

            $table->unique(['provider', 'provider_user_id']);
            $table->unique(['user_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
