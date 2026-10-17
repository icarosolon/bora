<?php

namespace Database\Factories;

use App\Enums\VenueClaimStatus;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueClaim;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VenueClaim>
 */
class VenueClaimFactory extends Factory
{
    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'account_id' => User::factory(),
            'status' => VenueClaimStatus::Pending,
            'requester_name' => fake()->name(),
            'requester_role' => 'Proprietário',
            'best_contact_time' => 'Após as 18h',
            'ask_for' => fake()->firstName(),
            'decided_by_account_id' => null,
            'decided_at' => null,
            'decision_reason' => null,
            'verification_method' => null,
        ];
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => VenueClaimStatus::Rejected,
            'decided_by_account_id' => User::factory(),
            'decided_at' => now(),
            'decision_reason' => 'Não foi possível confirmar o vínculo.',
            'verification_method' => 'manual',
        ]);
    }
}
