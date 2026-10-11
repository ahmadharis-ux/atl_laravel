<?php

namespace App\Enums;

enum DepositStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Expired = 'expired';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Pembayaran',
            self::Paid => 'Berhasil',
            self::Expired => 'Kedaluwarsa',
            self::Failed => 'Gagal',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'schedule',
            self::Paid => 'check_circle',
            self::Expired => 'timer_off',
            self::Failed => 'error',
        };
    }

    /**
     * Kelas pill (warna token Atlantic) untuk status ini.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'bg-gold tx-gold',
            self::Paid => 'bg-mint-l tx-mintt',
            self::Expired => 'bg-glass tx-mute',
            self::Failed => 'bg-errc-l tx-err',
        };
    }

    public function isSettled(): bool
    {
        return $this !== self::Pending;
    }
}
