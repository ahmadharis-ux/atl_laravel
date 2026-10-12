<?php

namespace Tests\Feature;

use App\Jobs\BotDigiflazzScrapeJob;
use App\Models\BotDigiflazzConfig;
use App\Models\BotDigiflazzProduct;
use App\Models\BotDigiflazzSyncLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BotDigiflazzSelectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    private function admin(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = ['dashboard.view', 'products.view', 'products.update'];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user = User::factory()->create(['is_admin' => true]);
        $user->givePermissionTo($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    private function config(array $scraperConfig = []): BotDigiflazzConfig
    {
        return BotDigiflazzConfig::create([
            'name' => 'Scraper Utama',
            'cookies_content' => 'session=abc',
            'csrf_token' => 'csrf-token',
            'xsrf_token' => 'xsrf-token',
            'is_active' => true,
            'scraper_config' => $scraperConfig,
        ]);
    }

    public function test_options_endpoint_returns_categories_from_api(): void
    {
        $this->config();

        Http::fake([
            '*buyer/product/category' => Http::response(['data' => [
                ['id' => 1, 'name' => 'Pulsa'],
                ['id' => 2, 'name' => 'Paket Data'],
            ]]),
        ]);

        $response = $this->actingAs($this->admin())
            ->getJson(route('admin.bot.digiflazz.options', ['type' => 'categories']));

        $response->assertOk()
            ->assertJsonFragment(['value' => '1', 'text' => 'Pulsa'])
            ->assertJsonFragment(['value' => '2', 'text' => 'Paket Data']);
    }

    public function test_options_endpoint_returns_empty_without_active_config(): void
    {
        $this->actingAs($this->admin())
            ->getJson(route('admin.bot.digiflazz.options', ['type' => 'categories']))
            ->assertOk()
            ->assertJson(['results' => []]);
    }

    public function test_store_merges_selection_into_scraper_config(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.bot.digiflazz.store'), [
            'name' => 'Baru',
            'cookies_content' => 'session=abc',
            'csrf_token' => 'csrf',
            'is_active' => '1',
            'scraper_config' => json_encode(['pattern_mode' => 'AND']),
            'categories' => ['1', '2'],
            'brands' => ['10'],
            'types' => [],
        ]);

        $response->assertRedirect(route('admin.bot.digiflazz.index'));

        $config = BotDigiflazzConfig::firstOrFail();
        $this->assertSame(['1', '2'], $config->scraper_config['categories']);
        $this->assertSame(['10'], $config->scraper_config['brands']);
        $this->assertArrayNotHasKey('types', $config->scraper_config);
        $this->assertSame('AND', $config->scraper_config['pattern_mode']);
    }

    public function test_job_fetches_products_by_selected_category_and_filters_brand(): void
    {
        $config = $this->config([
            'categories' => ['1'],
            'brands' => ['10'],
            'description_patterns' => [],
            'no_seller_action' => 'skip',
        ]);

        Http::fake([
            '*buyer/product/category/1/' => Http::response(['data' => [
                ['id' => 101, 'code' => 'A', 'product' => 'Produk A', 'brand_id' => 10, 'type_id' => 20],
                ['id' => 102, 'code' => 'B', 'product' => 'Produk B', 'brand_id' => 99, 'type_id' => 20],
            ]]),
            '*buyer/product/seller/*' => Http::response(['data' => [[
                'id' => 500,
                'id_int' => 5000,
                'seller' => 'SellerX',
                'seller_sku_code' => 'SKU-1',
                'connectionType' => 'api',
                'seller_details' => [],
                'price' => 9000,
                'stock' => 5,
                'unlimited_stock' => false,
                'start_cut_off' => '00:00',
                'end_cut_off' => '23:59',
                'faktur' => false,
                'multi' => false,
                'multi_counter' => 0,
                'status' => true,
                'deskripsi' => 'Stok aman',
                'is_seller_favorite' => false,
                'reviewAvg' => 5,
                'rating_qty' => 100,
            ]]]),
            '*buyer/product/category' => Http::response(['data' => []]),
            '*buyer/product' => Http::response([], 200),
        ]);

        (new BotDigiflazzScrapeJob($config->id))->handle();

        $products = BotDigiflazzProduct::where('config_id', $config->id)->pluck('digiflazz_product_id');

        $this->assertCount(1, $products);
        $this->assertSame('101', (string) $products->first());
        $this->assertSame('completed', BotDigiflazzSyncLog::firstOrFail()->status);
    }

    public function test_job_without_selection_uses_all_products_endpoint(): void
    {
        $config = $this->config(['description_patterns' => [], 'no_seller_action' => 'skip']);

        Http::fake([
            '*buyer/product/closed' => Http::response(['data' => [
                ['id' => 201, 'code' => 'C', 'product' => 'Produk C'],
            ]]),
            '*buyer/product/seller/*' => Http::response(['data' => [[
                'id' => 501,
                'id_int' => 5001,
                'seller' => 'SellerY',
                'seller_sku_code' => 'SKU-2',
                'connectionType' => 'api',
                'seller_details' => [],
                'price' => 8000,
                'stock' => 3,
                'unlimited_stock' => false,
                'start_cut_off' => '00:00',
                'end_cut_off' => '23:59',
                'faktur' => false,
                'multi' => false,
                'multi_counter' => 0,
                'status' => true,
                'deskripsi' => 'Stok aman',
                'is_seller_favorite' => false,
                'reviewAvg' => 5,
                'rating_qty' => 50,
            ]]]),
            '*buyer/product' => Http::response([], 200),
        ]);

        (new BotDigiflazzScrapeJob($config->id))->handle();

        $this->assertSame(1, BotDigiflazzProduct::where('config_id', $config->id)->count());
        Http::assertSent(fn ($request) => str_contains($request->url(), '/buyer/product/closed'));
    }
}
