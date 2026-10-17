<?php

namespace Database\Seeders;

use App\Models\VenueCategory;
use Illuminate\Database\Seeder;

/**
 * Categorias iniciais de local (RN-LOCAL-002): bar, restaurante, casa de shows.
 *
 * A lista é dado: acrescentar categoria é inserir linha, sem mudar código.
 * Idempotente (updateOrCreate pelo slug): roda quantas vezes for preciso.
 */
class VenueCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['slug' => 'bar', 'name' => 'Bar'],
            ['slug' => 'restaurante', 'name' => 'Restaurante'],
            ['slug' => 'casa-de-shows', 'name' => 'Casa de shows'],
        ];

        foreach ($categories as $index => $category) {
            VenueCategory::updateOrCreate(
                ['slug' => $category['slug']],
                ['name' => $category['name'], 'active' => true, 'position' => $index + 1],
            );
        }
    }
}
