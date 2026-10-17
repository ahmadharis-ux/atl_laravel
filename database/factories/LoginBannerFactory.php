<?php

namespace Database\Factories;

use App\Models\LoginBanner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoginBanner>
 */
class LoginBannerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'body' => fake()->sentence(),
            'image' => null,
            'link' => null,
            'cta_label' => 'Selengkapnya',
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 10),
            'starts_at' => null,
            'ends_at' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
