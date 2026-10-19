<?php

namespace App\Enums;

enum MerchandiseRedemptionStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Diproses',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'schedule',
            self::Completed => 'check_circle',
            self::Cancelled => 'cancel',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'bg-gold tx-gold',
            self::Completed => 'bg-mint-l tx-mintt',
            self::Cancelled => 'bg-errc-l tx-err',
        };
    }
}
