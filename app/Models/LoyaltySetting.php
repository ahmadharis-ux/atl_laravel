<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        return static::query()->orderBy('id')->first()
            ?? static::query()->create([
                'gold_points' => (int) config('loyalty.gold_points'),
                'platinum_points' => (int) config('loyalty.platinum_points'),
            ]);
    }
}
