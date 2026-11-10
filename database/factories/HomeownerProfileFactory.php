<?php

namespace Database\Factories;

use App\Models\HomeownerProfile;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HomeownerProfile>
 */
class HomeownerProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->role(UserRole::Homeowner),
            'address' => fake()->streetAddress(),
            'city' => 'Casablanca',
        ];
    }
}
