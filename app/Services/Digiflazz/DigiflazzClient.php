<?php

namespace App\Services\Digiflazz;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DigiflazzClient
{
    private array $baseHeaders = [];

    private array $updateHeaders = [];

    private string $cookieString;

    private string $csrfToken;

    private string $socketId;

    private string $xsrfToken;

    public function __construct(
        private string $cookieContent,
        private string $csrfTokenInput,
        private string $socketIdInput = '',
        private ?string $xsrfTokenInput = null
    ) {
        $this->cookieString = trim($cookieContent);
        $this->csrfToken = trim($csrfTokenInput);
        $this->socketId = trim($socketIdInput);

        if (empty($this->csrfToken)) {
            throw new \InvalidArgumentException('CSRF token tidak boleh kosong');
        }

        $this->xsrfToken = $this->xsrfTokenInput ?? $this->extractXsrfFromCookie();

        if (empty($this->xsrfToken)) {
            throw new \InvalidArgumentException('XSRF token tidak ditemukan');
        }

        $this->buildHeaders();
    }

    private function extractXsrfFromCookie(): string
    {
        if (preg_match('/(?:^|;\s*)XSRF-TOKEN=([^;]+)/i', $this->cookieString, $m)) {
            return trim($m[1]);
        }

        return '';
    }

    private function buildHeaders(): void
    {
        $this->baseHeaders = [
            'accept' => 'application/json',
            'accept-language' => 'id,en-US;q=0.9,en;q=0.8',
            'priority' => 'u=1, i',
            'sec-ch-ua' => '"Not:A-Brand";v="99", "Google Chrome";v="145", "Chromium";v="145"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => 'macOS',
            'sec-fetch-dest' => 'empty',
            'sec-fetch-mode' => 'cors',
            'sec-fetch-site' => 'same-origin',
            'x-csrf-token' => $this->csrfToken,
            'x-requested-with' => 'XMLHttpRequest',
            'x-socket-id' => $this->socketId,
            'x-xsrf-token' => $this->xsrfToken,
            'cookie' => $this->cookieString,
            'Referer' => 'https://member.digiflazz.com/buyer-area/product',
        ];

        $this->updateHeaders = array_merge($this->baseHeaders, [
            'content-type' => 'application/json;charset=UTF-8',
        ]);
    }

