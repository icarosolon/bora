<?php

namespace Database\Factories;

use App\Models\VenueCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VenueCategory>
 */
class VenueCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => 'categoria-'.fake()->unique()->numerify('######'),
            'name' => fake()->unique()->words(2, true),
            'active' => true,
            'position' => fake()->numberBetween(10, 99),
        ];
    }
}
