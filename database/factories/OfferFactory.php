<?php

namespace Database\Factories;

use App\Models\HandymanProfile;
use App\Models\Offer;
use App\Models\ServiceRequest;
use App\OfferStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->addDays(fake()->numberBetween(2, 20));

        return [
            'request_id' => ServiceRequest::factory(),
            'handyman_id' => HandymanProfile::factory(),
            'price' => fake()->numberBetween(150, 9000),
            'currency' => 'MAD',
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDays(fake()->numberBetween(0, 5))->toDateString(),
            'description' => fake()->paragraph(),
            'status' => OfferStatus::Pending,
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OfferStatus::Accepted,
            'accepted_at' => now(),
        ]);
    }
}
