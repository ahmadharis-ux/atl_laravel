<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

/**
 * Klien tipis API buyer Digiflazz.
 *
 * Autentikasi memakai signature md5(username + apiKey + suffix) yang
 * dikirim di body request. Suffix berbeda per endpoint: "depo" untuk cek
 * saldo, "pricelist" untuk daftar harga, dan ref_id untuk transaksi.
 *
 * Dokumentasi: https://developer.digiflazz.com/api/buyer/persiapan/
 */
final class Digiflazz
{
    public function __construct(
        private readonly string $username,
        private readonly string $apiKey,
        private readonly string $baseUrl = 'https://api.digiflazz.com/v1',
    ) {}

    public static function make(): self
    {
        return new self(
            (string) config('services.digiflazz.username'),
            (string) config('services.digiflazz.api_key'),
            (string) config('services.digiflazz.base_url', 'https://api.digiflazz.com/v1'),
        );
    }

    /**
     * Apakah memakai mode sandbox Digiflazz. Di mode ini transaksi dikirim
     * dengan penanda `testing` sehingga tidak memotong deposit sebenarnya.
     */
    public static function sandbox(): bool
    {
        return config('services.digiflazz.mode') === 'development';
    }

    /**
     * Signature autentikasi: md5(username + apiKey + suffix).
     */
    public function sign(string $suffix): string
    {
        return md5($this->username.$this->apiKey.$suffix);
    }

    /**
     * Sisa deposit (saldo) akun buyer.
     *
     * @return array<string, mixed>
     */
    public function checkBalance(): array
    {
        return $this->post('cek-saldo', [
            'cmd' => 'deposit',
            'sign' => $this->sign('depo'),
        ]);
    }

    /**
     * Daftar harga produk. $cmd bernilai "prepaid" atau "pasca".
     *
     * @param  array<string, string|int>  $filters
     * @return array<int, array<string, mixed>>
     *
     * @throws DigiflazzException
     */
    public function priceList(string $cmd = 'prepaid', array $filters = []): array
    {
        $data = $this->post('price-list', array_merge($filters, [
            'cmd' => $cmd,
            'sign' => $this->sign('pricelist'),
        ]));

        if (! array_is_list($data)) {
            throw DigiflazzException::fromResponse($data);
        }

        /** @var array<int, array<string, mixed>> $data */
        return $data;
    }

    /**
     * Kirim transaksi prabayar/pascabayar.
     *
     * @return array<string, mixed>
     */
    public function topup(string $skuCode, string $customerNo, string $refId, bool $testing = false): array
    {
        $payload = [
            'buyer_sku_code' => $skuCode,
            'customer_no' => $customerNo,
            'ref_id' => $refId,
            'sign' => $this->sign($refId),
        ];

        if ($testing) {
            $payload['testing'] = true;
        }

        return $this->post('transaction', $payload);
    }

    /**
     * Validasi signature webhook Digiflazz (header X-Hub-Signature).
     */
    public function verifyWebhook(string $payload, ?string $signatureHeader, ?string $secret = null): bool
    {
        $secret ??= (string) config('services.digiflazz.webhook_secret');

        if ($secret === '' || $signatureHeader === null) {
            return false;
        }

        return hash_equals('sha1='.hash_hmac('sha1', $payload, $secret), $signatureHeader);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function post(string $endpoint, array $payload): array
    {
        $response = Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->asJson()
            ->timeout(15)
            ->post($endpoint, array_merge(['username' => $this->username], $payload));

        $data = $response->json('data');

        return is_array($data) ? $data : [];
    }
}
