<?php

namespace Database\Factories;

use App\Models\HomeownerProfile;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\RequestStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'homeowner_id' => HomeownerProfile::factory(),
            'service_id' => Service::factory(),
            'title' => ucfirst(fake()->words(4, true)),
            'description' => fake()->paragraph(),
            'service_location' => fake()->streetAddress(),
            'service_city' => 'Casablanca',
            'desired_start_date' => now()->addDays(fake()->numberBetween(3, 30))->toDateString(),
            'status' => RequestStatus::Open,
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RequestStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
