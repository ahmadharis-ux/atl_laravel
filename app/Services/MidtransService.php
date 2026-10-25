<?php

namespace App\Services;

use App\Enums\DepositStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Jobs\RefundStorefrontOrder;
use App\Models\Deposit;
use App\Models\Order;
use App\Models\User;
use App\Support\DepositSettler;
use App\Support\OrderFulfillment;
use App\Support\OrderPaymentSettler;
use Closure;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Midtrans\Config as MidtransConfig;
use Midtrans\CoreApi;
use Midtrans\Snap;
use Midtrans\Transaction;
use Throwable;

class MidtransService
{
    /**
     * Apakah kredensial Midtrans sudah diisi.
     */
    public function isConfigured(): bool
    {
        return filled(config('midtrans.server_key')) && filled(config('midtrans.client_key'));
    }

    /**
     * Biaya admin QRIS (rupiah) yang dibebankan ke pengguna.
     *
     * Dihitung gross-up: fee = ceil(amount * r / (1 - r)) dengan r = persentase
     * MDR. Hasilnya, setelah Midtrans memotong r dari total tagihan, merchant
     * tetap menerima dana sebesar `amount`.
     */
    public function qrisFee(int $amount): int
    {
        $rate = (float) config('midtrans.qris_fee_percent', 0) / 100;

        if ($amount <= 0 || $rate <= 0 || $rate >= 1) {
            return 0;
        }

        return (int) ceil($amount * $rate / (1 - $rate));
    }

    /**
     * Buat transaksi QRIS dinamis untuk sebuah deposit lalu simpan detail QR
     * ke metadata deposit.
     *
     * @return array{qr_string: ?string, qr_url: ?string, transaction_id: ?string, order_id: string}|null
     *                                                                                                    Null bila Midtrans belum dikonfigurasi atau permintaan gagal.
     */
    public function chargeQris(Deposit $deposit): ?array
    {
        return $this->charge(
            orderId: $deposit->reference,
            amount: (int) $deposit->totalDue(),
            user: $deposit->user,
            onSuccess: function (array $data) use ($deposit): void {
                $metadata = $deposit->metadata ?? [];
                $metadata['midtrans'] = $data;

                $deposit->forceFill([
                    'payment_code' => $data['qr_string'] ?? $deposit->payment_code,
                    'metadata' => $metadata,
                ])->save();
            },
        );
    }

    /**
     * Buat transaksi QRIS melalui halaman hosted Midtrans Snap.
     */
    public function chargeStorefrontOrder(Order $order, string $statusUrl): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $this->boot();
        $product = $order->product;
        $items = [[
            'id' => (string) $order->product_id,
            'price' => (int) $order->amount,
            'quantity' => 1,
            'name' => Str::limit($product?->name ?? 'Produk Atlantic Pedia', 50, ''),
        ]];

        if ($order->fee > 0) {
            $items[] = [
                'id' => 'qris-fee',
                'price' => (int) $order->fee,
                'quantity' => 1,
                'name' => 'Biaya QRIS',
            ];
        }

        try {
            $response = Snap::createTransaction([
                'transaction_details' => [
                    'order_id' => $order->order_number,
                    'gross_amount' => (int) $order->totalDue(),
                ],
                'item_details' => $items,
                'enabled_payments' => ['qris'],
                'callbacks' => [
                    'finish' => $statusUrl,
                    'error' => $statusUrl,
                    'pending' => $statusUrl,
                ],
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }

        $redirectUrl = $response->redirect_url ?? null;

        if (! is_string($redirectUrl) || ! filter_var($redirectUrl, FILTER_VALIDATE_URL)) {
            report(new \UnexpectedValueException('Midtrans Snap tidak mengembalikan redirect_url yang valid.'));

            return null;
        }

        $metadata = $order->metadata ?? [];
        $metadata['midtrans'] = [
            'snap_token' => $response->token ?? null,
            'redirect_url' => $redirectUrl,
        ];

        $order->forceFill(['metadata' => $metadata])->save();

        return $redirectUrl;
    }

    /**
     * Ajukan refund penuh untuk QRIS yang dibuat melalui toko reseller.
     */
    public function requestStorefrontRefund(Order $order): object
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Kredensial Midtrans belum dikonfigurasi.');
        }

        if ($order->source !== 'store_qris' || $order->payment_method !== PaymentMethod::Qris) {
            throw new \InvalidArgumentException('Refund Midtrans hanya berlaku untuk pesanan QRIS toko reseller.');
        }

