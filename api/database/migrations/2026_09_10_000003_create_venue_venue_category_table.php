<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Spec 002 — N:N entre local e categoria. Chave única no par e SEM teto de
 * categorias por local (RN-LOCAL-002).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_venue_category', function (Blueprint $table) {
            $table->id();
            // Sem cascade: local e categoria não se excluem, se inativam (Princípio X).
            $table->foreignId('venue_id')->constrained('venues')->restrictOnDelete();
            $table->foreignId('venue_category_id')->constrained('venue_categories')->restrictOnDelete();

            $table->unique(['venue_id', 'venue_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_venue_category');
    }
};
