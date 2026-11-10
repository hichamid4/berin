<?php

namespace Database\Factories;

use App\ContactMessageStatus;
use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'subject' => fake()->sentence(5),
            'message' => fake()->paragraph(3),
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ContactMessageStatus::Read,
            'read_at' => now(),
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ContactMessageStatus::Resolved,
            'read_at' => now(),
            'resolved_at' => now(),
        ]);
    }
}
