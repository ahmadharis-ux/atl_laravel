<?php

namespace App\Jobs;

use App\Models\BotDigiflazzConfig;
use App\Models\BotDigiflazzProduct;
use App\Models\BotDigiflazzSeller;
use App\Models\BotDigiflazzSyncLog;
use App\Services\Digiflazz\DigiflazzClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

/**
 * Scraper & updater produk Digiflazz (port dari `produk.php`).
 *
 * STEP 1: scrape daftar produk closed.
 * STEP 2+3 (gabungan): untuk tiap produk → ambil seller → pilih termurah → update.
 * Hasil disimpan ke DB (produk & seller) dan dicatat ke BotDigiflazzSyncLog.
 */
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
            $scraperConfig = DigiflazzClient::normalizeConfig(
                array_merge($config->scraper_config ?? [], $this->overrideConfig ?? [])
            );
            $client->requestDelay = $scraperConfig['request_delay'];

            // ── STEP 1: Scrape produk closed ─────────────────────
            $job->addLog('info', 'STEP 1: Scraping daftar produk closed...');
            $products = $client->scrapeProducts();

            if (empty($products)) {
                $job->addLog('error', 'Tidak ada produk ditemukan. Proses dihentikan.');
                $job->markFailed('Tidak ada produk ditemukan');

                return;
            }

            $total = count($products);
            $job->addLog('info', "Total produk ditemukan: {$total}");

            $summary = ['updated' => 0, 'deleted' => 0, 'skipped' => 0, 'no_seller' => 0];

            // ── STEP 2+3: Scrape seller & langsung update ────────
            foreach ($products as $i => $product) {
                $no = $i + 1;
                $productId = $product['id'] ?? null;
                $productName = $product['product'] ?? '-';

                if (! $productId) {
                    $summary['skipped']++;
                    $job->addLog('warning', "[{$no}/{$total}] SKIP: ID produk tidak ada ({$productName})");

                    continue;
                }

                $savedProduct = $this->saveProduct($config->id, $product);

                $sellerResponse = $client->fetchSellers((string) $productId);

                if (! $sellerResponse['success']) {
                    $summary['skipped']++;
                    $job->addLog('error', "[{$no}/{$total}] Gagal ambil seller (HTTP {$sellerResponse['http_code']}): {$productName}");
                    usleep($scraperConfig['request_delay']);

                    continue;
                }

                $sellers = $sellerResponse['sellers'];
                $this->saveSellers($config->id, $savedProduct, $sellers);

                // ── Tidak ada seller ─────────────────────────────
                if (empty($sellers)) {
                    $summary['no_seller']++;
                    $this->handleNoSeller($client, $job, $scraperConfig, $summary, $no, $total, (string) $productId, $productName);
                    usleep($scraperConfig['request_delay']);

                    continue;
                }

                // ── Pilih seller termurah ────────────────────────
                $selection = $client->selectCheapestSeller($sellers, $scraperConfig);

                if ($selection['status'] !== 'ok' || empty($selection['chosen'])) {
                    $summary['no_seller']++;
                    $job->addLog('info', "[{$no}/{$total}] {$selection['status']}, produk di-skip ({$productName})");
                    usleep($scraperConfig['request_delay']);

                    continue;
                }

                $chosen = $selection['chosen'];
                $suffix = $selection['fallback'] ? '_fallback' : '';
                $priceLabel = $this->formatPrice($chosen['price'] ?? null);

                // ── Dry run ──────────────────────────────────────
                if ($scraperConfig['dry_run']) {
                    $summary['updated']++;
                    $job->addLog('info', "[{$no}/{$total}] DRY RUN: {$chosen['seller']} @ {$priceLabel} ({$productName})");
                    usleep($scraperConfig['request_delay']);

                    continue;
                }

                // ── Update ───────────────────────────────────────
                $update = $client->updateProduct($product, $chosen);
                $status = $update['status'].$suffix;

                if (str_starts_with($status, 'updated')) {
                    $summary['updated']++;
                    $job->addLog('info', "[{$no}/{$total}] OK: {$chosen['seller']} @ {$priceLabel} ({$productName})");
                } elseif (str_starts_with($status, 'deleted')) {
                    $summary['deleted']++;
                    $job->addLog('warning', "[{$no}/{$total}] Duplikat, produk dihapus ({$productName})");
                } else {
                    $summary['skipped']++;
                    $job->addLog('error', "[{$no}/{$total}] Gagal update ({$status}): {$productName}");
                }

                usleep($scraperConfig['request_delay']);
            }

            $job->addLog('info', sprintf(
                'Selesai. Updated: %d, Deleted: %d, Skipped: %d, NoSeller: %d',
                $summary['updated'],
                $summary['deleted'],
                $summary['skipped'],
                $summary['no_seller']
            ));

            $job->markCompleted();
        } catch (\Throwable $e) {
            $job->addLog('error', 'Sinkronisasi gagal: '.$e->getMessage());
            $job->markFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * @param  array<string, int>  $summary
     */
    private function handleNoSeller(
        DigiflazzClient $client,
        BotDigiflazzSyncLog $job,
        array $scraperConfig,
        array &$summary,
        int $no,
        int $total,
        string $productId,
        string $productName
    ): void {
        if ($scraperConfig['no_seller_action'] !== 'delete') {
            $summary['skipped']++;
            $job->addLog('info', "[{$no}/{$total}] Tidak ada seller, diabaikan ({$productName})");

            return;
        }

        if ($scraperConfig['dry_run']) {
            $summary['deleted']++;
            $job->addLog('info', "[{$no}/{$total}] DRY RUN: tidak ada seller, produk akan dihapus ({$productName})");

            return;
        }

        if ($client->deleteProduct($productId)) {
            $summary['deleted']++;
            $job->addLog('info', "[{$no}/{$total}] Tidak ada seller, produk dihapus ({$productName})");
        } else {
            $summary['skipped']++;
            $job->addLog('error', "[{$no}/{$total}] Tidak ada seller, gagal hapus produk ({$productName})");
        }
    }

    private function saveProduct(int $configId, array $product): BotDigiflazzProduct
    {
        return BotDigiflazzProduct::updateOrCreate(
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
    }

    private function saveSellers(int $configId, BotDigiflazzProduct $product, array $sellers): void
    {
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

    private function formatPrice(mixed $price): string
    {
        return is_numeric($price) ? 'Rp '.number_format((float) $price, 0, ',', '.') : '-';
    }
}
