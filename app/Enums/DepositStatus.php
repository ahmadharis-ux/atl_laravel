<?php

namespace App\Enums;

enum DepositStatus: string
{
    case Pending = 'pending';
    case UnderReview = 'under_review';
    case NeedsRevision = 'needs_revision';
    case Paid = 'paid';
    case Expired = 'expired';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Pembayaran',
            self::UnderReview => 'Menunggu Verifikasi Admin',
            self::NeedsRevision => 'Perlu Perbaikan Bukti',
            self::Paid => 'Berhasil',
            self::Expired => 'Kedaluwarsa',
            self::Failed => 'Gagal',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'schedule',
            self::UnderReview => 'hourglass_top',
            self::NeedsRevision => 'edit_document',
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
            self::UnderReview => 'bg-gold tx-gold',
            self::NeedsRevision => 'bg-errc-l tx-err',
            self::Paid => 'bg-mint-l tx-mintt',
            self::Expired => 'bg-glass tx-mute',
            self::Failed => 'bg-errc-l tx-err',
        };
    }

    public function isSettled(): bool
    {
        return in_array($this, [self::Paid, self::Expired, self::Failed], true);
    }
}
