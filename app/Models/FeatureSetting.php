<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan fitur halaman pengguna (singleton).
 *
 * Baris pertama dipakai sebagai konfigurasi aktif; dibuat otomatis dengan
 * nilai default bila belum ada. Bila sebuah fitur dinonaktifkan, halaman
 * pengguna yang terkait menampilkan "Coming Soon".
 */
class FeatureSetting extends Model
{
    protected $fillable = [
        'reseller_enabled',
        'ticket_enabled',
        'merchandise_enabled',
        'dashboard_promo_enabled',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'reseller_enabled' => true,
        'ticket_enabled' => true,
        'merchandise_enabled' => true,
        'dashboard_promo_enabled' => true,
    ];

    public static function current(): self
    {
        return static::query()->orderBy('id')->first()
            ?? static::query()->create(static::defaults());
    }

    /**
     * @return array<string, bool>
     */
    public static function defaults(): array
    {
        return [
            'reseller_enabled' => true,
            'ticket_enabled' => true,
            'merchandise_enabled' => true,
            'dashboard_promo_enabled' => true,
        ];
    }

    /**
     * Apakah sebuah fitur aktif. Kunci: reseller, ticket, merchandise.
     */
    public static function enabled(string $feature): bool
    {
        $column = $feature.'_enabled';

        if (! array_key_exists($column, static::defaults())) {
            return true;
        }

        return (bool) static::current()->getAttribute($column);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reseller_enabled' => 'boolean',
            'ticket_enabled' => 'boolean',
            'merchandise_enabled' => 'boolean',
            'dashboard_promo_enabled' => 'boolean',
        ];
    }
}
