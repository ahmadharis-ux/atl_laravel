<?php

namespace Database\Factories;

use App\Models\MasterBank;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MasterBank>
 */
class MasterBankFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => MasterBank::TYPE_BANK,
            'bank_name' => fake()->randomElement(['BCA', 'BNI', 'BRI', 'Mandiri', 'CIMB Niaga']),
            'account_number' => (string) fake()->unique()->numerify('##########'),
            'account_holder' => fake()->company(),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }

    public function ewallet(): static
    {
        return $this->state(fn (): array => [
            'type' => MasterBank::TYPE_EWALLET,
            'bank_name' => fake()->randomElement(['GoPay', 'OVO', 'DANA', 'ShopeePay', 'LinkAja']),
        ]);
    }
}
