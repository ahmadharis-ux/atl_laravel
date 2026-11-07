<?php

namespace App\Support;

use App\Jobs\SendResellerWebhook;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Kontrak webhook status pesanan untuk reseller.
 *
 * Payload JSON dikirim ke URL milik reseller dan ditandatangani HMAC-SHA256
 * memakai secret per-reseller. Reseller memverifikasi header
 * `X-ATL-Signature: sha256=<hex>` sebelum memproses payload.
 */
final class ResellerWebhook
{
    public const EVENT_SUCCESS = 'order.success';

    public const EVENT_FAILED = 'order.failed';

    public const EVENT_EXPIRED = 'order.expired';

    public const SIGNATURE_HEADER = 'X-ATL-Signature';

    public const EVENT_HEADER = 'X-ATL-Event';

    public const DELIVERY_HEADER = 'X-ATL-Delivery';

    /**
     * Buat secret baru. Secret asli hanya dikembalikan sekali; yang disimpan
     * di database adalah versi terenkripsi (lihat cast User).
     *
     * @return array{plain: string, hint: string}
     */
    public static function generateSecret(): array
    {
        $prefix = (string) config('reseller.webhook.secret_prefix', 'whsec_');
        $plain = $prefix.Str::random(40);

        return [
            'plain' => $plain,
            'hint' => mb_substr($plain, 0, 14).'…',
        ];
    }

    /**
     * Tanda tangan payload: `sha256=<hmac hex>`.
     */
    public static function signature(string $payload, string $secret): string
    {
        return 'sha256='.hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Reseller punya URL, secret, dan keanggotaan yang aktif.
     */
    public static function isConfigured(User $user): bool
    {
        return $user->is_reseller
            && in_array($user->reseller_status, [null, 'active'], true)
            && filled($user->reseller_webhook_url)
            && filled($user->reseller_webhook_secret);
    }

    /**
     * Tolak URL yang tidak aman sebelum mencoba resolusi DNS dan pengiriman.
     */
    public static function isDeliverableUrl(?string $url): bool
    {
        if (! is_string($url) || trim($url) === '') {
            return false;
        }

        $parts = parse_url(trim($url));

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])) {
            return false;
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower(trim($parts['host'], '[]'));

        $schemeAllowed = $scheme === 'https'
            || (! app()->environment('production') && $scheme === 'http');

        if (! $schemeAllowed || $host === '' || str_ends_with($host, '.')) {
            return false;
        }

        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        if (app()->environment('production') && ($scheme !== 'https' || $port !== 443)) {
            return false;
        }

        if (in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0', 'metadata.google.internal'], true)
            || str_ends_with($host, '.localhost')
            || str_ends_with($host, '.local')) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        }

        $labels = explode('.', $host);
        $lastLabel = $labels[array_key_last($labels)];

        return count($labels) > 1
            && filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false
            && preg_match('/[a-z]/i', $lastLabel) === 1;
    }

    /**
     * Payload webhook untuk satu pesanan.
     *
     * @return array<string, mixed>
     */
    public static function payload(Order $order, string $event, string $deliveryId): array
    {
        $order->loadMissing(['product.brand', 'product.category']);
        $product = $order->product;

        return [
            'event' => $event,
            'delivery_id' => $deliveryId,
            'sent_at' => now()->toIso8601String(),
            'order' => [
                'order_number' => $order->order_number,
                'reff_id' => $order->idempotency_key,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'product' => [
                    'id' => $product?->getKey(),
                    'code' => $product?->code,
                    'name' => $product?->name,
                    'brand' => $product?->brand?->name,
                    'category' => $product?->category?->name,
                ],
                'target' => $order->target,
                'target_zone' => $order->target_zone,
                'amount' => (int) $order->amount,
                'serial_number' => $order->provider_sn,
                'provider_status' => $order->provider_status,
                'notes' => $order->notes,
                'created_at' => $order->created_at?->toIso8601String(),
                'completed_at' => $order->paid_at?->toIso8601String(),
            ],
        ];
    }

    /**
     * Kirim webhook bila pesanan berasal dari API reseller dan konfigurasi
     * reseller lengkap. Dipanggil setelah transaksi DB selesai.
     */
    public static function dispatchForOrder(Order $order, string $event): void
    {
        if ($order->source !== 'api') {
            return;
        }

        $user = $order->user()->first();

        if ($user === null || ! self::isConfigured($user)) {
            return;
        }

        SendResellerWebhook::dispatch($order->getKey(), $event);
    }
}
