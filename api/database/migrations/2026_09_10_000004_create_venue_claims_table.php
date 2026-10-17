<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Spec 002 — pedido de reivindicação (data-model.md, seção `venue_claims`).
 *
 * Vários `pending` para o mesmo local são permitidos (FR-022): por isso NÃO há
 * índice único em (venue_id, account_id) nem em (venue_id, status).
 * Os quatro campos de evidência (FR-026) dão matéria à aprovação manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_claims', function (Blueprint $table) {
            $table->id();
            // Sem cascade: pedido é histórico e não se apaga (Princípios VIII e X).
            $table->foreignId('venue_id')->constrained('venues')->restrictOnDelete();
            $table->foreignId('account_id')->constrained('users')->restrictOnDelete();
            // String, não enum do MySQL; os valores válidos vivem em App\Enums\VenueClaimStatus.
            $table->string('status', 16)->default('pending');
            $table->string('requester_name');
            $table->string('requester_role');
            $table->string('best_contact_time');
            $table->string('ask_for');
            $table->foreignId('decided_by_account_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_reason')->nullable();
            $table->string('verification_method')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_claims');
    }
};
