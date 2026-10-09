<?php

namespace App\Enums;

enum ProductPaymentType: string
{
    case Prabayar = 'prabayar';
    case Pascabayar = 'pascabayar';

    public function label(): string
    {
        return match ($this) {
            self::Prabayar => 'Prabayar',
            self::Pascabayar => 'Pascabayar',
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
