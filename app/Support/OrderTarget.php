<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Validation\ValidationException;

/**
 * Label & aturan input tujuan untuk checkout produk.
 *
 * Label kolom tujuan menyesuaikan kategori produk (ID game, nomor meter PLN,
 * nomor HP, dsb). Pulsa & paket data memilih paketnya di halaman /pulsa
 * (deteksi operator), tetapi pembeliannya tetap lewat checkout yang sama.
 */
final class OrderTarget
{
    /**
     * Label kolom tujuan per slug kategori.
     *
     * @var array<string, string>
     */
    private const LABELS = [
        'pulsa' => 'Nomor HP',
        'data' => 'Nomor HP',
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

    public static function label(?string $categorySlug): string
    {
        return self::LABELS[$categorySlug] ?? 'Nomor Tujuan';
    }

    public static function placeholder(?string $categorySlug): string
    {
        return 'Masukkan '.mb_strtolower(self::label($categorySlug));
    }

    public static function hint(?string $categorySlug): string
    {
        return match ($categorySlug) {
            'pulsa', 'data', 'e-money', 'esim' => 'Contoh nomor HP: 081234567890. Pastikan nomor aktif dan sesuai produk yang dipilih.',
            'games' => 'Masukkan ID akun persis seperti yang tampil pada profil game. Pastikan nama akun sesuai sebelum melanjutkan.',
            'tagihan', 'gas' => 'Masukkan nomor pelanggan persis seperti yang tercantum pada tagihan.',
            'pln' => 'Masukkan nomor meter atau ID pelanggan yang tercantum pada meter atau tagihan.',
            'aktivasi-voucher' => 'Masukkan kode persis seperti yang tertera pada voucher dan ikuti ketentuan huruf besar/kecil.',
            'hiburan', 'streaming' => 'Masukkan ID akun atau email yang digunakan untuk login ke layanan.',
            'tv' => 'Masukkan ID atau nomor pelanggan persis seperti yang tercantum pada tagihan.',
            'voucher' => 'Periksa deskripsi produk untuk memastikan apakah yang diminta adalah kode atau ID tujuan.',
            'malaysia-topup', 'philippines-topup', 'thailand-topup', 'singapore-topup', 'vietnam-topup' => 'Ikuti format ID akun atau nomor HP yang diminta pada deskripsi produk dan pastikan wilayahnya sesuai.',
            default => 'Periksa deskripsi produk untuk mengetahui format tujuan yang diminta.',
        };
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

    /**
     * Untuk produk pulsa/paket data, operator nomor tujuan wajib sama dengan
     * brand produk. Mencegah pembeli paket Telkomsel untuk nomor Indosat.
     *
     * @throws ValidationException bila operator nomor tidak cocok dengan brand produk.
     */
    public static function ensureOperatorMatches(Product $product, string $target): void
    {
        $categorySlug = (string) $product->category?->slug;

        if (! in_array($categorySlug, Product::OPERATOR_CATEGORY_SLUGS, true)) {
            return;
        }

        $detected = PhoneOperator::detect($target);
        $productBrandSlug = (string) $product->brand?->slug;

        if ($detected !== null && $productBrandSlug !== '' && $detected['brand_slug'] !== $productBrandSlug) {
            throw ValidationException::withMessages([
                'target' => 'Nomor ini terdeteksi operator '.$detected['operator'].'. Silakan pilih produk yang sesuai.',
            ]);
        }
    }
}
