<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Support\Str;

/**
 * Pembuat nomor pesanan unik (mis. TRX-20261020-AB12CD).
 */
final class OrderNumber
{
    public static function generate(): string
    {
        do {
            $number = 'TRX-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }
}
