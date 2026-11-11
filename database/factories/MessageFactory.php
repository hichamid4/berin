<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Message sent by the conversation's handyman to its homeowner unless overridden.
 *
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sender_id' => fn (array $attributes): int => Conversation::query()->findOrFail($attributes['conversation_id'])->handyman_id,
            'recipient_id' => fn (array $attributes): int => Conversation::query()->findOrFail($attributes['conversation_id'])->homeowner_id,
            'client_message_id' => fn (): string => (string) Str::uuid(),
            'body' => fake()->sentence(),
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes): array => ['read_at' => now()]);
    }
}
