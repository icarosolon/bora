<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Spec 002 — vínculo de gestão, N:N por decisão explícita (FR-012,
 * RN-LOCAL-004): uma conta gerencia vários locais com a MESMA conta
 * (Princípio I).
 *
 * A linha nunca se apaga: revogar é preencher `revoked_at` (Princípios VIII e X).
 * Por isso a unicidade do par (venue_id, account_id) vale SÓ entre vínculos
 * ativos: `active_marker` é uma coluna gerada que vale 1 enquanto o vínculo está
 * ativo e NULL depois de revogado; NULL não colide em índice único no MySQL,
 * então o histórico de vínculos revogados do mesmo par é preservado.
 *
 * Criada após venue_claims porque `granted_by_claim_id` referencia essa tabela.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_managers', function (Blueprint $table) {
            $table->id();
            // Sem cascade: nenhum vínculo some por exclusão de local, conta ou pedido.
            $table->foreignId('venue_id')->constrained('venues')->restrictOnDelete();
            $table->foreignId('account_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('granted_at');
            $table->foreignId('granted_by_claim_id')->constrained('venue_claims')->restrictOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedTinyInteger('active_marker')->nullable()
                ->virtualAs('IF(revoked_at IS NULL, 1, NULL)');
            $table->timestamps();

            $table->unique(['venue_id', 'account_id', 'active_marker'], 'venue_managers_active_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_managers');
    }
};
