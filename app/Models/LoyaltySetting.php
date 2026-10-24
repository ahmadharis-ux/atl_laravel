<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Pengaturan tier loyalitas (singleton). Baris pertama dipakai sebagai
 * setting aktif; fallback ke config/loyalty.php bila belum ada baris.
 *
 * Cache hanya menyimpan ID baris (bukan objek model) supaya tidak pernah
 * menghasilkan __PHP_Incomplete_Class saat struktur kelas berubah.
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
        $id = Cache::remember('loyalty_setting.current_id', now()->addHour(), function (): int {
            return (int) (static::query()->orderBy('id')->value('id')
                ?? static::query()->create([
                    'gold_points' => (int) config('loyalty.gold_points'),
                    'platinum_points' => (int) config('loyalty.platinum_points'),
                ])->getKey());
        });

        $setting = $id > 0 ? static::query()->find($id) : null;

        if ($setting !== null) {
            return $setting;
        }

        Cache::forget('loyalty_setting.current_id');

        return static::query()->orderBy('id')->first()
            ?? static::query()->create([
                'gold_points' => (int) config('loyalty.gold_points'),
                'platinum_points' => (int) config('loyalty.platinum_points'),
            ]);
    }

    public static function flushCache(): void
    {
        Cache::forget('loyalty_setting.current');
        Cache::forget('loyalty_setting.current_id');
    }
}
