<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\WalletTransactionType;
use App\Jobs\SendDigiflazzTransaction;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Pemenuhan pesanan ber-provider (Digiflazz): mengirim transaksi, menafsirkan
 * hasilnya (baik dari respons langsung maupun webhook), dan mengembalikan
 * saldo/stok bila transaksi gagal.
 *
 * Seluruh perubahan status dijaga idempoten: pesanan yang sudah final tidak
 * diproses ulang, dan refund hanya diberikan sekali.
 */
final class OrderFulfillment
{
    /**
     * Mulai proses pemenuhan untuk pesanan yang sudah lunas.
     */
    public static function start(Order $order): void
    {
        if (! $order->usesProviderFulfillment() || $order->status !== OrderStatus::Processing) {
            return;
        }

        SendDigiflazzTransaction::dispatch($order->getKey());
    }

    /**
     * Kirim pesanan ke Digiflazz. Dipanggil dari antrean.
     */
    public static function send(Order $order): void
    {
        $order->refresh();

        if (! $order->usesProviderFulfillment() || $order->status !== OrderStatus::Processing) {
            return;
        }

        $product = $order->product()->first();

        if ($product === null) {
            self::fail($order, 'Produk untuk pesanan ini tidak ditemukan.');

            return;
        }

        $order->forceFill(['processed_at' => now()])->save();

        try {
            $data = Digiflazz::make()->topup(
                (string) $product->code,
                $order->providerCustomerNo(),
                (string) $order->provider_ref,
                testing: Digiflazz::sandbox(),
            );
        } catch (Throwable $e) {
            TransactionLogger::error('digiflazz.topup_failed', 'Gagal mengirim transaksi ke Digiflazz.', $e, [
                'order_number' => $order->order_number,
                'provider_ref' => $order->provider_ref,
            ], $order, $order->user);

            throw $e;
        }

        self::applyResult($order, $data);
    }

    /**
     * Simpan status terbaru dari provider (respons topup atau webhook).
     *
     * @param  array<string, mixed>  $data
     */
    public static function applyResult(Order $order, array $data): void
    {
        DB::transaction(function () use ($order, $data): void {
            $locked = Order::query()->whereKey($order->getKey())->lockForUpdate()->first();

            if ($locked === null) {
                return;
            }

            $locked->forceFill([
                'provider_status' => $data['status'] ?? $locked->provider_status,
                'provider_sn' => $data['sn'] ?? $locked->provider_sn,
                'provider_price' => is_numeric($data['price'] ?? null) ? (int) $data['price'] : $locked->provider_price,
                'provider_admin' => is_numeric($data['admin'] ?? null) ? (int) $data['admin'] : $locked->provider_admin,
                'provider_payload' => $data,
            ])->save();

            if ($locked->status->isFinal()) {
                return;
            }

            $status = self::normalizeStatus($data);

            if ($status === OrderStatus::Success) {
                self::markSuccess($locked, $data);
            } elseif ($status === OrderStatus::Failed) {
                self::markFailed($locked, (string) ($data['message'] ?? 'Transaksi gagal di provider.'));
            }
        });
    }

    /**
     * Tandai pesanan gagal (mis. antrean habis percobaan) tanpa hasil dari
     * provider.
     */
    public static function fail(Order $order, string $message, ?array $payload = null): void
    {
        DB::transaction(function () use ($order, $message, $payload): void {
            $locked = Order::query()->whereKey($order->getKey())->lockForUpdate()->first();

            if ($locked === null || $locked->status->isFinal()) {
                return;
            }

            if ($payload !== null) {
                $locked->forceFill(['provider_payload' => $payload])->save();
            }

            self::markFailed($locked, $message);
        });
    }

