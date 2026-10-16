<?php

namespace App\Support;

/**
 * Peta wilayah (region) produk topup game dari nama produk Digiflazz.
 *
 * Digiflazz menandai wilayah lewat token di dalam kurung pada nama produk
 * (mis. "Mobile Legends 112 Diamonds (102+10) (Malaysia)"). Token itu
 * diekstrak saat sinkronisasi daftar harga dan disimpan ke kolom
 * `products.region`. Produk tanpa token wilayah dianggap umum (null).
 */
final class ProductRegion
{
    /**
     * Label wilayah per kata kunci (sudah dinormalisasi huruf kecil).
     * Indonesia sengaja dipetakan ke null agar digabung dengan produk umum.
     *
     * @var array<string, string>
     */
    private const KEYWORDS = [
        'malaysia' => 'Malaysia',
        'singapore' => 'Singapore',
        'singapura' => 'Singapore',
        'philippines' => 'Filipina',
        'filipina' => 'Filipina',
        'thippines' => 'Filipina',
        'thailand' => 'Thailand',
        'vietnam' => 'Vietnam',
        'brazil' => 'Brazil',
        'brasil' => 'Brazil',
        'russia' => 'Russia',
        'rusia' => 'Russia',
        'turkey' => 'Turkey',
        'turki' => 'Turkey',
        'global' => 'Global',
    ];

    /**
     * Ekstrak wilayah dari nama produk. Mengembalikan null bila produk umum.
     */
    public static function parse(string $productName): ?string
    {
        preg_match_all('/\(([^)]+)\)/', $productName, $matches);

        foreach ($matches[1] as $token) {
            $normalized = strtolower(trim($token));

            if (array_key_exists($normalized, self::KEYWORDS)) {
                return self::KEYWORDS[$normalized];
            }
        }

        return null;
    }

    /**
     * Label tampil untuk sebuah wilayah (null = Umum).
     */
    public static function label(?string $region): string
    {
        return $region ?? 'Umum';
    }

    /**
     * Nilai filter "tanpa wilayah" (produk umum).
     */
    public const NONE = '__none__';
}