    private function request(string $method, string $url, array $data = [], bool $isUpdate = false): array
    {
        $headers = $isUpdate ? $this->updateHeaders : $this->baseHeaders;

        try {
            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->retry(3, 500)
                ->withBody($data ? json_encode($data) : '', 'application/json')
                ->$method($url);

            return [
                'success' => $response->successful(),
                'http_code' => $response->status(),
                'data' => $response->json(),
                'raw' => $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error('Digiflazz request failed', [
                'method' => $method,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'http_code' => 0,
                'data' => ['message' => $e->getMessage()],
                'raw' => '',
            ];
        }
    }

    // ── STEP 1 — Scrape daftar produk closed ───────────────────────
    public function scrapeProducts(): array
    {
        $response = $this->request('GET', 'https://member.digiflazz.com/api/v1/buyer/product/closed');
        $data = $response['data']['data'] ?? [];

        return $data;
    }

    // ── Kategori / Brand / Type ──────────────────────────────────

    /** Ambil semua kategori Digiflazz → [id => name] */
    public function listCategories(): array
    {
        $response = $this->request('GET', 'https://member.digiflazz.com/api/v1/buyer/product/category');
        $raw = $response['data']['data'] ?? $response['data'] ?? [];

        return is_array($raw) ? array_column($raw, 'name', 'id') : [];
    }

    /** Ambil semua brand Digiflazz → [id => name] */
    public function listBrands(): array
    {
        $response = $this->request('GET', 'https://member.digiflazz.com/api/v1/buyer/product/brand');
        $raw = $response['data']['data'] ?? $response['data'] ?? [];

        return is_array($raw) ? array_column($raw, 'name', 'id') : [];
    }

    /** Ambil semua type Digiflazz → [id => name] */
    public function listTypes(): array
    {
        $response = $this->request('GET', 'https://member.digiflazz.com/api/v1/buyer/product/type');
        $raw = $response['data']['data'] ?? $response['data'] ?? [];

        return is_array($raw) ? array_column($raw, 'name', 'id') : [];
    }

    /**
     * Fetch produk untuk satu/beberapa kategori → [productId => product].
     *
     * @param  array<int, string|int>  $categoryIds
     */
    public function fetchProductsByCategories(array $categoryIds): array
    {
        $result = [];

        foreach ($categoryIds as $categoryId) {
            $response = $this->request('GET', "https://member.digiflazz.com/api/v1/buyer/product/category/{$categoryId}/");
            $items = $response['data']['data'] ?? [];

            if (is_array($items)) {
                foreach ($items as $product) {
                    if (isset($product['id'])) {
                        $result[$product['id']] = $product;
                    }
                }
            }

            usleep(300000);
        }

        return $result;
    }

    /**
     * Filter produk (client-side) berdasarkan brand & type terpilih.
     * Array kosong berarti tanpa filter (semua lolos).
     *
     * @param  array<array-key, array>  $products
     * @param  array<int, string|int>  $brandIds
     * @param  array<int, string|int>  $typeIds
     */
    public function filterProductsByBrandAndType(array $products, array $brandIds = [], array $typeIds = []): array
    {
        if (! empty($brandIds)) {
            $allowed = array_flip(array_map('strval', $brandIds));
            $products = array_filter($products, function ($product) use ($allowed) {
                $id = $product['brand_id'] ?? $product['brandId']
                    ?? $product['product_details']['brand']['id']
                    ?? null;

                return $id !== null && isset($allowed[(string) $id]);
            });
        }

        if (! empty($typeIds)) {
            $allowed = array_flip(array_map('strval', $typeIds));
            $products = array_filter($products, function ($product) use ($allowed) {
                $id = $product['type_id'] ?? $product['typeId']
                    ?? $product['product_details']['type']['id']
                    ?? null;

                return $id !== null && isset($allowed[(string) $id]);
            });
        }

        return $products;
    }

    // ── STEP 2 — Scrape seller per produk ────────────────────────
    public function scrapeSellerPerProduct(array $products): array
    {
        $total = count($products);
        $results = [];
        $success = 0;
        $failed = 0;

        foreach ($products as $i => $product) {
            $productId = $product['id'] ?? null;
            $productName = $product['product'] ?? '-';
            $productCode = $product['code'] ?? '-';
            $no = $i + 1;

            if (! $productId) {
                $failed++;

                continue;
            }

            $url = "https://member.digiflazz.com/api/v1/buyer/product/seller/{$productId}";
            $response = $this->request('GET', $url);

            if (! $response['success']) {
                $failed++;

                continue;
            }

            $sellerData = $response['data'];

            if (isset($sellerData['data']) && is_array($sellerData['data'])) {
                usort($sellerData['data'], function ($a, $b) {
                    $pA = is_numeric($a['price'] ?? null) ? (float) $a['price'] : PHP_INT_MAX;
                    $pB = is_numeric($b['price'] ?? null) ? (float) $b['price'] : PHP_INT_MAX;

                    return $pA <=> $pB;
                });
            }

            $sellerCount = count($sellerData['data'] ?? []);
            $results[] = [
                'product_id' => $productId,
                'product_code' => $productCode,
                'product_name' => $productName,
                'seller_data' => $sellerData,
            ];
            $success++;

            usleep(300000); // 0.3 detik delay
        }

        return $results;
    }

    // ── STEP 3 — Update produk ke seller termurah ────────────────
    public function updateProducts(
        array $sellerResults,
        array $productMap,
        array $config
    ): array {
        $total = count($sellerResults);
        $results = [];
        $updated = 0;
        $skipped = 0;
        $deleted = 0;
        $noSeller = 0;

        foreach ($sellerResults as $i => $item) {
            $no = $i + 1;
            $productId = $item['product_id'];
            $productCode = $item['product_code'];
            $productName = $item['product_name'];
            $sellers = $item['seller_data']['data'] ?? [];

            // ── Tidak ada seller ──────────────────────────────────
            if (empty($sellers)) {
                $noSeller++;
                if ($config['no_seller_action'] === 'delete') {
                    if (! $config['dry_run']) {
                        $this->deleteProduct($productId);
                    }
                    $deleted++;
                    $results[] = ['product_id' => $productId, 'status' => $config['dry_run'] ? 'dry_run_delete_no_seller' : 'deleted_no_seller', 'seller' => null];

                    continue;
                }
                $skipped++;
                $results[] = ['product_id' => $productId, 'status' => 'skip_no_seller', 'seller' => null];

                continue;
            }

            // ── Filter & pilih seller termurah ──────────────────
            $filtered = $this->filterAndSelectSeller($sellers, $config);

            if (empty($filtered)) {
                if ($config['strict_filters']) {
                    $noSeller++;
                    $results[] = ['product_id' => $productId, 'status' => 'skip_strict_hard_filter', 'seller' => null];

                    continue;
                }
                $filtered = $sellers; // fallback
            }

            usort($filtered, function ($a, $b) {
                $pA = is_numeric($a['price'] ?? null) ? (float) $a['price'] : PHP_INT_MAX;
                $pB = is_numeric($b['price'] ?? null) ? (float) $b['price'] : PHP_INT_MAX;

                return $pA <=> $pB;
            });

            $chosen = $filtered[0];
            $original = $productMap[$productId] ?? [];

            $body = array_merge($original, [
                'price' => $chosen['price'],
                'stock' => $chosen['stock'],
                'start_cut_off' => $chosen['start_cut_off'],
                'end_cut_off' => $chosen['end_cut_off'],
                'unlimited_stock' => $chosen['unlimited_stock'],
                'faktur' => $chosen['faktur'],
                'multi' => $chosen['multi'],
                'multi_counter' => $chosen['multi_counter'],
                'seller' => $chosen['seller'],
                'seller_sku_id' => $chosen['id'],
                'seller_sku_id_int' => $chosen['id_int'],
                'seller_connection_type' => $chosen['connectionType'],
                'seller_sku_code' => $chosen['seller_sku_code'],
                'seller_details' => $chosen['seller_details'],
                'status' => $chosen['status'],
                'seller_sku_desc' => $chosen['deskripsi'],
                'max_price' => 0,
                'change' => true,
            ]);

            if ($config['dry_run']) {
                $results[] = ['product_id' => $productId, 'status' => 'dry_run', 'seller' => $this->buildSellerMeta($chosen)];

                continue;
            }

            $response = $this->request('POST', 'https://member.digiflazz.com/api/v1/buyer/product', $body, true);

            if ($response['http_code'] === 200) {
                $results[] = ['product_id' => $productId, 'status' => 'updated', 'seller' => $this->buildSellerMeta($chosen)];
            } elseif (
                $response['http_code'] === 400
                && str_contains($response['data']['message'] ?? '', 'Produk dan Seller sudah ada sebelumnya')
            ) {
                $this->deleteProduct($productId);
                $results[] = ['product_id' => $productId, 'status' => 'deleted_duplicate', 'seller' => $this->buildSellerMeta($chosen)];
            } else {
                $results[] = ['product_id' => $productId, 'status' => "failed_http_{$response['http_code']}", 'response' => $response['data']];
            }

            usleep(300000);
        }

        return $results;
    }

    private function filterAndSelectSeller(array $sellers, array $config): array
    {
        $filtered = $sellers;

        // Hard filters
        if ($config['filter_multi'] !== null) {
            $filtered = array_values(array_filter($filtered, function ($s) use ($config) {
                return ((bool) ($s['multi'] ?? false)) === $config['filter_multi'];
            }));
        }

        if ($config['prioritize_favorite']) {
            $favoriteSellers = array_values(array_filter($filtered, function ($s) {
                return (bool) ($s['is_seller_favorite'] ?? false);
            }));
            if (! empty($favoriteSellers)) {
                $filtered = $favoriteSellers;
            }
        }

        if ($config['filter_min_rating'] !== null) {
            $filtered = array_values(array_filter($filtered, function ($s) use ($config) {
                $avg = is_numeric($s['reviewAvg'] ?? null) ? (float) $s['reviewAvg'] : 0;

                return $avg >= $config['filter_min_rating'];
            }));
        }

        if ($config['filter_min_rating_qty'] !== null) {
            $filtered = array_values(array_filter($filtered, function ($s) use ($config) {
                $qty = (int) filter_var($s['rating_qty'] ?? 0, FILTER_SANITIZE_NUMBER_INT);

                return $qty >= $config['filter_min_rating_qty'];
            }));
        }

        // Strict hard filter
        $hardFilterActive = $config['filter_multi'] !== null || $config['prioritize_favorite']
            || $config['filter_min_rating'] !== null || $config['filter_min_rating_qty'] !== null;
        $hardFallbackUsed = $hardFilterActive && empty($filtered);

        if ($hardFallbackUsed && $config['strict_filters']) {
            return []; // strict mode: skip
        }

        if ($hardFallbackUsed) {
            $filtered = $this->sellers ?? $sellers; // fallback to all
        }

        // Pattern filter
        $patterns = $config['description_patterns'] ?? [];
        if (! empty($patterns)) {
            $patternFiltered = array_values(array_filter($filtered, function ($s) use ($config) {
                $desc = $s['deskripsi'] ?? '';
                if ($config['pattern_mode'] === 'AND') {
                    foreach ($config['description_patterns'] as $pat) {
                        if (! preg_match($pat, $desc)) {
                            return false;
                        }
                    }

                    return true;
                } else {
                    foreach ($config['description_patterns'] as $pat) {
                        if (preg_match($pat, $desc)) {
                            return true;
                        }
                    }

                    return false;
                }
            }));

            if (! empty($patternFiltered)) {
                $filtered = $patternFiltered;
            } elseif ($config['strict_patterns']) {
                return []; // strict pattern: skip
            }
            // fallback to hard filtered
        }

        return $filtered;
    }

    private function buildSellerMeta(array $chosen): array
    {
        return [
            'name' => $chosen['seller'] ?? '',
            'sku' => $chosen['seller_sku_code'] ?? '',
            'price' => $chosen['price'] ?? 0,
            'favorite' => (bool) ($chosen['is_seller_favorite'] ?? false),
            'multi' => (bool) ($chosen['multi'] ?? false),
            'rating' => $chosen['reviewAvg'] ?? null,
            'rating_qty' => $chosen['rating_qty'] ?? null,
        ];
    }

    private function deleteProduct(string $productId): bool
    {
        $url = "https://member.digiflazz.com/api/v1/buyer/product/delete/{$productId}";
        $response = $this->request('POST', $url, [], true);

        return $response['http_code'] === 200;
    }
}