    /**
     * Kedaluwarsakan pesanan yang tidak dibayar dan kembalikan stoknya.
     */
    public static function expire(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $locked = Order::query()->whereKey($order->getKey())->lockForUpdate()->first();

            if ($locked === null || $locked->status->isFinal()) {
                return;
            }

            $locked->forceFill(['status' => OrderStatus::Expired])->save();

            self::restoreStock($locked);

            TransactionLogger::info('order.expired', 'Pesanan kedaluwarsa karena tidak dibayar.', [
                'order_number' => $locked->order_number,
            ], $locked, $locked->user);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function markSuccess(Order $order, array $data): void
    {
        $order->forceFill(['status' => OrderStatus::Success])->save();

        TransactionLogger::info('order.fulfilled', 'Transaksi berhasil diproses provider.', [
            'order_number' => $order->order_number,
            'provider_status' => $data['status'] ?? null,
            'sn' => $data['sn'] ?? null,
        ], $order, $order->user);
    }

    private static function markFailed(Order $order, string $message): void
    {
        $order->forceFill([
            'status' => OrderStatus::Failed,
            'notes' => trim(($order->notes ? $order->notes.' | ' : '').$message),
        ])->save();

        self::refund($order);
        self::restoreStock($order);

        TransactionLogger::warning('order.failed', 'Transaksi gagal diproses provider: '.$message, [
            'order_number' => $order->order_number,
            'provider_status' => $order->provider_status,
        ], $order, $order->user);
    }

    /**
     * Refund manual oleh admin untuk pesanan gagal. Bila dibayar via saldo,
     * saldo dikembalikan otomatis; bila non-saldo, hanya ditandai sudah
     * direfund (dana dikembalikan di luar sistem).
     *
     * @return bool true bila status refund berubah
     */
    public static function refundManually(Order $order): bool
    {
        return DB::transaction(function () use ($order): bool {
            $locked = Order::query()->whereKey($order->getKey())->lockForUpdate()->first();

            if ($locked === null || $locked->status !== OrderStatus::Failed || $locked->refunded_at !== null) {
                return false;
            }

            if ($locked->payment_method === PaymentMethod::Saldo) {
                $user = $locked->user()->first();

                if ($user !== null) {
                    WalletLedger::credit($user, (int) $locked->amount, WalletTransactionType::Refund, $locked, 'Refund '.$locked->order_number, [
                        'reason' => 'manual_refund',
                    ]);
                }
            }

            $locked->forceFill(['refunded_at' => now()])->save();

            TransactionLogger::info('order.refunded_manual', 'Refund manual diproses admin.', [
                'order_number' => $locked->order_number,
                'payment_method' => $locked->payment_method->value,
                'amount' => $locked->amount,
            ], $locked, $locked->user);

            return true;
        });
    }

    /**
     * Kembalikan saldo user (sekali saja) bila pesanan dibayar dari saldo.
     */
    private static function refund(Order $order): void
    {
        if ($order->refunded_at !== null) {
            return;
        }

        $user = $order->user()->first();

        if ($user === null) {
            return;
        }

        if ($order->payment_method !== PaymentMethod::Saldo) {
            TransactionLogger::warning('order.refund_manual', 'Pesanan gagal namun dibayar non-saldo; refund perlu diproses manual.', [
                'order_number' => $order->order_number,
                'payment_method' => $order->payment_method->value,
            ], $order, $user);

            return;
        }

        WalletLedger::credit($user, (int) $order->amount, WalletTransactionType::Refund, $order, 'Refund '.$order->order_number, [
            'reason' => 'provider_failed',
        ]);

        $order->forceFill(['refunded_at' => now()])->save();

        TransactionLogger::info('order.refunded', 'Saldo dikembalikan karena transaksi gagal.', [
            'order_number' => $order->order_number,
            'amount' => $order->amount,
        ], $order, $user);
    }

    /**
     * Kembalikan stok & kurangi jumlah terjual saat pesanan gagal.
     */
    private static function restoreStock(Order $order): void
    {
        $product = Product::query()->whereKey($order->product_id)->lockForUpdate()->first();

        if ($product === null) {
            return;
        }

        if (! $product->unlimited_stock) {
            $product->increment('stock');
        }

        if ($product->sold_count > 0) {
            $product->decrement('sold_count');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function normalizeStatus(array $data): OrderStatus
    {
        $status = strtolower(trim((string) ($data['status'] ?? '')));

        return match ($status) {
            'sukses', 'success' => OrderStatus::Success,
            'gagal', 'failed' => OrderStatus::Failed,
            'pending' => OrderStatus::Processing,
            default => ($data['status'] ?? '') === '' && ($data['rc'] ?? '00') !== '00'
                ? OrderStatus::Failed
                : OrderStatus::Processing,
        };
    }
}
