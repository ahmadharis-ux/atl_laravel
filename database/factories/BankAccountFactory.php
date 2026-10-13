<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankAccount>
 */
class BankAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'bank_name' => fake()->randomElement(['BCA', 'BNI', 'BRI', 'Mandiri', 'CIMB Niaga']),
            'account_number' => (string) fake()->numerify('##########'),
            'account_holder' => fake()->name(),
            'is_primary' => false,
        ];
    }
}
