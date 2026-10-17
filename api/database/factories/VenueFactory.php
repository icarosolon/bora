<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Local sem campos ricos (perfil não reivindicado, FR-002).
 *
 * @extends Factory<Venue>
 */
class VenueFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('####'),
            'name' => $name,
            'name_normalized' => Str::of($name)->ascii()->lower()->replaceMatches('/[^a-z0-9 ]/', '')->squish()->toString(),
            'phone' => fake()->numerify('(74) 9####-####'),
            'postal_code' => fake()->numerify('48900-###'),
            'street' => fake()->streetName(),
            'number' => (string) fake()->numberBetween(1, 999),
            'complement' => null,
            'district' => fake()->word(),
            'city' => 'Juazeiro',
            'state' => 'BA',
            'latitude' => null,
            'longitude' => null,
            'description' => null,
            'instagram' => null,
            'logo_path' => null,
            'active' => true,
            'created_by_account_id' => User::factory(),
        ];
    }
}
