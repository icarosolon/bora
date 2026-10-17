<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Spec 002 — o estabelecimento (data-model.md, seção `venues`).
 *
 * Campos ricos (nulos) são TRÊS e só três: description, instagram e logo_path.
 * Não há coluna de capa: `cover_path` foi retirado em 2026-09-10.
 * latitude/longitude existem nulas para a BORA-8 não exigir migração depois (R5).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('name_normalized')->index();
            $table->string('phone');
            $table->string('postal_code');
            $table->string('street');
            $table->string('number');
            $table->string('complement')->nullable();
            $table->string('district')->index();
            $table->string('city')->index();
            $table->string('state')->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('description')->nullable();
            $table->string('instagram')->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('active')->default(true);
            // Sem cascadeOnDelete: local e conta não se excluem, se inativam (Princípio X).
            $table->foreignId('created_by_account_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};
