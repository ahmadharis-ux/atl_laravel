<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public const DEFAULT_PIN = '482916';

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    protected static ?string $pinHash;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'pin_hash' => static::$pinHash ??= Hash::make(self::DEFAULT_PIN),
            'pin_set_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function withoutPin(): static
    {
        return $this->state(fn (array $attributes) => [
            'pin_hash' => null,
            'pin_set_at' => null,
            'pin_attempts' => 0,
            'pin_locked_until' => null,
            'pin_reset_at' => null,
        ]);
    }

    public function withPin(?string $pin = null): static
    {
        $pin ??= self::DEFAULT_PIN;

        return $this->state(fn (array $attributes) => [
            'pin_hash' => Hash::make($pin),
            'pin_set_at' => now(),
            'pin_attempts' => 0,
            'pin_locked_until' => null,
        ]);
    }
}
