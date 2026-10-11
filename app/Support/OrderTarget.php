<?php

namespace App\Support;

use App\Models\Product;

/**
 * Label & aturan input tujuan untuk checkout produk.
 *
 * Label kolom tujuan menyesuaikan kategori produk (ID game, nomor meter PLN,
 * nomor HP e-wallet, dsb). Alur pulsa & paket data dikelola di halaman
 * terpisah (deteksi operator), jadi belum memakai checkout ini.
 */
final class OrderTarget
{
    /**
     * Kategori yang belum boleh checkout lewat halaman umum.
     *
     * @var list<string>
     */
    private const EXCLUDED = Product::OPERATOR_CATEGORY_SLUGS;

    /**
     * Label kolom tujuan per slug kategori.
     *
     * @var array<string, string>
     */
    private const LABELS = [
        'games' => 'ID Game',
        'tagihan' => 'Nomor Pelanggan',
        'pln' => 'Nomor Meter / ID Pelanggan',
        'gas' => 'Nomor Pelanggan',
        'e-money' => 'Nomor HP',
        'voucher' => 'Kode / ID Tujuan',
        'aktivasi-voucher' => 'Kode Voucher',
        'hiburan' => 'ID / Email Akun',
        'streaming' => 'ID / Email Akun',
        'tv' => 'ID / Nomor Pelanggan',
        'esim' => 'Nomor HP',
        'malaysia-topup' => 'ID Game / Nomor HP',
        'philippines-topup' => 'ID Game / Nomor HP',
        'thailand-topup' => 'ID Game / Nomor HP',
        'singapore-topup' => 'ID Game / Nomor HP',
        'vietnam-topup' => 'ID Game / Nomor HP',
    ];

    public static function supportsCheckout(?string $categorySlug): bool
    {
        return ! in_array($categorySlug, self::EXCLUDED, true);
    }

    public static function label(?string $categorySlug): string
    {
        return self::LABELS[$categorySlug] ?? 'Nomor Tujuan';
    }

    public static function placeholder(?string $categorySlug): string
    {
        return 'Masukkan '.mb_strtolower(self::label($categorySlug));
    }

    public static function zoneLabel(): string
    {
        return 'ID Server / Zona';
    }

    /**
     * Zona hanya diminta untuk brand game yang membutuhkannya.
     */
    public static function requiresZone(?string $brandSlug): bool
    {
        return $brandSlug !== null && $brandSlug !== '' && GameNickname::requiresZone($brandSlug);
    }
}
