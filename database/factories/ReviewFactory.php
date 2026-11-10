<?php

namespace Database\Factories;

use App\Models\Offer;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'offer_id' => Offer::factory()->accepted(),
            'rating' => fake()->numberBetween(3, 5),
            'comment' => fake()->sentence(12),
        ];
    }
}
