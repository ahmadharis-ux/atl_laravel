<?php

namespace App\Support\Report;

/**
 * Format nilai mentah sebuah kolom laporan untuk tampilan (blade/PDF).
 */
final class ReportFormatter
{
    public static function display(string $type, mixed $value): string
    {
        if ($value === null) {
            return '-';
        }

        return match ($type) {
            'currency' => 'Rp '.number_format((float) $value, 0, ',', '.'),
            'int' => number_format((float) $value, 0, ',', '.'),
            'percent' => number_format((float) $value, 1, ',', '.').'%',
            default => (string) $value,
        };
    }
}