        $this->boot();

        $currentStatus = Transaction::status($order->order_number);
        if ((string) ($currentStatus->transaction_status ?? '') === 'refund'
            && (float) ($currentStatus->refund_amount ?? 0) >= $order->totalDue()) {
            return is_object($currentStatus) ? $currentStatus : (object) $currentStatus;
        }

        $response = Transaction::refund($order->order_number, [
            'refund_key' => 'store-refund-'.$order->order_number,
            'amount' => (int) $order->totalDue(),
            'reason' => 'Pesanan toko reseller gagal diproses',
        ]);

        return is_object($response) ? $response : (object) $response;
    }

    /**
     * Buat transaksi QRIS dinamis untuk pesanan produk, simpan QR ke metadata
     * order dan set kode pembayaran (qr_string) bila kosong.
     *
     * @return array{qr_string: ?string, qr_url: ?string, transaction_id: ?string, order_id: string}|null
     */
    public function chargeOrderQris(Order $order): ?array
    {
        return $this->charge(
            orderId: $order->order_number,
            amount: (int) $order->totalDue(),
            user: $order->user,
            onSuccess: function (array $data) use ($order): void {
                $metadata = $order->metadata ?? [];
                $metadata['midtrans'] = $data;

                $order->forceFill([
                    'payment_code' => $order->payment_code ?? $data['qr_string'],
                    'metadata' => $metadata,
                ])->save();
            },
        );
    }

    /**
     * Proses payload notifikasi Midtrans. Mencoba settle deposit terlebih dulu
     * (berdasarkan reference), kemudian settle order (berdasarkan order_number).
     */
    public function handleNotification(array $payload): Deposit|Order|null
    {
        $orderId = (string) ($payload['order_id'] ?? '');

        if ($orderId === '') {
            return null;
        }

        if ($this->isConfigured() && ! $this->verifySignature($payload)) {
            return null;
        }

        return $this->applyTransactionStatus(
            orderId: $orderId,
            status: (string) ($payload['transaction_status'] ?? ''),
            fraud: (string) ($payload['fraud_status'] ?? ''),
            refundAmount: isset($payload['refund_amount']) ? (string) $payload['refund_amount'] : null,
            transactionId: isset($payload['transaction_id']) ? (string) $payload['transaction_id'] : null,
        );
    }

    /**
     * Tanyakan status transaksi terkini ke Midtrans lalu terapkan ke order
     * terkait. Dipakai saat pengguna menekan tombol "Cek Status" ketika webhook
     * belum/terlambat menjangkau server.
     */
    public function syncOrderStatus(Order $order): ?string
    {
        return $this->syncStatus($order->order_number);
    }

    /**
     * Tanyakan status transaksi terkini ke Midtrans lalu terapkan ke deposit
     * terkait (top up saldo).
     */
    public function syncDepositStatus(Deposit $deposit): ?string
    {
        return $this->syncStatus($deposit->reference);
    }

    /**
     * Ambil status transaksi dari Midtrans berdasarkan order_id/reference lalu
     * terapkan ke deposit atau order terkait.
     */
    private function syncStatus(string $orderId): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $this->boot();

        try {
            $response = Transaction::status($orderId);
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        $status = (string) ($response->transaction_status ?? '');

        if ($status === '') {
            return null;
        }

        $this->applyTransactionStatus(
            orderId: $orderId,
            status: $status,
            fraud: (string) ($response->fraud_status ?? ''),
            refundAmount: isset($response->refund_amount) ? (string) $response->refund_amount : null,
            transactionId: isset($response->transaction_id) ? (string) $response->transaction_id : null,
        );

        return $status;
    }

    /**
     * Unduh gambar QRIS dari URL Midtrans agar bisa dijadikan file oleh
     * pengguna (menghindari batasan CORS saat diunduh dari browser).
     */
    public function fetchQrImage(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        try {
            $response = Http::timeout(20)->get($url);
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        return $response->successful() ? $response->body() : null;
    }

    /**
     * Terapkan status transaksi ke deposit (berdasarkan reference) atau order
     * (berdasarkan order_number).
     */
    private function applyTransactionStatus(
        string $orderId,
        string $status,
        string $fraud,
        ?string $refundAmount = null,
        ?string $transactionId = null,
    ): Deposit|Order|null {
        $settled = in_array($status, ['capture', 'settlement'], true)
            && in_array($fraud, ['', 'accept'], true);

        $deposit = Deposit::query()->where('reference', $orderId)->first();
        if ($deposit !== null) {
            if ($settled && $deposit->status === DepositStatus::Pending) {
                DepositSettler::settle($deposit);

                return $deposit->refresh();
            }

            if ($status === 'expire' && $deposit->status === DepositStatus::Pending) {
                $deposit->update([
                    'status' => DepositStatus::Expired,
                    'finalized_at' => now(),
                ]);

                return $deposit->refresh();
            }

            return $deposit;
        }

        $order = Order::query()->where('order_number', $orderId)->first();
        if ($order !== null) {
            if ($settled && $order->status === OrderStatus::Pending) {
                OrderPaymentSettler::settle($order);

                return $order->refresh();
            }

            if ($settled && in_array($order->source, ['store_qris'], true)
                && in_array($order->status, [OrderStatus::Expired, OrderStatus::Failed], true)) {
                RefundStorefrontOrder::dispatch($order->getKey())->afterCommit();

                return $order->refresh();
            }

            if ($status === 'expire' && $order->status === OrderStatus::Pending) {
                OrderFulfillment::expire($order);

                return $order->refresh();
            }

            if (in_array($status, ['refund', 'partial_refund'], true) && $order->source === 'store_qris') {
                $isFullRefund = $status === 'refund'
                    || ($refundAmount !== null && (float) $refundAmount >= $order->totalDue());
                $metadata = $order->metadata ?? [];
                $metadata['midtrans_refund'] = [
                    'status' => $isFullRefund ? 'completed' : 'manual_required',
                    'transaction_id' => $transactionId,
                ];

                $order->forceFill([
                    'refund_status' => $isFullRefund ? 'completed' : 'manual_required',
                    'refund_transaction_id' => $transactionId,
                    'refunded_at' => $isFullRefund ? ($order->refunded_at ?? now()) : $order->refunded_at,
                    'metadata' => $metadata,
                ])->save();

                return $order->refresh();
            }

            return $order;
        }

        return null;
    }

    /**
     * Verifikasi signature_key dari payload notifikasi Midtrans.
     */
    public function verifySignature(array $payload): bool
    {
        $signature = hash('sha512', ($payload['order_id'] ?? '')
            .($payload['status_code'] ?? '')
            .($payload['gross_amount'] ?? '')
            .(string) config('midtrans.server_key'));

        return hash_equals($signature, (string) ($payload['signature_key'] ?? ''));
    }

    private function boot(): void
    {
        MidtransConfig::$serverKey = (string) config('midtrans.server_key');
        MidtransConfig::$clientKey = (string) config('midtrans.client_key');
        MidtransConfig::$isProduction = (bool) config('midtrans.is_production');
        MidtransConfig::$isSanitized = (bool) config('midtrans.is_sanitized');
        MidtransConfig::$is3ds = (bool) config('midtrans.is_3ds');
        MidtransConfig::$overrideNotifUrl = config('midtrans.notification_url') ?: null;
    }

    /**
     * @param  Closure(array):void|null  $onSuccess
     * @return array{qr_string: ?string, qr_url: ?string, transaction_id: ?string, order_id: string}|null
     */
    private function charge(string $orderId, int $amount, ?User $user, ?Closure $onSuccess = null): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $this->boot();

        try {
            $response = CoreApi::charge([
                'payment_type' => 'qris',
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $amount,
                ],
                'qris' => ['acquirer' => 'gopay'],
                'customer_details' => [
                    'first_name' => $user?->name,
                    'email' => $user?->email,
                ],
            ]);
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        $data = [
            'qr_string' => $response->qr_string ?? null,
            'qr_url' => $this->qrUrlFromActions($response),
            'transaction_id' => $response->transaction_id ?? null,
            'order_id' => (string) ($response->order_id ?? $orderId),
        ];

        if ($onSuccess !== null) {
            $onSuccess($data);
        }

        return $data;
    }

    private function qrUrlFromActions(object $response): ?string
    {
        foreach ($response->actions ?? [] as $action) {
            $name = is_object($action) ? ($action->name ?? null) : ($action['name'] ?? null);

            if ($name === 'generate-qr-code') {
                return is_object($action) ? ($action->url ?? null) : ($action['url'] ?? null);
            }
        }

        return null;
    }
}
