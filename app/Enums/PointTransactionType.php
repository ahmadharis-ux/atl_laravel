<?php

namespace App\Enums;

/**
 * Sumber perubahan poin reward pengguna, dicatat di tabel point_transactions.
 */
enum PointTransactionType: string
{
    case OrderReward = 'order_reward';
    case OrderRewardReversed = 'order_reward_reversed';
    case TopupReward = 'topup_reward';
    case MerchandiseRedeemed = 'merchandise_redeemed';
    case MerchandiseRefund = 'merchandise_refund';

    public function label(): string
    {
        return match ($this) {
            self::OrderReward => 'Bonus Pembelian',
            self::OrderRewardReversed => 'Pembatalan Bonus Pembelian',
            self::TopupReward => 'Bonus Top Up',
            self::MerchandiseRedeemed => 'Tukar Merchandise',
            self::MerchandiseRefund => 'Pengembalian Poin',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::OrderReward => 'shopping_bag',
            self::OrderRewardReversed => 'undo',
            self::TopupReward => 'account_balance_wallet',
            self::MerchandiseRedeemed => 'redeem',
            self::MerchandiseRefund => 'undo',
        };
    }
}
