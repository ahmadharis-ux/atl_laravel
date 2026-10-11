<?php

namespace App\Enums;

enum WalletTransactionDirection: string
{
    case Credit = 'credit';
    case Debit = 'debit';

    public function label(): string
    {
        return match ($this) {
            self::Credit => 'Masuk',
            self::Debit => 'Keluar',
        };
    }

    public function sign(): int
    {
        return $this === self::Credit ? 1 : -1;
    }
}
