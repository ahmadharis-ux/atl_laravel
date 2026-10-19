<?php

namespace Database\Factories;

use App\Enums\MerchandiseRedemptionStatus;
use App\Models\Merchandise;
use App\Models\MerchandiseRedemption;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MerchandiseRedemption>
 */
class MerchandiseRedemptionFactory extends Factory
{
    protected $model = MerchandiseRedemption::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'merchandise_id' => Merchandise::factory(),
            'points_cost' => fake()->numberBetween(10, 500),
            'status' => MerchandiseRedemptionStatus::Pending,
            'notes' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => MerchandiseRedemptionStatus::Completed]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => MerchandiseRedemptionStatus::Cancelled]);
    }
}
