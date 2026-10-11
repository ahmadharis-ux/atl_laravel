<?php

namespace App\Enums;

enum WalletTransactionType: string
{
    case Deposit = 'deposit';
    case Purchase = 'purchase';
    case Refund = 'refund';
    case Adjustment = 'adjustment';
    case Reversal = 'reversal';

    public function label(): string
    {
        return match ($this) {
            self::Deposit => 'Top Up',
            self::Purchase => 'Pembelian',
            self::Refund => 'Refund',
            self::Adjustment => 'Penyesuaian',
            self::Reversal => 'Pembatalan',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Deposit => 'add_card',
            self::Purchase => 'shopping_bag',
            self::Refund => 'undo',
            self::Adjustment => 'tune',
            self::Reversal => 'cancel',
        };
    }

    /**
     * Kelas pill (warna token Atlantic) untuk jenis mutasi ini.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Deposit, self::Refund => 'bg-mint-l tx-mintt',
            self::Purchase => 'bg-tealc-m tx-tealt',
            self::Adjustment => 'bg-gold tx-gold',
            self::Reversal => 'bg-errc-l tx-err',
        };
    }
}
