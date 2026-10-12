<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhoneOperatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_detect_operator(): void
    {
        $this->getJson(route('products.detect-operator', ['number' => '081234567890']))
            ->assertUnauthorized();
    }

    public function test_detects_telkomsel_operator(): void
    {
        Brand::factory()->create(['slug' => 'telkomsel', 'name' => 'Telkomsel']);

        $this->actingAs(User::factory()->create())
            ->getJson(route('products.detect-operator', ['number' => '081234567890']))
            ->assertOk()
            ->assertJson([
                'status' => true,
                'operator' => 'Telkomsel',
                'brand_slug' => 'telkomsel',
            ]);
    }

    public function test_detects_byu_operator_with_sub_prefix(): void
    {
        Brand::factory()->create(['slug' => 'byu', 'name' => 'by.U']);

        $this->actingAs(User::factory()->create())
            ->getJson(route('products.detect-operator', ['number' => '085156789012']))
            ->assertOk()
            ->assertJson([
                'status' => true,
                'brand_slug' => 'byu',
            ]);
    }

    public function test_returns_error_for_unknown_operator(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route('products.detect-operator', ['number' => '07900000000']))
            ->assertOk()
            ->assertJson([
                'status' => false,
            ]);
    }

    public function test_detect_operator_returns_the_brand_logo_url(): void
    {
        Storage::fake('brands');
        Storage::disk('brands')->put('telkomsel.png', 'x');

        Brand::factory()->create(['slug' => 'telkomsel', 'name' => 'Telkomsel', 'logo' => 'telkomsel.png']);

        $response = $this->actingAs(User::factory()->create())
            ->getJson(route('products.detect-operator', ['number' => '081234567890']))
            ->assertOk();

        $this->assertStringContainsString('telkomsel.png', (string) $response->json('brand_logo'));
    }

    public function test_pulsa_products_are_not_listed_in_the_general_catalog(): void
    {
        $pulsa = Category::factory()->create(['slug' => 'pulsa', 'name' => 'Pulsa']);
        $telkomsel = Brand::factory()->create(['slug' => 'telkomsel', 'name' => 'Telkomsel']);

        Product::factory()->create([
            'name' => 'Telkomsel Pulsa 50K',
            'category_id' => $pulsa->getKey(),
            'brand_id' => $telkomsel->getKey(),
            'status' => true,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('products.index', ['category' => $pulsa->getKey()]))
            ->assertOk()
            ->assertDontSee('Telkomsel Pulsa 50K')
            ->assertSee('Produk tidak ditemukan');
    }
}
