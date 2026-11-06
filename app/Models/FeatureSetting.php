<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan fitur halaman pengguna dan metode pembayaran (singleton).
 *
 * Baris pertama dipakai sebagai konfigurasi aktif; dibuat otomatis dengan
 * nilai default bila belum ada. Bila sebuah fitur dinonaktifkan, halaman
 * pengguna yang terkait menampilkan "Coming Soon".
 */
class FeatureSetting extends Model
{
    protected $fillable = [
        'reseller_enabled',
        'reseller_discount_percent',
        'ticket_enabled',
        'merchandise_enabled',
        'dashboard_promo_enabled',
        'deposit_qris_enabled',
        'deposit_transfer_enabled',
        'deposit_ewallet_enabled',
        'transaction_saldo_enabled',
        'transaction_qris_enabled',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'reseller_enabled' => true,
        'reseller_discount_percent' => null,
        'ticket_enabled' => true,
        'merchandise_enabled' => true,
        'dashboard_promo_enabled' => true,
        'deposit_qris_enabled' => true,
        'deposit_transfer_enabled' => true,
        'deposit_ewallet_enabled' => true,
        'transaction_saldo_enabled' => true,
        'transaction_qris_enabled' => true,
    ];

    public static function current(): self
    {
        return static::query()->orderBy('id')->first()
            ?? static::query()->create(static::defaults());
    }

    /**
     * @return array<string, bool|float|null>
     */
    public static function defaults(): array
    {
        return [
            'reseller_enabled' => true,
            'reseller_discount_percent' => null,
            'ticket_enabled' => true,
            'merchandise_enabled' => true,
            'dashboard_promo_enabled' => true,
            'deposit_qris_enabled' => true,
            'deposit_transfer_enabled' => true,
            'deposit_ewallet_enabled' => true,
            'transaction_saldo_enabled' => true,
            'transaction_qris_enabled' => true,
        ];
    }

    /**
     * @return list<PaymentMethod>
     */
    public static function availablePaymentMethods(string $flow): array
    {
        $columns = match ($flow) {
            'deposit' => [
                PaymentMethod::Qris->value => 'deposit_qris_enabled',
                PaymentMethod::VirtualAccount->value => 'deposit_transfer_enabled',
                PaymentMethod::EWallet->value => 'deposit_ewallet_enabled',
            ],
            'transaction' => [
                PaymentMethod::Saldo->value => 'transaction_saldo_enabled',
                PaymentMethod::Qris->value => 'transaction_qris_enabled',
            ],
            default => [],
        };

        $setting = static::current();

        return array_values(array_map(
            PaymentMethod::from(...),
            array_keys(array_filter(
                $columns,
                fn (string $column): bool => (bool) $setting->getAttribute($column),
            )),
        ));
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

    public static function resellerDiscountPercent(): float
    {
        $value = static::current()->reseller_discount_percent;

        return $value === null
            ? (float) config('reseller.discount_percent', 0)
            : max(0, (float) $value);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reseller_enabled' => 'boolean',
            'reseller_discount_percent' => 'decimal:2',
            'ticket_enabled' => 'boolean',
            'merchandise_enabled' => 'boolean',
            'dashboard_promo_enabled' => 'boolean',
            'deposit_qris_enabled' => 'boolean',
            'deposit_transfer_enabled' => 'boolean',
            'deposit_ewallet_enabled' => 'boolean',
            'transaction_saldo_enabled' => 'boolean',
            'transaction_qris_enabled' => 'boolean',
        ];
    }
}
