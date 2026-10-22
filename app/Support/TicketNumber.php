<?php

namespace App\Support;

use App\Models\Ticket;
use Illuminate\Support\Str;

/**
 * Pembuat kode tiket unik (mis. TKT-20261023-A1B2C3).
 */
final class TicketNumber
{
    public static function generate(): string
    {
        do {
            $code = 'TKT-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Ticket::query()->where('code', $code)->exists());

        return $code;
    }
}
