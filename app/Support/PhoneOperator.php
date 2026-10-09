<?php

namespace App\Support;

/**
 * Deteksi operator seluler Indonesia berdasarkan prefix nomor HP.
 *
 * Prefix dicek dari yang paling spesifik (5 digit) ke paling umum (4 digit),
 * sehingga sub-prefix seperti by.U (08514–08518) menang atas Telkomsel (0851).
 */
final class PhoneOperator
{
    /**
     * @var array<string, list<string>>
     */
    private const OPERATORS = [
        'by.U' => ['08514', '08515', '08516', '08517', '08518'],
        'TELKOMSEL' => ['0811', '0812', '0813', '0821', '0822', '0823', '0851', '0852', '0853'],
        'INDOSAT' => ['0814', '0815', '0816', '0855', '0856', '0857', '0858'],
        'XL' => ['0817', '0818', '0819', '0859', '0877', '0878'],
        'AXIS' => ['0831', '0832', '0833', '0838'],
        'TRI' => ['0895', '0896', '0897', '0898', '0899'],
        'SMARTFREN' => ['0881', '0882', '0883', '0884', '0885', '0886', '0887', '0888', '0889'],
    ];

    /**
     * @var array<string, string>
     */
    private const BRAND_SLUGS = [
        'by.U' => 'byu',
        'TELKOMSEL' => 'telkomsel',
        'INDOSAT' => 'indosat',
        'XL' => 'xl',
        'AXIS' => 'axis',
        'TRI' => 'tri',
        'SMARTFREN' => 'smartfren',
    ];

    /**
     * Normalisasi nomor ke format lokal (08xxxxxxxxxx).
     */
    public static function normalize(string $number): string
    {
        $digits = preg_replace('/\D/', '', $number) ?? '';

        if (str_starts_with($digits, '62')) {
            return '0'.substr($digits, 2);
        }

        if ($digits !== '' && ! str_starts_with($digits, '0')) {
            return '0'.$digits;
        }

        return $digits;
    }

    /**
     * Deteksi operator dari nomor HP. Mengembalikan null bila tidak dikenali.
     *
     * @return array{operator: string, brand_slug: string}|null
     */
    public static function detect(string $number): ?array
    {
        $normalized = self::normalize($number);

        if (strlen($normalized) < 10 || strlen($normalized) > 15 || ! str_starts_with($normalized, '08')) {
            return null;
        }

        $best = null;
        $bestLength = 0;

        foreach (self::OPERATORS as $operator => $prefixes) {
            foreach ($prefixes as $prefix) {
                $length = strlen($prefix);

                if ($length > $bestLength && str_starts_with($normalized, $prefix)) {
                    $best = $operator;
                    $bestLength = $length;
                }
            }
        }

        if ($best === null) {
            return null;
        }

        return ['operator' => $best, 'brand_slug' => self::BRAND_SLUGS[$best]];
    }
}
