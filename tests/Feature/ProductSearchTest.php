<?php

namespace Tests\Feature;

use App\Enums\MarkupType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_finds_postpaid_products_without_choosing_the_tab(): void
    {
        $user = User::factory()->create();
        Product::factory()->pascabayar()->create(['name' => 'Tagihan PDAM', 'status' => true]);

        $this->actingAs($user)->get('/produk?q=pdam')
            ->assertOk()
            ->assertSee('Tagihan PDAM')
            ->assertDontSee('Produk tidak ditemukan');
    }

    public function test_search_ignores_the_active_payment_type_tab(): void
    {
        $user = User::factory()->create();
        Product::factory()->pascabayar()->create(['name' => 'Tagihan PDAM', 'status' => true]);

        $this->actingAs($user)->get('/produk?q=pdam&payment_type=prabayar')
            ->assertOk()
            ->assertSee('Tagihan PDAM');
    }

    public function test_browsing_without_a_query_filters_by_payment_type(): void
    {
        $user = User::factory()->create();
        Product::factory()->prabayar()->create(['name' => 'Pulsa Telkomsel', 'status' => true]);
        Product::factory()->pascabayar()->create(['name' => 'Tagihan PDAM', 'status' => true]);

        $this->actingAs($user)->get('/produk')
            ->assertOk()
            ->assertSee('Pulsa Telkomsel')
            ->assertDontSee('Tagihan PDAM');
    }

    public function test_ajax_search_returns_tabs_fragment(): void
    {
        $user = User::factory()->create();
        Product::factory()->pascabayar()->create(['name' => 'Tagihan PDAM', 'status' => true]);

        $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get('/produk?q=pdam')
            ->assertOk()
            ->assertJsonStructure(['rows', 'tabs', 'footer'])
            ->assertJsonPath('tabs', fn (string $tabs): bool => str_contains($tabs, 'Semua jenis'));
    }

    public function test_filter_is_rendered_as_a_modal_triggered_by_a_button(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/produk')
            ->assertOk()
            ->assertSee('data-modal-open="modalFilter"', false)
            ->assertSee('id="modalFilter"', false)
            ->assertSee('name="category"', false)
            ->assertSee('name="brand"', false)
            ->assertSee('name="min_price"', false)
            ->assertSee('name="max_price"', false)
            ->assertDontSee('data-ajax-filter-toggle', false);
    }

    public function test_filters_products_by_price_range(): void
    {
        $user = User::factory()->create();

        Product::factory()->prabayar()->create([
            'name' => 'Produk Murah', 'status' => true,
            'cost_price' => 10_000, 'markup_type' => MarkupType::Fixed, 'markup_value' => 0,
        ]);
        Product::factory()->prabayar()->create([
            'name' => 'Produk Mahal', 'status' => true,
            'cost_price' => 100_000, 'markup_type' => MarkupType::Fixed, 'markup_value' => 0,
        ]);
        Product::factory()->prabayar()->create([
            'name' => 'Produk Persen', 'status' => true,
            'cost_price' => 10_000, 'markup_type' => MarkupType::Percent, 'markup_value' => 50,
        ]);

        $this->actingAs($user)->get('/produk?min_price=50.000&max_price=200.000')
            ->assertOk()
            ->assertSee('Produk Mahal')
            ->assertDontSee('Produk Murah')
            ->assertDontSee('Produk Persen');

        $this->actingAs($user)->get('/produk?max_price=15000')
            ->assertOk()
            ->assertSee('Produk Murah')
            ->assertSee('Produk Persen')
            ->assertDontSee('Produk Mahal');
    }

    public function test_search_box_has_an_inline_clear_button(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/produk')
            ->assertOk()
            ->assertSee('ui-search-clear', false)
            ->assertSee('data-search-clear', false);
    }

    public function test_product_list_uses_load_more_with_sixteen_per_page(): void
    {
        $user = User::factory()->create();
        Product::factory()->prabayar()->count(20)->create(['status' => true]);

        $response = $this->actingAs($user)->get('/produk');

        $response->assertOk()
            ->assertSee('Muat lebih banyak')
            ->assertSee('dari 20 produk');

        $this->assertSame(16, substr_count($response->getContent(), 'pcard r-card'));
    }

    public function test_catalog_hides_pulsa_and_data_products(): void
    {
        $user = User::factory()->create();

        $pulsa = Category::factory()->create(['slug' => 'pulsa', 'name' => 'Pulsa']);
        $data = Category::factory()->create(['slug' => 'data', 'name' => 'Paket Data']);
        $games = Category::factory()->create(['slug' => 'games', 'name' => 'Games']);

        Product::factory()->prabayar()->create(['name' => 'Pulsa Telkomsel 50K', 'category_id' => $pulsa->getKey(), 'status' => true]);
        Product::factory()->prabayar()->create(['name' => 'Telkomsel 10GB', 'category_id' => $data->getKey(), 'status' => true]);
        Product::factory()->prabayar()->create(['name' => 'Diamond ML', 'category_id' => $games->getKey(), 'status' => true]);

        $this->actingAs($user)->get('/produk')
            ->assertOk()
            ->assertSee('Diamond ML')
            ->assertDontSee('Pulsa Telkomsel 50K')
            ->assertDontSee('Telkomsel 10GB');
    }

    public function test_search_hides_pulsa_and_data_products(): void
    {
        $user = User::factory()->create();

        $pulsa = Category::factory()->create(['slug' => 'pulsa', 'name' => 'Pulsa']);
        Product::factory()->prabayar()->create(['name' => 'Telkomsel Pulsa 100K', 'category_id' => $pulsa->getKey(), 'status' => true]);

        $this->actingAs($user)->get('/produk?q=telkomsel')
            ->assertOk()
            ->assertDontSee('Telkomsel Pulsa 100K')
            ->assertSee('Produk tidak ditemukan');
    }

    public function test_category_lookup_hides_pulsa_and_data(): void
    {
        $user = User::factory()->create();

        Category::factory()->create(['slug' => 'pulsa', 'name' => 'Pulsa']);
        Category::factory()->create(['slug' => 'data', 'name' => 'Paket Data']);
        Category::factory()->create(['slug' => 'games', 'name' => 'Games']);

        $this->actingAs($user)
            ->getJson(route('products.lookup', ['type' => 'categories']))
            ->assertOk()
            ->assertJsonMissing(['text' => 'Pulsa'])
            ->assertJsonMissing(['text' => 'Paket Data'])
            ->assertJsonFragment(['text' => 'Games']);
    }

    public function test_product_lookup_hides_pulsa_and_data(): void
    {
        $user = User::factory()->create();

        $pulsa = Category::factory()->create(['slug' => 'pulsa', 'name' => 'Pulsa']);
        Product::factory()->prabayar()->create(['name' => 'Pulsa Telkomsel 50K', 'category_id' => $pulsa->getKey(), 'status' => true]);
        Product::factory()->prabayar()->create(['name' => 'Diamond ML', 'status' => true]);

        $this->actingAs($user)
            ->getJson(route('products.lookup', ['type' => 'products', 'q' => 'a']))
            ->assertOk()
            ->assertJsonMissing(['text' => 'Pulsa Telkomsel 50K'])
            ->assertJsonFragment(['text' => 'Diamond ML']);
    }

    public function test_brand_lookup_follows_the_selected_category(): void
    {
        $user = User::factory()->create();

        $games = Category::factory()->create(['slug' => 'games', 'name' => 'Games']);
        $voucher = Category::factory()->create(['slug' => 'voucher', 'name' => 'Voucher']);

        $mobileLegends = Brand::factory()->create(['name' => 'Mobile Legends']);
        $pubg = Brand::factory()->create(['name' => 'PUBG']);
        $steam = Brand::factory()->create(['name' => 'Steam']);

        Product::factory()->prabayar()->create(['name' => 'ML 10 Diamond', 'category_id' => $games->getKey(), 'brand_id' => $mobileLegends->getKey(), 'status' => true]);
        Product::factory()->prabayar()->create(['name' => 'PUBG 60 UC', 'category_id' => $games->getKey(), 'brand_id' => $pubg->getKey(), 'status' => true]);
        Product::factory()->prabayar()->create(['name' => 'Steam Wallet', 'category_id' => $voucher->getKey(), 'brand_id' => $steam->getKey(), 'status' => true]);

        $this->actingAs($user)
            ->getJson(route('products.lookup', ['type' => 'brands', 'category' => $games->getKey()]))
            ->assertOk()
            ->assertJsonFragment(['text' => 'Mobile Legends'])
            ->assertJsonFragment(['text' => 'PUBG'])
            ->assertJsonMissing(['text' => 'Steam']);
    }

    public function test_dashboard_best_sellers_hide_pulsa_and_data(): void
    {
        $user = User::factory()->create();

        $pulsa = Category::factory()->create(['slug' => 'pulsa', 'name' => 'Pulsa']);
        Product::factory()->prabayar()->create(['name' => 'Pulsa Telkomsel 50K', 'category_id' => $pulsa->getKey(), 'status' => true, 'sold_count' => 99]);
        Product::factory()->prabayar()->create(['name' => 'Diamond ML', 'status' => true, 'sold_count' => 1]);

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSee('Diamond ML')
            ->assertDontSee('Pulsa Telkomsel 50K');
    }
}
