<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\MidtransService;
use App\Support\TransactionLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class RefundStorefrontOrder implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $orderId) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 60];
    }

    public function handle(MidtransService $midtrans): void
    {
        $order = Order::query()->find($this->orderId);

        if ($order === null || $order->source !== 'store_qris' || $order->refunded_at !== null) {
            return;
        }

        $shouldRequest = DB::transaction(function (): bool {
            $locked = Order::query()->whereKey($this->orderId)->lockForUpdate()->first();

            if ($locked === null
                || $locked->source !== 'store_qris'
                || $locked->refunded_at !== null
                || ! in_array($locked->status->value, ['failed', 'expired'], true)
                || in_array($locked->refund_status, ['completed', 'requested'], true)) {
                return false;
            }

            $locked->forceFill([
                'refund_status' => 'pending',
                'refund_requested_at' => $locked->refund_requested_at ?? now(),
            ])->save();

            return true;
        });

        if (! $shouldRequest) {
            return;
        }

        $order->refresh();
        $response = $midtrans->requestStorefrontRefund($order);
        $statusCode = (string) ($response->status_code ?? '');
        $refundAmount = isset($response->refund_amount) ? (float) $response->refund_amount : 0.0;
        $isApproved = $statusCode === '200'
            && (string) ($response->transaction_status ?? '') === 'refund'
            && $refundAmount >= $order->totalDue();

        DB::transaction(function () use ($order, $response, $isApproved): void {
            $locked = Order::query()->whereKey($order->getKey())->lockForUpdate()->first();

            if ($locked === null || $locked->refunded_at !== null) {
                return;
            }

            $metadata = $locked->metadata ?? [];
            $metadata['midtrans_refund'] = [
                'status' => $isApproved ? 'completed' : 'manual_required',
                'refund_key' => $response->refund_key ?? 'store-refund-'.$locked->order_number,
                'status_code' => $response->status_code ?? null,
                'status_message' => $response->status_message ?? null,
                'refund_amount' => $response->refund_amount ?? null,
            ];

            $locked->forceFill([
                'refund_status' => $isApproved ? 'completed' : 'manual_required',
                'refund_transaction_id' => $response->transaction_id ?? null,
                'refunded_at' => $isApproved ? now() : null,
                'metadata' => $metadata,
            ])->save();

            if ($isApproved) {
                TransactionLogger::info('order.storefront_refunded', 'Refund QRIS toko reseller disetujui Midtrans.', [
                    'order_number' => $locked->order_number,
                    'amount' => $locked->totalDue(),
                ], $locked, $locked->user);
            } else {
                TransactionLogger::warning('order.storefront_refund_manual', 'Refund QRIS toko reseller memerlukan penanganan admin.', [
                    'order_number' => $locked->order_number,
                    'status_code' => $response->status_code ?? null,
                    'status_message' => $response->status_message ?? null,
                ], $locked, $locked->user);
            }
        });
    }

    public function failed(Throwable $exception): void
    {
        report($exception);

        DB::transaction(function () use ($exception): void {
            $order = Order::query()->whereKey($this->orderId)->lockForUpdate()->first();

            if ($order === null || $order->refunded_at !== null) {
                return;
            }

            $metadata = $order->metadata ?? [];
            $metadata['midtrans_refund'] = [
                'status' => 'manual_required',
                'error' => $exception->getMessage(),
            ];

            $order->forceFill([
                'refund_status' => 'manual_required',
                'metadata' => $metadata,
            ])->save();

            TransactionLogger::error('order.storefront_refund_failed', 'Refund QRIS otomatis gagal setelah beberapa percobaan.', $exception, [
                'order_number' => $order->order_number,
            ], $order, $order->user);
        });
    }
}
