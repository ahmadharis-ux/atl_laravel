<?php

namespace App\Jobs;

use App\Models\BotDigiflazzConfig;
use App\Models\BotDigiflazzProduct;
use App\Models\BotDigiflazzSeller;
use App\Models\BotDigiflazzSyncLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class BotDigiflazzScrapeJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $configId,
        public ?array $overrideConfig = null
    ) {}

    public function handle(): void
    {
        $config = BotDigiflazzConfig::findOrFail($this->configId);
        $jobId = (string) Str::uuid();

        $job = BotDigiflazzSyncLog::createJob($jobId, $config->id, $config->scraper_config ?? []);

        try {
            $client = $config->getClient();
            $scraperConfig = array_merge($config->scraper_config ?? [], $this->overrideConfig ?? []);

            $job->addLog('info', 'Memulai scraping produk Digiflazz...');

            $categories = array_values(array_filter((array) ($scraperConfig['categories'] ?? []), fn ($v) => $v !== '' && $v !== null));
            $brands = array_values(array_filter((array) ($scraperConfig['brands'] ?? []), fn ($v) => $v !== '' && $v !== null));
            $types = array_values(array_filter((array) ($scraperConfig['types'] ?? []), fn ($v) => $v !== '' && $v !== null));

            // STEP 1: Scrape products (semua kategori, atau kategori terpilih)
            if (! empty($categories)) {
                $job->addLog('info', 'STEP 1: Mengambil produk untuk '.count($categories).' kategori terpilih...');
                $products = $client->fetchProductsByCategories(array_values($categories));
            } else {
                $job->addLog('info', 'STEP 1: Scraping daftar produk closed (semua kategori)...');
                $products = $client->scrapeProducts();
            }

            if (! empty($brands) || ! empty($types)) {
                $products = $client->filterProductsByBrandAndType($products, $brands, $types);
                $job->addLog('info', 'Filter brand/type diterapkan: '.count($products).' produk tersisa');
            }

            if (empty($products)) {
                $job->addLog('error', 'Tidak ada produk ditemukan. Proses dihentikan.');
                $job->markFailed('Tidak ada produk ditemukan');

                return;
            }

            $job->addLog('info', 'Total produk ditemukan: '.count($products));

            // Save products to DB
            $productMap = $this->saveProducts($config->id, $products);
            $job->addLog('info', 'Produk disimpan ke database: '.count($productMap));

            // STEP 2: Scrape seller per produk
            $job->addLog('info', 'STEP 2: Scraping seller per produk...');
            $sellerResults = $client->scrapeSellerPerProduct(array_values($productMap));
            $job->addLog('info', 'Seller scraping selesai: '.count($sellerResults).' produk');

            // Save sellers
            $this->saveSellers($config->id, $sellerResults);
            $job->addLog('info', 'Seller data disimpan ke database');

            // STEP 3: Update products ke seller termurah
            $job->addLog('info', 'STEP 3: Update produk ke seller termurah...');
            $scraperConfig = array_merge($config->scraper_config ?? [], [
                'dry_run' => false,
                'description_patterns' => $config->scraper_config['description_patterns'] ?? ['/100%/i', '/detikan/i'],
                'pattern_mode' => $config->scraper_config['pattern_mode'] ?? 'AND',
                'prioritize_favorite' => $config->scraper_config['prioritize_favorite'] ?? true,
                'filter_multi' => $config->scraper_config['filter_multi'] ?? null,
                'filter_min_rating' => $config->scraper_config['filter_min_rating'] ?? null,
                'filter_min_rating_qty' => $config->scraper_config['filter_min_rating_qty'] ?? null,
                'strict_filters' => $config->scraper_config['strict_filters'] ?? false,
                'strict_patterns' => $config->scraper_config['strict_patterns'] ?? false,
                'no_seller_action' => $config->scraper_config['no_seller_action'] ?? 'skip',
                'dry_run' => false,
            ]);

            $results = $config->getClient()->updateProducts(
                $sellerResults,
                $productMap,
                $scraperConfig
            );

            $updated = collect($results)->where('status', 'updated')->count();
            $deleted = collect($results)->where('status', 'deleted_duplicate')->count();
            $skipped = collect($results)->whereIn('status', ['skip_no_seller', 'skip_strict_hard_filter', 'skip_strict_pattern'])->count();

            $job->addLog('info', "Sinkronisasi selesai. Updated: {$updated}, Deleted: {$deleted}, Skipped: {$skipped}");

            $job->markCompleted();
        } catch (\Throwable $e) {
            $job->addLog('error', 'Sinkronisasi gagal: '.$e->getMessage());
            $job->markFailed($e->getMessage());
            throw $e;
        }
    }

    private function saveProducts(int $configId, array $products): array
    {
        $map = [];

        foreach ($products as $product) {
            $productId = $product['id'] ?? null;
            if (! $productId) {
                continue;
            }

            $saved = BotDigiflazzProduct::updateOrCreate(
                [
                    'config_id' => $configId,
                    'digiflazz_product_id' => $product['id'],
                ],
                [
                    'product_code' => $product['code'] ?? '',
                    'product_name' => $product['product'] ?? '',
                    'category' => $product['category'] ?? null,
                    'brand' => $product['brand'] ?? null,
                    'type' => $product['type'] ?? null,
                    'seller' => $product['seller'] ?? null,
                    'seller_sku_code' => $product['seller_sku_code'] ?? null,
                    'seller_connection_type' => $product['seller_connection_type'] ?? null,
                    'price' => $product['price'] ?? 0,
                    'stock' => $product['stock'] ?? 0,
                    'unlimited_stock' => $product['unlimited_stock'] ?? false,
                    'status' => $product['status'] ?? true,
                    'start_cut_off' => $product['start_cut_off'] ?? null,
                    'end_cut_off' => $product['end_cut_off'] ?? null,
                    'description' => $product['description'] ?? null,
                    'faktur' => $product['faktur'] ?? false,
                    'multi' => $product['multi'] ?? false,
                    'multi_counter' => $product['multi_counter'] ?? 0,
                    'seller_sku_id' => $product['seller_sku_id'] ?? null,
                    'seller_sku_id_int' => $product['seller_sku_id_int'] ?? null,
                    'seller_details' => $product['seller_details'] ?? null,
                    'last_update' => $product['last_update'] ?? null,
                    'is_seller_favorite' => $product['is_seller_favorite'] ?? false,
                    'rating' => $product['reviewAvg'] ?? null,
                    'rating_qty' => $product['rating_qty'] ?? null,
                    'raw_data' => $product,
                ]
            );

            $map[$product['id']] = $saved->toArray();
        }

        return $map;
    }

    private function saveSellers(int $configId, array $sellerResults): void
    {
        foreach ($sellerResults as $result) {
            $product = BotDigiflazzProduct::where('config_id', $configId)
                ->where('digiflazz_product_id', $result['product_id'])
                ->first();

            if (! $product) {
                continue;
            }

            $sellers = $result['seller_data']['data'] ?? [];

            foreach ($sellers as $seller) {
                BotDigiflazzSeller::updateOrCreate(
                    [
                        'config_id' => $configId,
                        'product_id' => $product->id,
                        'seller_name' => $seller['seller'] ?? '',
                    ],
                    [
                        'seller_sku_code' => $seller['seller_sku_code'] ?? null,
                        'seller_connection_type' => $seller['connectionType'] ?? null,
                        'price' => $seller['price'] ?? 0,
                        'stock' => $seller['stock'] ?? 0,
                        'unlimited_stock' => $seller['unlimited_stock'] ?? false,
                        'status' => $seller['status'] ?? true,
                        'start_cut_off' => $seller['start_cut_off'] ?? null,
                        'end_cut_off' => $seller['end_cut_off'] ?? null,
                        'faktur' => $seller['faktur'] ?? false,
                        'multi' => $seller['multi'] ?? false,
                        'multi_counter' => $seller['multi_counter'] ?? 0,
                        'seller_sku_id' => $seller['id'] ?? null,
                        'seller_sku_id_int' => $seller['id_int'] ?? null,
                        'is_seller_favorite' => $seller['is_seller_favorite'] ?? false,
                        'rating' => $seller['reviewAvg'] ?? null,
                        'rating_qty' => $seller['rating_qty'] ?? null,
                        'deskripsi' => $seller['deskripsi'] ?? null,
                        'seller_details' => $seller['seller_details'] ?? null,
                        'raw_data' => $seller,
                    ]
                );
            }
        }
    }
}
