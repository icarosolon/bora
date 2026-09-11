<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Papeis da plataforma (RN-PLAT-001, Principio I).
 *
 * A spec 001 criou apenas `rolezeiro` — os papeis de gestor de estabelecimento
 * e artista chegam com as features de local e de artista, vinculados a MESMA
 * conta. Idempotente de proposito: roda quantas vezes for preciso.
 *
 * A spec 002 acrescenta o papel de OPERACAO, que e de outra natureza: os papeis
 * de produto acumulam-se na conta por cadastro; o de operacao NUNCA se
 * autoatribui. Semear aqui cria o papel vazio — quem o recebe e decidido pelo
 * comando `bora:grant-operator`, nunca por cadastro (spec 002, FR-027).
 *
 * Semeado, e nao concedido a mao no banco, porque a T037 roda
 * `migrate:fresh --seed`: concessao manual nao sobrevive a recriacao do
 * esquema, que se repete a cada iteracao do desenvolvimento.
 */
class RolesSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findOrCreate(config('bora.account.initial_role'), 'web');
        Role::findOrCreate(config('bora.account.operation_role'), 'web');
    }
}
