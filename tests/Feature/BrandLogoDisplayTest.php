<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandLogoDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_card_renders_the_brand_logo_when_available(): void
    {
        Storage::fake('brands');
        Storage::disk('brands')->put('telkomsel.png', 'x');

        $brand = Brand::factory()->create([
            'slug' => 'telkomsel',
            'name' => 'Telkomsel',
            'logo' => 'telkomsel.png',
        ]);

        $product = Product::factory()->create(['brand_id' => $brand->getKey()]);

        $html = view('user.product._card', ['p' => $product->load('brand', 'category')])->render();

        $this->assertStringContainsString('pcard-logo', $html);
        $this->assertStringContainsString('telkomsel.png', $html);
    }

    public function test_product_card_falls_back_to_the_category_icon_without_a_logo(): void
    {
        $brand = Brand::factory()->create(['slug' => 'telkomsel', 'name' => 'Telkomsel']);

        $product = Product::factory()->create(['brand_id' => $brand->getKey()]);

        $html = view('user.product._card', ['p' => $product->load('brand', 'category')])->render();

        $this->assertStringNotContainsString('pcard-logo', $html);
        $this->assertStringContainsString('<span class="ms ', $html);
    }
}
