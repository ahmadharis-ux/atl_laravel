<?php

namespace Database\Factories;

use App\Enums\MarkupType;
use App\Enums\ProductPaymentType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Type;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'external_id' => Str::upper(Str::random(6)),
            'code' => 'sku-'.fake()->unique()->numerify('########'),
            'name' => Str::title(fake()->words(3, true)),
            'payment_type' => ProductPaymentType::Prabayar,
            'brand_id' => Brand::factory(),
            'category_id' => Category::factory(),
            'type_id' => Type::factory(),
            'seller_id' => Seller::factory(),
            'description' => fake()->sentence(),
            'cost_price' => fake()->numberBetween(5_000, 500_000),
            'max_price' => 0,
            'markup_type' => MarkupType::Fixed,
            'markup_value' => fake()->numberBetween(500, 5_000),
            'stock' => fake()->numberBetween(0, 100),
            'unlimited_stock' => fake()->boolean(),
            'faktur' => fake()->boolean(20),
            'multi' => fake()->boolean(30),
            'multi_counter' => fake()->numberBetween(1, 999),
            'status' => fake()->boolean(80),
            'sort_order' => fake()->numberBetween(0, 50),
        ];
    }

    public function prabayar(): static
    {
        return $this->state(fn () => ['payment_type' => ProductPaymentType::Prabayar]);
    }

    public function pascabayar(): static
    {
        return $this->state(fn () => ['payment_type' => ProductPaymentType::Pascabayar]);
    }
}
