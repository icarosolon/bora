<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Papeis da plataforma (RN-PLAT-001, Principio I).
 *
 * A spec 001 cria apenas `rolezeiro` — os papeis de gestor de estabelecimento e
 * artista chegam com as features de local e de artista, vinculados a MESMA
 * conta. Idempotente de proposito: roda quantas vezes for preciso.
 */
class RolesSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findOrCreate(config('bora.account.initial_role'), 'web');
    }
}
