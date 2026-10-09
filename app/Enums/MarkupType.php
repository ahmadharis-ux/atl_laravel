<?php

namespace App\Enums;

enum MarkupType: string
{
    case Fixed = 'fixed';
    case Percent = 'percent';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Nominal',
            self::Percent => 'Persen',
        };
    }

    /**
     * Terapkan markup pada harga modal (rupiah bulat).
     */
    public function apply(int $costPrice, float $markupValue): int
    {
        return match ($this) {
            self::Fixed => (int) round($costPrice + $markupValue),
            self::Percent => (int) round($costPrice + ($costPrice * $markupValue / 100)),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
