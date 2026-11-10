<?php

namespace Database\Factories;

use App\Actions\Accounts\AdministratorTwoFactor;
use App\Models\TwoFactorCredential;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use PragmaRX\Google2FA\Google2FA;

/**
 * @extends Factory<TwoFactorCredential>
 */
class TwoFactorCredentialFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->role(UserRole::Administrator),
            'secret' => (new Google2FA)->generateSecretKey(32),
            'recovery_codes' => [],
            'confirmed_at' => now(),
        ];
    }

    public function unconfirmed(): static
    {
        return $this->state(fn (array $attributes): array => ['confirmed_at' => null]);
    }

    /**
     * @param  list<string>  $plainCodes
     */
    public function withRecoveryCodes(array $plainCodes): static
    {
        return $this->state(fn (array $attributes): array => [
            'recovery_codes' => array_map(AdministratorTwoFactor::hashRecoveryCode(...), $plainCodes),
        ]);
    }
}
