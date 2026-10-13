<?php

namespace Tests\Feature;

use App\Jobs\BotDigiflazzScrapeJob;
use App\Models\BotDigiflazzConfig;
use App\Models\BotDigiflazzProduct;
use App\Models\BotDigiflazzSeller;
use App\Models\BotDigiflazzSyncLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Perilaku bot Digiflazz mengikuti script `produk.php`:
 * STEP 1 scrape produk closed → STEP 2+3 ambil seller → pilih termurah → update.
 */
class BotDigiflazzScrapeJobTest extends TestCase
{
    use RefreshDatabase;

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

    /**
     * @param  array<string, mixed>  $seller
     * @return array<string, mixed>
     */
    private function seller(int $id, string $name, float $price, string $deskripsi = 'Stok aman', bool $favorite = false, bool $multi = false): array
    {
        return [
            'id' => $id,
            'id_int' => $id * 10,
            'seller' => $name,
            'seller_sku_code' => 'SKU-'.$id,
            'connectionType' => 'api',
            'seller_details' => [],
            'price' => $price,
            'stock' => 5,
            'unlimited_stock' => false,
            'start_cut_off' => '00:00',
            'end_cut_off' => '23:59',
            'faktur' => false,
            'multi' => $multi,
            'multi_counter' => 0,
            'status' => true,
            'deskripsi' => $deskripsi,
            'is_seller_favorite' => $favorite,
            'reviewAvg' => 5,
            'rating_qty' => 100,
        ];
    }

    private function isProductUpdate(object $request): bool
    {
        $path = parse_url($request->url(), PHP_URL_PATH) ?? '';

        return $request->method() === 'POST' && str_ends_with($path, '/buyer/product');
    }

    public function test_job_scrapes_products_then_updates_cheapest_seller(): void
    {
        $config = $this->config(['description_patterns' => [], 'no_seller_action' => 'skip']);

        Http::fake([
            '*buyer/product/closed' => Http::response(['data' => [
                ['id' => 101, 'code' => 'A', 'product' => 'Produk A'],
            ]]),
            '*buyer/product/seller/*' => Http::response(['data' => [
                $this->seller(1, 'SellerMahal', 9000),
                $this->seller(2, 'SellerMurah', 7000),
            ]]),
            '*buyer/product/delete/*' => Http::response([], 200),
            '*buyer/product' => Http::response([], 200),
        ]);

        (new BotDigiflazzScrapeJob($config->id))->handle();

        $this->assertSame(1, BotDigiflazzProduct::where('config_id', $config->id)->count());
        $this->assertSame(2, BotDigiflazzSeller::where('config_id', $config->id)->count());
        $this->assertSame('completed', BotDigiflazzSyncLog::firstOrFail()->status);

        Http::assertSent(fn ($request) => $this->isProductUpdate($request) && $request['seller'] === 'SellerMurah');
    }

    public function test_job_uses_pattern_filter_to_pick_matching_seller(): void
    {
        $config = $this->config([
            'description_patterns' => ['/100%/i'],
            'pattern_mode' => 'OR',
            'prioritize_favorite' => false,
            'no_seller_action' => 'skip',
        ]);

        Http::fake([
            '*buyer/product/closed' => Http::response(['data' => [
                ['id' => 102, 'code' => 'B', 'product' => 'Produk B'],
            ]]),
            '*buyer/product/seller/*' => Http::response(['data' => [
                $this->seller(3, 'MurahNoMatch', 1000, 'fast'),
                $this->seller(4, 'MahalMatch', 5000, 'stok 100% ready'),
            ]]),
            '*buyer/product' => Http::response([], 200),
        ]);

        (new BotDigiflazzScrapeJob($config->id))->handle();

        Http::assertSent(fn ($request) => $this->isProductUpdate($request) && $request['seller'] === 'MahalMatch');
    }

    public function test_job_skips_product_without_sellers_by_default(): void
    {
        $config = $this->config(['description_patterns' => []]);

        Http::fake([
            '*buyer/product/closed' => Http::response(['data' => [
                ['id' => 103, 'code' => 'C', 'product' => 'Produk C'],
            ]]),
            '*buyer/product/seller/*' => Http::response(['data' => []]),
            '*buyer/product/delete/*' => Http::response([], 200),
            '*buyer/product' => Http::response([], 200),
        ]);

        (new BotDigiflazzScrapeJob($config->id))->handle();

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/buyer/product/delete/'));
        Http::assertNotSent(fn ($request) => $this->isProductUpdate($request));
    }

    public function test_job_deletes_product_without_sellers_when_configured(): void
    {
        $config = $this->config(['description_patterns' => [], 'no_seller_action' => 'delete']);

        Http::fake([
            '*buyer/product/closed' => Http::response(['data' => [
                ['id' => 104, 'code' => 'D', 'product' => 'Produk D'],
            ]]),
            '*buyer/product/seller/*' => Http::response(['data' => []]),
            '*buyer/product/delete/*' => Http::response([], 200),
            '*buyer/product' => Http::response([], 200),
        ]);

        (new BotDigiflazzScrapeJob($config->id))->handle();

        Http::assertSent(fn ($request) => $request->method() === 'POST' && str_contains($request->url(), '/buyer/product/delete/104'));
    }

    public function test_job_deletes_duplicate_product_on_400(): void
    {
        $config = $this->config(['description_patterns' => [], 'no_seller_action' => 'skip']);

        Http::fake([
            '*buyer/product/closed' => Http::response(['data' => [
                ['id' => 105, 'code' => 'E', 'product' => 'Produk E'],
            ]]),
            '*buyer/product/seller/*' => Http::response(['data' => [
                $this->seller(9, 'SellerZ', 7000),
            ]]),
            '*buyer/product/delete/*' => Http::response([], 200),
            '*buyer/product' => Http::response(['message' => 'Produk dan Seller sudah ada sebelumnya'], 400),
        ]);

        (new BotDigiflazzScrapeJob($config->id))->handle();

        Http::assertSent(fn ($request) => $request->method() === 'POST' && str_contains($request->url(), '/buyer/product/delete/105'));
    }
}
