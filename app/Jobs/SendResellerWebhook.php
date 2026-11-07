<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use App\Support\ResellerWebhook;
use App\Support\ResellerWebhookAddressResolver;
use App\Support\TransactionLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Mengirim notifikasi status pesanan ke webhook milik reseller.
 *
 * Payload dikirim sebagai JSON mentah agar tanda tangan HMAC-SHA256 yang
 * dihitung di sisi kita sama persis dengan byte yang diterima reseller.
 * Kegagalan HTTP dilempar kembali ke antrean untuk dicoba ulang.
 */
class SendResellerWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries;

    public string $deliveryId;

    public function __construct(public int $orderId, public string $event)
    {
        $this->tries = max(1, (int) config('reseller.webhook.tries', 3));
        $this->deliveryId = (string) Str::uuid();
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return (array) config('reseller.webhook.backoff', [30, 120]);
    }

    public function handle(ResellerWebhookAddressResolver $addressResolver): void
    {
        $order = Order::query()->with(['user', 'product.brand', 'product.category'])->find($this->orderId);

        if ($order === null || $order->source !== 'api') {
            return;
        }

        $user = $order->user;

        if ($user === null || ! ResellerWebhook::isConfigured($user)) {
            return;
        }

        if (! ResellerWebhook::isDeliverableUrl($user->reseller_webhook_url)) {
            $this->recordStatus($user, 'invalid_url');

            TransactionLogger::warning('reseller.webhook_invalid_url', 'URL webhook reseller tidak valid atau mengarah ke jaringan internal.', [
                'order_number' => $order->order_number,
                'event' => $this->event,
            ], $order, $user);

            return;
        }

        $parts = parse_url($user->reseller_webhook_url);
        $host = strtolower(rtrim(trim((string) ($parts['host'] ?? ''), '[]'), '.'));
        $port = (int) ($parts['port'] ?? (strtolower((string) ($parts['scheme'] ?? '')) === 'https' ? 443 : 80));
        $addresses = $addressResolver->resolvePublicAddresses($host);

        if ($addresses === null) {
            $this->recordStatus($user, 'invalid_url');

            TransactionLogger::warning('reseller.webhook_invalid_url', 'URL webhook reseller tidak memiliki alamat DNS publik yang aman.', [
                'order_number' => $order->order_number,
                'event' => $this->event,
            ], $order, $user);

            return;
        }

        if (! extension_loaded('curl')) {
            throw new RuntimeException('Ekstensi cURL diperlukan untuk mengirim webhook dengan DNS yang dipin.');
        }

        $payload = ResellerWebhook::payload($order, $this->event, $this->deliveryId);
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $signature = ResellerWebhook::signature($json, (string) $user->reseller_webhook_secret);
        $curlOptions = [];

        if (filter_var($host, FILTER_VALIDATE_IP) === false) {
            $curlOptions[CURLOPT_RESOLVE] = $addressResolver->curlResolveEntries($host, $port, $addresses);
        }

        try {
            $response = Http::withHeaders([
                ResellerWebhook::SIGNATURE_HEADER => $signature,
                ResellerWebhook::EVENT_HEADER => $this->event,
                ResellerWebhook::DELIVERY_HEADER => $this->deliveryId,
            ])
                ->connectTimeout((int) config('reseller.webhook.connect_timeout', 5))
                ->timeout((int) config('reseller.webhook.timeout', 10))
                ->withOptions([
                    'allow_redirects' => false,
                    'proxy' => '',
                    'curl' => $curlOptions,
                ])
                ->withBody($json, 'application/json')
                ->post((string) $user->reseller_webhook_url);
        } catch (Throwable $e) {
            $this->recordStatus($user, 'failed');

            TransactionLogger::error('reseller.webhook_failed', 'Gagal mengirim webhook ke reseller.', $e, [
                'order_number' => $order->order_number,
                'event' => $this->event,
                'delivery_id' => $this->deliveryId,
            ], $order, $user);

            throw $e;
        }

        if (! $response->successful()) {
            $isPermanentClientError = $response->clientError() && $response->status() !== 429;
            $isRedirect = $response->status() >= 300 && $response->status() < 400;
            $status = $isPermanentClientError || $isRedirect ? 'rejected' : 'failed';
            $this->recordStatus($user, $status);

            TransactionLogger::warning('reseller.webhook_http_error', 'Webhook reseller dibalas HTTP non-2xx.', [
                'order_number' => $order->order_number,
                'event' => $this->event,
                'delivery_id' => $this->deliveryId,
                'http_status' => $response->status(),
            ], $order, $user);

            if ($isPermanentClientError || $isRedirect) {
                return;
            }

            throw new RuntimeException('Webhook reseller dibalas HTTP '.$response->status().'.');
        }

        $this->recordStatus($user, 'sent');

        TransactionLogger::info('reseller.webhook_sent', 'Webhook status pesanan terkirim ke reseller.', [
            'order_number' => $order->order_number,
            'event' => $this->event,
            'delivery_id' => $this->deliveryId,
            'http_status' => $response->status(),
        ], $order, $user);
    }

    public function failed(?Throwable $e): void
    {
        $order = Order::query()->with('user')->find($this->orderId);
        $user = $order?->user;

        if ($user !== null) {
            $this->recordStatus($user, 'failed');
        }

        if ($order !== null) {
            TransactionLogger::warning('reseller.webhook_dropped', 'Webhook reseller gagal setelah beberapa percobaan: '.($e?->getMessage() ?? 'unknown'), [
                'order_number' => $order->order_number,
                'event' => $this->event,
            ], $order, $user);
        }
    }

    private function recordStatus(User $user, string $status): void
    {
        User::query()->whereKey($user->getKey())->update([
            'reseller_webhook_last_status' => $status,
            'reseller_webhook_last_sent_at' => now(),
        ]);
    }
}
