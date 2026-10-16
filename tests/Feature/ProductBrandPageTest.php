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

    public function test_product_tab_is_the_default_and_hides_the_brand_directory(): void
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
            ->assertSee('Cari produk')
            ->assertSee('data-ajax-rows', false)
            ->assertSee('ML Diamonds')
            ->assertDontSee('Cari brand');
    }

    public function test_brand_tab_lists_brands(): void
    {
        $user = $this->user();
        $brand = Brand::factory()->create(['name' => 'Mobile Legends']);

        Product::factory()->create([
            'brand_id' => $brand->getKey(),
            'name' => 'ML Diamonds',
            'status' => true,
        ]);

        $this->actingAs($user)
            ->get(route('products.index', ['tab' => 'brand']))
            ->assertOk()
            ->assertSee('Cari brand')
            ->assertSee($brand->name)
            ->assertSee(route('products.brand', $brand), false)
            ->assertDontSee('Cari produk');
    }

    public function test_brand_tab_searchbox_keeps_the_brand_tab_on_submit(): void
    {
        $user = $this->user();
        $brand = Brand::factory()->create(['name' => 'Mobile Legends']);
        Product::factory()->create(['brand_id' => $brand->getKey(), 'name' => 'ML Diamonds', 'status' => true]);

        $this->actingAs($user)
            ->get(route('products.index', ['tab' => 'brand']))
            ->assertOk()
            ->assertSee('name="tab" value="brand"', false)
            ->assertSee(route('products.index', ['tab' => 'brand']), false);
    }

    public function test_brand_tab_searches_brands_by_name(): void
    {
        $user = $this->user();
        $ml = Brand::factory()->create(['name' => 'Mobile Legends']);
        $pubg = Brand::factory()->create(['name' => 'PUBG Mobile']);

        Product::factory()->create(['brand_id' => $ml->getKey(), 'name' => 'ML Diamonds', 'status' => true]);
        Product::factory()->create(['brand_id' => $pubg->getKey(), 'name' => 'UC PUBG', 'status' => true]);

        $this->actingAs($user)
            ->get(route('products.index', ['tab' => 'brand', 'q' => 'Legends']))
            ->assertOk()
            ->assertSee('Mobile Legends')
            ->assertDontSee('PUBG Mobile');
    }

    public function test_brand_tab_limits_brands_and_offers_show_all(): void
    {
        $user = $this->user();

        foreach (range(1, 30) as $i) {
            $brand = Brand::factory()->create(['name' => 'Brand '.str_pad((string) $i, 2, '0', STR_PAD_LEFT)]);
            Product::factory()->create(['brand_id' => $brand->getKey(), 'name' => 'Produk '.$i, 'status' => true]);
        }

        $limited = $this->actingAs($user)->get(route('products.index', ['tab' => 'brand']));
        $limited->assertOk()->assertSee('Lihat semua 30 brand');
        $this->assertSame(24, substr_count($limited->getContent(), 'bcard r-card'));

        $all = $this->actingAs($user)->get(route('products.index', ['tab' => 'brand', 'all' => 1]));
        $all->assertOk()->assertDontSee('Lihat semua');
        $this->assertSame(30, substr_count($all->getContent(), 'bcard r-card'));
    }

    public function test_brand_page_shows_nickname_check_and_checkout_links_for_validated_brand(): void
    {
        $user = $this->user();
        $brand = Brand::factory()->create(['slug' => 'mobile-legends']);
        $category = Category::factory()->create(['slug' => 'games']);

        Product::factory()->create([
            'brand_id' => $brand->getKey(),
            'category_id' => $category->getKey(),
            'name' => 'ML 112 Diamonds (Malaysia)',
            'status' => true,
            'region' => 'Malaysia',
        ]);

        $this->actingAs($user)
            ->get(route('products.brand', $brand))
            ->assertOk()
            ->assertSee('Cek ID Akun')
            ->assertSee('data-nickname-check', false)
            ->assertSee('data-checkout-link', false);
    }

    public function test_brand_page_without_validation_has_no_nickname_check(): void
    {
        $user = $this->user();
        $brand = Brand::factory()->create(['slug' => 'voucher-biasa-'.uniqid()]);

        Product::factory()->create([
            'brand_id' => $brand->getKey(),
            'name' => 'Voucher Nonton',
            'status' => true,
        ]);

        $this->actingAs($user)
            ->get(route('products.brand', $brand))
            ->assertOk()
            ->assertDontSee('data-nickname-check', false)
            ->assertDontSee('data-checkout-link', false);
    }
}
