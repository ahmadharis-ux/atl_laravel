<?php

namespace App\Services;

use App\Enums\DepositStatus;
use App\Enums\OrderStatus;
use App\Models\Deposit;
use App\Models\Order;
use App\Models\User;
use App\Support\DepositSettler;
use App\Support\OrderPaymentSettler;
use Closure;
use Illuminate\Support\Facades\Http;
use Midtrans\Config as MidtransConfig;
use Midtrans\CoreApi;
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
    private function applyTransactionStatus(string $orderId, string $status, string $fraud): Deposit|Order|null
    {
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

            if ($status === 'expire' && $order->status === OrderStatus::Pending) {
                $order->update(['status' => OrderStatus::Expired]);

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
