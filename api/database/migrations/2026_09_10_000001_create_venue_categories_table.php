<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Spec 002 — RN-LOCAL-002: a lista de categorias é DADO, não código.
 * Acrescentar categoria é inserir uma linha nesta tabela.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->unsignedInteger('position')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_categories');
    }
};
