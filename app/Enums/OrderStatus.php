<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Success = 'success';
    case Failed = 'failed';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Processing => 'Diproses',
            self::Success => 'Sukses',
            self::Failed => 'Gagal',
            self::Expired => 'Kedaluwarsa',
        };
    }

    /**
     * Kelas pill (warna token Atlantic) untuk status ini.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'bg-gold tx-gold',
            self::Processing => 'bg-sky-l tx-navy',
            self::Success => 'bg-mint-l tx-mintt',
            self::Failed => 'bg-errc-l tx-err',
            self::Expired => 'bg-glass tx-mute',
        };
    }
}
