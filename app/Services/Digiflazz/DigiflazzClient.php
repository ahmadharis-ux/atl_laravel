<?php

namespace App\Services\Digiflazz;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Klien scraping & update produk Digiflazz (member.digiflazz.com).
 *
 * Port dari script `produk.php` + `lib/Digiflazz.php` (folder Digiflazz)
 * yang menangani STEP 1 (daftar produk closed), STEP 2 (seller per produk),
 * dan STEP 3 (update ke seller termurah) — termasuk retry HTTP 429.
 */
class DigiflazzClient
{
    private array $baseHeaders = [];

    private array $updateHeaders = [];

    private string $cookieString;

    private string $csrfToken;

    private string $socketId;

    private string $xsrfToken;

    /** Jeda antar request dalam microsecond (300000 = 0.3 detik). */
    public int $requestDelay = 300000;

    /** Jumlah maksimal retry jika dapat HTTP 429. 0 = tidak retry. */
    public int $retryMax = 3;

    /** Delay awal (detik) sebelum retry pertama; berlipat ganda tiap retry. */
    public int $retryDelaySeconds = 5;

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

    /**
     * Request dengan retry otomatis saat HTTP 429 (Too Many Requests).
     * Menghormati header `Retry-After` dan memakai exponential backoff.
     *
     * @return array{success: bool, http_code: int, data: mixed}
     */
    private function request(string $method, string $url, array $data = [], bool $isUpdate = false): array
    {
        $headers = $isUpdate ? $this->updateHeaders : $this->baseHeaders;
        $attempt = 0;
        $delaySeconds = $this->retryDelaySeconds;

        while (true) {
            try {
                $pending = Http::withHeaders($headers)->timeout(30);

                if ($method === 'POST' && ! empty($data)) {
                    $pending = $pending->withBody(
                        json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'application/json'
                    );
                }

                $response = $pending->send($method, $url);
            } catch (\Throwable $e) {
                Log::error('Digiflazz request failed', [
                    'method' => $method,
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);

                return ['success' => false, 'http_code' => 0, 'data' => ['message' => $e->getMessage()]];
            }

            $httpCode = $response->status();

            // ── Handle 429 Too Many Requests ──────────────────────
            if ($httpCode === 429) {
                if ($attempt >= $this->retryMax) {
                    return ['success' => false, 'http_code' => 429, 'data' => $response->json()];
                }

                $waitSeconds = $delaySeconds;
                $retryAfter = $response->header('Retry-After');

                if (is_string($retryAfter) && preg_match('/\d+/', $retryAfter, $m)) {
                    $waitSeconds = (int) $m[0];
                }

                $attempt++;
                Log::warning('Digiflazz 429 Too Many Requests, retry', [
                    'url' => $url,
                    'wait_seconds' => $waitSeconds,
                    'attempt' => $attempt,
                    'max' => $this->retryMax,
                ]);

                sleep($waitSeconds);
                $delaySeconds *= 2;

                continue;
            }

            if ($httpCode !== 200) {
                return ['success' => false, 'http_code' => $httpCode, 'data' => $response->json()];
            }

            return ['success' => true, 'http_code' => 200, 'data' => $response->json()];
        }
    }

    // ── STEP 1 — Scrape daftar produk closed ───────────────────────
    public function scrapeProducts(): array
    {
        $response = $this->request('GET', 'https://member.digiflazz.com/api/v1/buyer/product/closed');
        $data = $response['data']['data'] ?? [];

        return is_array($data) ? $data : [];
    }

    // ── STEP 2 — Ambil seller per produk ─────────────────────────
    /**
     * Ambil seller untuk satu produk, sudah diurutkan dari harga termurah.
     *
     * @return array{success: bool, http_code: int, sellers: array}
     */
    public function fetchSellers(string $productId): array
    {
        $url = "https://member.digiflazz.com/api/v1/buyer/product/seller/{$productId}";
        $response = $this->request('GET', $url);

        if (! $response['success']) {
            return ['success' => false, 'http_code' => $response['http_code'], 'sellers' => []];
        }

        $sellers = $response['data']['data'] ?? [];
        $sellers = is_array($sellers) ? $sellers : [];

        usort($sellers, function ($a, $b) {
            $pA = is_numeric($a['price'] ?? null) ? (float) $a['price'] : PHP_INT_MAX;
            $pB = is_numeric($b['price'] ?? null) ? (float) $b['price'] : PHP_INT_MAX;

            return $pA <=> $pB;
        });

        return ['success' => true, 'http_code' => $response['http_code'], 'sellers' => $sellers];
    }

    // ── STEP 3 — Filter & pilih seller termurah ──────────────────
    /**
     * Terapkan hard filter (multi/favorit/rating) dan filter pattern deskripsi,
     * lalu pilih seller dengan harga termurah.
     *
     * @param  array<array-key, array>  $sellers
     * @param  array<string, mixed>  $config  config mentah atau hasil normalizeConfig()
     * @return array{status: string, chosen: ?array, fallback: bool, source: string}
     */
    public function selectCheapestSeller(array $sellers, array $config): array
    {
        $config = self::normalizeConfig($config);

        $baseFiltered = $sellers;

        // ── TAHAP 1: Hard filter ─────────────────────────────────
        if ($config['filter_multi'] !== null) {
            $baseFiltered = array_values(array_filter($baseFiltered, function ($s) use ($config) {
                return ((bool) ($s['multi'] ?? false)) === $config['filter_multi'];
            }));
        }

        if ($config['prioritize_favorite']) {
            $favoriteSellers = array_values(array_filter($baseFiltered, function ($s) {
                return (bool) ($s['is_seller_favorite'] ?? false);
            }));

            if (! empty($favoriteSellers)) {
                $baseFiltered = $favoriteSellers;
            }
        }

        if ($config['filter_min_rating'] !== null) {
            $baseFiltered = array_values(array_filter($baseFiltered, function ($s) use ($config) {
                $avg = is_numeric($s['reviewAvg'] ?? null) ? (float) $s['reviewAvg'] : 0;

                return $avg >= $config['filter_min_rating'];
            }));
        }

        if ($config['filter_min_rating_qty'] !== null) {
            $baseFiltered = array_values(array_filter($baseFiltered, function ($s) use ($config) {
                $qty = (int) filter_var($s['rating_qty'] ?? 0, FILTER_SANITIZE_NUMBER_INT);

                return $qty >= $config['filter_min_rating_qty'];
            }));
        }

        $hardFilterActive = $config['filter_multi'] !== null || $config['prioritize_favorite']
            || $config['filter_min_rating'] !== null || $config['filter_min_rating_qty'] !== null;
        $hardFallbackUsed = $hardFilterActive && empty($baseFiltered);

        if ($hardFallbackUsed && $config['strict_filters']) {
            return ['status' => 'skip_strict_hard_filter', 'chosen' => null, 'fallback' => false, 'source' => '[STRICT] Tidak ada seller memenuhi hard filter'];
        }

        $afterHardFilter = ! empty($baseFiltered) ? $baseFiltered : $sellers;

        // ── TAHAP 2: Filter pattern deskripsi ────────────────────
        $patternActive = ! empty($config['description_patterns']);
        $patternFallback = false;

        if ($patternActive) {
            $filteredByPattern = array_values(array_filter($afterHardFilter, function ($s) use ($config) {
                $desc = $s['deskripsi'] ?? '';

                if ($config['pattern_mode'] === 'AND') {
                    foreach ($config['description_patterns'] as $pattern) {
                        if (! preg_match($pattern, $desc)) {
                            return false;
                        }
                    }

                    return true;
                }

                foreach ($config['description_patterns'] as $pattern) {
                    if (preg_match($pattern, $desc)) {
                        return true;
                    }
                }

                return false;
            }));

            if (! empty($filteredByPattern)) {
                $filtered = $filteredByPattern;
            } elseif ($config['strict_patterns']) {
                return ['status' => 'skip_strict_pattern', 'chosen' => null, 'fallback' => false, 'source' => '[STRICT] Tidak ada seller cocok pattern'];
            } else {
                $filtered = $afterHardFilter;
                $patternFallback = true;
            }
        } else {
            $filtered = $afterHardFilter;
        }

        if (empty($filtered)) {
            return ['status' => 'skip_no_seller', 'chosen' => null, 'fallback' => false, 'source' => ''];
        }

        // ── TAHAP 3: Sort by harga termurah ──────────────────────
        usort($filtered, function ($a, $b) {
            $pA = is_numeric($a['price'] ?? null) ? (float) $a['price'] : PHP_INT_MAX;
            $pB = is_numeric($b['price'] ?? null) ? (float) $b['price'] : PHP_INT_MAX;

            return $pA <=> $pB;
        });

        $chosen = $filtered[0];

        // ── Label sumber ─────────────────────────────────────────
        if (! $patternActive && ! $hardFilterActive) {
            $sourceLabel = '[TANPA FILTER] Pakai termurah';
        } elseif ($patternFallback && $hardFallbackUsed) {
            $sourceLabel = '[FALLBACK PENUH] Hard filter & pattern tidak cocok, pakai termurah';
        } elseif ($patternFallback) {
            $sourceLabel = '[FALLBACK PATTERN] Tidak ada yang cocok pattern, pakai termurah dari hard filter';
        } elseif ($hardFallbackUsed) {
            $sourceLabel = '[FALLBACK HARD] Hard filter tidak cocok, pakai dari semua seller';
        } elseif ($patternActive) {
            $modeLabel = count($config['description_patterns']) > 1 ? "[{$config['pattern_mode']}]" : '';
            $sourceLabel = "[PATTERN MATCH] $modeLabel";
        } else {
            $sourceLabel = '[HARD FILTER MATCH]';
        }

        return [
            'status' => 'ok',
            'chosen' => $chosen,
            'fallback' => $patternFallback || $hardFallbackUsed,
            'source' => $sourceLabel,
        ];
    }

    // ── Update produk ke seller terpilih ─────────────────────────
    /**
     * POST update produk ke seller terpilih. Duplikat otomatis dihapus.
     *
     * @return array{status: string, http_code: int, response: mixed}
     */
    public function updateProduct(array $product, array $chosen): array
    {
        $body = array_merge($product, [
            'price' => $chosen['price'] ?? null,
            'stock' => $chosen['stock'] ?? null,
            'start_cut_off' => $chosen['start_cut_off'] ?? null,
            'end_cut_off' => $chosen['end_cut_off'] ?? null,
            'unlimited_stock' => $chosen['unlimited_stock'] ?? false,
            'faktur' => $chosen['faktur'] ?? false,
            'multi' => $chosen['multi'] ?? false,
            'multi_counter' => $chosen['multi_counter'] ?? null,
            'seller' => $chosen['seller'] ?? null,
            'seller_sku_id' => $chosen['id'] ?? null,
            'seller_sku_id_int' => $chosen['id_int'] ?? null,
            'seller_connection_type' => $chosen['connectionType'] ?? null,
            'seller_sku_code' => $chosen['seller_sku_code'] ?? null,
            'seller_details' => $chosen['seller_details'] ?? null,
            'status' => $chosen['status'] ?? true,
            'seller_sku_desc' => $chosen['deskripsi'] ?? null,
            'max_price' => 0,
            'change' => true,
        ]);

        $response = $this->request('POST', 'https://member.digiflazz.com/api/v1/buyer/product', $body, true);

        if ($response['http_code'] === 200) {
            return ['status' => 'updated', 'http_code' => 200, 'response' => $response['data']];
        }

        if (
            $response['http_code'] === 400
            && str_contains($response['data']['message'] ?? '', 'Produk dan Seller sudah ada sebelumnya')
        ) {
            $deleted = $this->deleteProduct((string) ($product['id'] ?? ''));

            return [
                'status' => $deleted ? 'deleted_duplicate' : 'delete_failed',
                'http_code' => 400,
                'response' => $response['data'],
            ];
        }

        return [
            'status' => "failed_http_{$response['http_code']}",
            'http_code' => $response['http_code'],
            'response' => $response['data'],
        ];
    }

    public function deleteProduct(string $productId): bool
    {
        $response = $this->request('POST', "https://member.digiflazz.com/api/v1/buyer/product/delete/{$productId}", [], true);

        return $response['http_code'] === 200;
    }

    /**
     * Metadata seller terpilih untuk keperluan log.
     */
    public function buildSellerMeta(array $chosen): array
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

    /**
     * Normalisasi scraper_config ke tipe yang aman + default ala produk.php.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function normalizeConfig(array $config): array
    {
        $toBool = function ($value, bool $default = false): bool {
            if ($value === null || $value === '') {
                return $default;
            }

            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        };

        $filterMulti = $config['filter_multi'] ?? null;
        $filterMulti = ($filterMulti === null || $filterMulti === '')
            ? null
            : filter_var($filterMulti, FILTER_VALIDATE_BOOLEAN);

        $minRating = $config['filter_min_rating'] ?? null;
        $minRatingQty = $config['filter_min_rating_qty'] ?? null;

        return [
            'description_patterns' => array_values(array_filter(
                (array) ($config['description_patterns'] ?? []),
                fn ($pattern) => is_string($pattern) && $pattern !== ''
            )),
            'pattern_mode' => strtoupper((string) ($config['pattern_mode'] ?? 'OR')) === 'AND' ? 'AND' : 'OR',
            'prioritize_favorite' => $toBool($config['prioritize_favorite'] ?? null),
            'filter_multi' => $filterMulti,
            'filter_min_rating' => ($minRating === null || $minRating === '') ? null : (float) $minRating,
            'filter_min_rating_qty' => ($minRatingQty === null || $minRatingQty === '') ? null : (int) $minRatingQty,
            'strict_filters' => $toBool($config['strict_filters'] ?? null),
            'strict_patterns' => $toBool($config['strict_patterns'] ?? null),
            'no_seller_action' => ($config['no_seller_action'] ?? 'skip') === 'delete' ? 'delete' : 'skip',
            'dry_run' => $toBool($config['dry_run'] ?? null),
            'request_delay' => (int) ($config['request_delay'] ?? 300000),
        ];
    }
}
