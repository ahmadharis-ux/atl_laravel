<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Pengaturan tier loyalitas (singleton). Baris pertama dipakai sebagai
 * setting aktif; fallback ke config/loyalty.php bila belum ada baris.
 */
class LoyaltySetting extends Model
{
    protected $fillable = [
        'gold_points',
        'platinum_points',
    ];

    protected $casts = [
        'gold_points' => 'integer',
        'platinum_points' => 'integer',
    ];

    public static function current(): self
    {
        return Cache::remember('loyalty_setting.current', now()->addHour(), function (): self {
            return static::query()->orderBy('id')->first()
                ?? static::query()->create([
                    'gold_points' => (int) config('loyalty.gold_points'),
                    'platinum_points' => (int) config('loyalty.platinum_points'),
                ]);
        });
    }

    public static function flushCache(): void
    {
        Cache::forget('loyalty_setting.current');
    }
}
