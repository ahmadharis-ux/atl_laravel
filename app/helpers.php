<?php

if (! function_exists('format_short')) {
    function format_short(int|float $n): string
    {
        $abs = abs($n);

        [$value, $suffix, $decimals] = match (true) {
            $abs >= 1_000_000_000 => [$n / 1_000_000_000, 'M', 2],
            $abs >= 1_000_000     => [$n / 1_000_000,     'Jt', 2],
            $abs >= 1_000         => [$n / 1_000,         'K', 1],
            default               => [$n, '', 0],
        };

        $formatted = number_format($value, $decimals, ',', '.');

        // buang nol di belakang koma: 50,0 -> 50 | 1,50 -> 1,5
        if ($decimals > 0) {
            $formatted = rtrim(rtrim($formatted, '0'), ',');
        }

        return $formatted . $suffix;
    }
}