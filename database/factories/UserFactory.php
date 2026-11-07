<?php

namespace Database\Factories;

use App\Models\User;
use App\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'role' => UserRole::Homeowner,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'phone_e164' => null,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Test-password-2026!'),
            'status' => 'active',
            'remember_token' => Str::random(60),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => ['email_verified_at' => null]);
    }

    public function role(UserRole $role): static
    {
        return $this->state(fn (array $attributes): array => ['role' => $role]);
    }

    public function blocked(int $blockedBy): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'blocked',
            'blocked_at' => now(),
            'blocked_by' => $blockedBy,
        ]);
    }
}
