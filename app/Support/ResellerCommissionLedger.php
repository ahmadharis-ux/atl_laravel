<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\ResellerCommission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ResellerCommissionLedger
{
    public static function creditForSuccessfulOrder(Order $order): void
    {
        if ($order->source !== 'store_qris' || $order->status !== OrderStatus::Success) {
            return;
        }

        $storefront = $order->metadata['storefront'] ?? [];
        $amount = (int) ($storefront['selling_price'] ?? 0) - (int) ($storefront['reseller_price'] ?? 0);

        if ($amount <= 0) {
            return;
        }

        DB::transaction(function () use ($order, $amount): void {
            $reseller = User::query()->whereKey($order->user_id)->lockForUpdate()->first();

            if ($reseller === null || ResellerCommission::query()->where('order_id', $order->getKey())->exists()) {
                return;
            }

            ResellerCommission::query()->create([
                'user_id' => $reseller->getKey(),
                'order_id' => $order->getKey(),
                'amount' => $amount,
            ]);
        });
    }
}
