<?php

namespace App\Enums;

use App\Models\LoyaltySetting;
use Throwable;

/**
 * Tingkatan loyalitas user berdasarkan akumulasi poin reward.
 * Threshold dikelola admin via tabel loyalty_settings, fallback config/loyalty.php.
 */
enum UserTier: string
{
    case Silver = 'silver';
    case Gold = 'gold';
    case Platinum = 'platinum';

    /**
     * @return array{gold: int, platinum: int}
     */
    public static function thresholds(): array
    {
        try {
            $setting = LoyaltySetting::current();

            $gold = (int) $setting->gold_points;
            $platinum = (int) $setting->platinum_points;
        } catch (Throwable) {
            $gold = (int) config('loyalty.gold_points');
            $platinum = (int) config('loyalty.platinum_points');
        }

        if ($platinum <= $gold) {
            $platinum = $gold + 1;
        }

        return ['gold' => $gold, 'platinum' => $platinum];
    }

    public static function fromPoints(int $points): self
    {
        $thresholds = self::thresholds();

        return match (true) {
            $points >= $thresholds['platinum'] => self::Platinum,
            $points >= $thresholds['gold'] => self::Gold,
            default => self::Silver,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Silver => 'Silver',
            self::Gold => 'Gold',
            self::Platinum => 'Platinum',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Silver => 'star',
            self::Gold => 'workspace_premium',
            self::Platinum => 'diamond',
        };
    }

    /**
     * Kelas CSS modifier untuk kartu wallet & badge (ditambahkan di atlantic.css).
     */
    public function cssClass(): string
    {
        return 'tier-'.$this->value;
    }

    /**
     * Poin minimum untuk menuju tier berikutnya; null bila sudah tier tertinggi.
     */
    public function nextThreshold(): ?int
    {
        return match ($this) {
            self::Silver => self::thresholds()['gold'],
            self::Gold => self::thresholds()['platinum'],
            self::Platinum => null,
        };
    }

    public function isMax(): bool
    {
        return $this === self::Platinum;
    }
}
