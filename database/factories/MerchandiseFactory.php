<?php

namespace Database\Factories;

use App\Models\Merchandise;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Merchandise>
 */
class MerchandiseFactory extends Factory
{
    protected $model = Merchandise::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'points_cost' => fake()->numberBetween(10, 500),
            'stock' => fake()->numberBetween(1, 20),
            'image' => null,
            'is_active' => true,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes): array => ['stock' => 0]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }
}
