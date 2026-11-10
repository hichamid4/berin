<?php

namespace Database\Factories;

use App\Models\HandymanProfile;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HandymanProfile>
 */
class HandymanProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->role(UserRole::Handyman),
            'address' => fake()->streetAddress(),
            'city' => 'Casablanca',
            'photo_path' => null,
        ];
    }
}
