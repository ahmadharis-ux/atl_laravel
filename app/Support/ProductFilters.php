<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Saringan & pengurutan katalog produk bersama (harga jual dihitung dari
 * markup). Dipakai halaman /produk dan /pulsa.
 */
final class ProductFilters
{
    public const SORT_DEFAULT = '';

    public const SORT_PRICE_ASC = 'price_asc';

    public const SORT_PRICE_DESC = 'price_desc';

    /**
     * Batas harga dari request; hanya digit yang dipakai dan nilai nol atau
     * kosong dianggap tidak diisi.
     */
    public static function priceBound(Request $request, string $key): ?int
    {
        $raw = $request->query($key);

        if (! is_string($raw) && ! is_numeric($raw)) {
            return null;
        }

        $value = (int) preg_replace('/\D/', '', (string) $raw);

        return $value > 0 ? $value : null;
    }

    /**
     * Opsi urutan yang valid; nilai lain jatuh ke urutan bawaan.
     */
    public static function sort(Request $request): string
    {
        $sort = (string) $request->query('sort');

        return in_array($sort, [self::SORT_PRICE_ASC, self::SORT_PRICE_DESC], true)
            ? $sort
            : self::SORT_DEFAULT;
    }

    /**
     * @return array<string, string>
     */
    public static function sortOptions(): array
    {
        return [
            self::SORT_DEFAULT => 'Urutkan: Terkait',
            self::SORT_PRICE_ASC => 'Harga: Termurah',
            self::SORT_PRICE_DESC => 'Harga: Termahal',
        ];
    }

    public static function applyOrdering(Builder $query, string $sort): void
    {
        match ($sort) {
            self::SORT_PRICE_ASC => $query->orderByPrice('asc'),
            self::SORT_PRICE_DESC => $query->orderByPrice('desc'),
            default => $query->orderBy('sort_order')->orderBy('name'),
        };
    }
}
