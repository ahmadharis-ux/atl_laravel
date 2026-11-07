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

    /**
     * @return array{numerator: int, denominator: int}
     */
    public function rewardMultiplier(): array
    {
        return match ($this) {
            self::Silver => ['numerator' => 1, 'denominator' => 1],
            self::Gold => ['numerator' => 5, 'denominator' => 4],
            self::Platinum => ['numerator' => 3, 'denominator' => 2],
        };
    }

    public function rewardMultiplierLabel(): string
    {
        return match ($this) {
            self::Silver => '1x',
            self::Gold => '1,25x',
            self::Platinum => '1,5x',
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
