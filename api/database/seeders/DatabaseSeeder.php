<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Semeia apenas dado de plataforma — nunca conta de pessoa.
     *
     * O scaffold do Laravel criava aqui um "Test User" (test@example.com).
     * Removido de propósito na spec 001: a invariante central desta feature é
     * "uma conta por e-mail" (RN-PLAT-001), e um seeder que cria conta silen-
     * ciosamente atrapalha tanto o teste manual quanto a leitura do banco.
     * Conta de teste se cria por factory, dentro do teste que precisa dela.
     */
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
        ]);
    }
}
