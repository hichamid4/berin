<?php

namespace Database\Factories;

use App\Models\ActivityEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityEvent>
 */
class ActivityEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_user_id' => User::factory(),
            'action' => 'user.registered',
            'subject_type' => 'user',
            'subject_id' => null,
            'metadata' => null,
            'created_at' => now(),
        ];
    }
}
