<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductBrandPageTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create();
    }

    public function test_brand_page_requires_auth(): void
    {
        $brand = Brand::factory()->create();

        $this->get(route('products.brand', $brand))->assertRedirect();
    }

    public function test_brand_page_renders_with_products(): void
    {
        $user = $this->user();
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();

        Product::factory()->create([
            'brand_id' => $brand->getKey(),
            'category_id' => $category->getKey(),
            'name' => 'Diamonds 100',
            'status' => true,
            'region' => 'Malaysia',
        ]);

        $this->actingAs($user)
            ->get(route('products.brand', $brand))
            ->assertOk()
            ->assertSee($brand->name)
            ->assertSee('Diamonds 100');
    }

    public function test_brand_page_filters_by_region(): void
    {
        $user = $this->user();
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();

        Product::factory()->create([
            'brand_id' => $brand->getKey(),
            'category_id' => $category->getKey(),
            'name' => 'Diamonds Malaysia',
            'status' => true,
            'region' => 'Malaysia',
        ]);

        Product::factory()->create([
            'brand_id' => $brand->getKey(),
            'category_id' => $category->getKey(),
            'name' => 'Diamonds Global',
            'status' => true,
            'region' => 'Global',
        ]);

        $response = $this->actingAs($user)
            ->get(route('products.brand', $brand).'?region=Malaysia');

        $response->assertOk()->assertSee('Diamonds Malaysia')->assertDontSee('Diamonds Global');
    }

    public function test_brand_cards_on_catalog_index(): void
    {
        $user = $this->user();
        $brand = Brand::factory()->create(['name' => 'Mobile Legends']);

        Product::factory()->create([
            'brand_id' => $brand->getKey(),
            'name' => 'ML Diamonds',
            'status' => true,
        ]);

        $this->actingAs($user)
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('Pilih Brand')
            ->assertSee($brand->name)
            ->assertSee(route('products.brand', $brand), false);
    }
}
