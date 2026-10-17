<?php

namespace Database\Factories;

use App\Models\DashboardBanner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DashboardBanner>
 */
class DashboardBannerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'badge' => 'FLASH PROMO',
            'title' => fake()->sentence(4),
            'subtitle' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'image' => null,
            'link' => null,
            'cta_label' => 'Klaim Promo',
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
