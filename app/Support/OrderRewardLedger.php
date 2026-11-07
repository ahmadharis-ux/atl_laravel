<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Enums\PointTransactionType;
use App\Enums\UserTier;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class OrderRewardLedger
{
    public static function estimate(int $saleAmount, int $supplierCost, int $companyCost, UserTier $tier): int
    {
        $margin = $saleAmount - $supplierCost - $companyCost;

        if ($margin <= 0) {
            return 0;
        }

        $earnPercent = (int) config('loyalty.earn_percent', 30);
        $rupiahPerPoint = (int) config('loyalty.rupiah_per_point', 10);

        if ($earnPercent < 0 || $earnPercent > 100 || $rupiahPerPoint < 1) {
            throw new \LogicException('Konfigurasi perhitungan poin reward tidak valid.');
        }

        $multiplier = $tier->rewardMultiplier();
        $denominator = 100 * $rupiahPerPoint * $multiplier['denominator'];

        return intdiv($margin * $earnPercent * $multiplier['numerator'], $denominator);
    }

    public static function award(Order $order): int
    {
        return DB::transaction(function () use ($order): int {
            $lockedOrder = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status !== OrderStatus::Success || $lockedOrder->refunded_at !== null) {
                return 0;
            }

            $existingAward = $lockedOrder->pointTransactions()
                ->where('type', PointTransactionType::OrderReward)
                ->value('points_change');

            if ($existingAward !== null) {
                return max(0, (int) $existingAward);
            }

            $user = User::query()->whereKey($lockedOrder->user_id)->lockForUpdate()->firstOrFail();
            $hasRewardSnapshot = $lockedOrder->supplier_cost_snapshot !== null;
            $isEligible = $lockedOrder->reward_eligible && $hasRewardSnapshot;
            $points = $isEligible
                ? self::estimate(
                    (int) $lockedOrder->amount,
                    (int) $lockedOrder->supplier_cost_snapshot,
                    (int) $lockedOrder->company_cost_snapshot,
                    $user->tier(),
                )
                : 0;

            $description = $isEligible
                ? 'Reward pembelian '.$lockedOrder->order_number
                : 'Reward tidak berlaku untuk pesanan '.$lockedOrder->order_number;

            $user->changePoints($points, PointTransactionType::OrderReward, $description, $lockedOrder);

            return $points;
        });
    }

    public static function reverseForRefund(Order $order): int
    {
        return DB::transaction(function () use ($order): int {
            $lockedOrder = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedOrder->refunded_at === null) {
                return 0;
            }

            $hasReversal = $lockedOrder->pointTransactions()
                ->where('type', PointTransactionType::OrderRewardReversed)
                ->exists();

            if ($hasReversal) {
                return 0;
            }

            $awardedPoints = (int) $lockedOrder->pointTransactions()
                ->where('type', PointTransactionType::OrderReward)
                ->sum('points_change');

            if ($awardedPoints < 1) {
                return 0;
            }

            $user = User::query()->whereKey($lockedOrder->user_id)->lockForUpdate()->firstOrFail();
            $user->changePoints(
                -$awardedPoints,
                PointTransactionType::OrderRewardReversed,
                'Pembatalan reward pesanan '.$lockedOrder->order_number,
                $lockedOrder,
            );

            return $awardedPoints;
        });
    }
}
