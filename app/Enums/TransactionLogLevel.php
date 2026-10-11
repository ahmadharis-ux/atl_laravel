<?php

namespace App\Enums;

enum TransactionLogLevel: string
{
    case Info = 'info';
    case Warning = 'warning';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::Info => 'Info',
            self::Warning => 'Peringatan',
            self::Error => 'Error',
        };
    }

    /**
     * Kelas pill (warna token Atlantic) untuk level ini.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Info => 'bg-sky-l tx-navy',
            self::Warning => 'bg-gold tx-gold',
            self::Error => 'bg-errc-l tx-err',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Info => 'info',
            self::Warning => 'warning',
            self::Error => 'error',
        };
    }
}
