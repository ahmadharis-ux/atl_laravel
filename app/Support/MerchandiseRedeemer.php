<?php

namespace App\Support;

use App\Enums\MerchandiseRedemptionStatus;
use App\Enums\PointTransactionType;
use App\Models\Merchandise;
use App\Models\MerchandiseRedemption;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Menukar poin user dengan merchandise secara atomik: poin dipotong dan
 * stok dikurangi dalam satu transaksi (baris user & merchandise di-lock),
 * sehingga penukaran barengan tidak membuat stok/poin minus.
 *
 * Pembatalan oleh admin mengembalikan poin dan stok.
 */
final class MerchandiseRedeemer
{
    /**
     * Tukar poin user dengan merchandise. Mengembalikan redemption berstatus
     * pending; melempar \Illuminate\Validation\ValidationException bila poin
     * kurang, stok habis, atau merchandise tidak aktif.
     */
    public static function redeem(User $user, Merchandise $merchandise): MerchandiseRedemption
    {
        return DB::transaction(function () use ($user, $merchandise): MerchandiseRedemption {
            $lockedUser = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $lockedItem = Merchandise::query()->whereKey($merchandise->getKey())->lockForUpdate()->firstOrFail();

            if (! $lockedItem->is_active) {
                throw ValidationException::withMessages([
                    'merchandise' => 'Merchandise tidak tersedia.',
                ]);
            }

            if ($lockedItem->stock < 1) {
                throw ValidationException::withMessages([
                    'merchandise' => 'Stok merchandise habis.',
                ]);
            }

            $cost = (int) $lockedItem->points_cost;

            if ((int) $lockedUser->points < $cost) {
                throw ValidationException::withMessages([
                    'points' => 'Poin kamu tidak mencukupi. Butuh '.$cost.' poin.',
                ]);
            }

            $lockedUser->changePoints(
                -$cost,
                PointTransactionType::MerchandiseRedeemed,
                'Tukar poin: '.$lockedItem->name,
                $lockedItem,
            );
            $lockedItem->decrement('stock');

            $redemption = MerchandiseRedemption::create([
                'user_id' => $lockedUser->getKey(),
                'merchandise_id' => $lockedItem->getKey(),
                'points_cost' => $cost,
                'status' => MerchandiseRedemptionStatus::Pending,
            ]);

            TransactionLogger::info('merchandise.redeemed', 'Penukaran poin ke merchandise.', [
                'merchandise' => $lockedItem->name,
                'points_cost' => $cost,
                'points_after' => (int) $lockedUser->points,
            ], null, $lockedUser);

            return $redemption;
        });
    }

    /**
     * Tandai penukaran selesai (admin sudah memproses pengiriman/serah terima).
     */
    public static function complete(MerchandiseRedemption $redemption, ?string $notes = null): bool
    {
        $updated = MerchandiseRedemption::query()
            ->whereKey($redemption->getKey())
            ->where('status', MerchandiseRedemptionStatus::Pending->value)
            ->update([
                'status' => MerchandiseRedemptionStatus::Completed->value,
                'notes' => $notes,
            ]);

        return $updated > 0;
    }

    /**
     * Batalkan penukaran: kembalikan poin ke user dan stok ke merchandise.
     * Hanya penukaran pending yang dapat dibatalkan.
     */
    public static function cancel(MerchandiseRedemption $redemption, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($redemption, $notes): bool {
            $locked = MerchandiseRedemption::query()
                ->whereKey($redemption->getKey())
                ->lockForUpdate()
                ->first();

            if ($locked === null || $locked->status !== MerchandiseRedemptionStatus::Pending) {
                return false;
            }

            $user = User::query()->whereKey($locked->user_id)->lockForUpdate()->first();
            $item = Merchandise::query()->whereKey($locked->merchandise_id)->lockForUpdate()->first();

            if ($user !== null) {
                $user->changePoints(
                    (int) $locked->points_cost,
                    PointTransactionType::MerchandiseRefund,
                    'Pembatalan penukaran poin',
                    $item,
                );
            }

            $item?->increment('stock');

            $locked->forceFill([
                'status' => MerchandiseRedemptionStatus::Cancelled,
                'notes' => $notes,
            ])->save();

            TransactionLogger::warning('merchandise.redemption_cancelled', 'Penukaran dibatalkan dan poin dikembalikan.', [
                'points_cost' => (int) $locked->points_cost,
                'notes' => $notes,
            ], null, $user);

            return true;
        });
    }
}
