<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Terbuka',
            self::InProgress => 'Diproses',
            self::Resolved => 'Selesai',
            self::Closed => 'Ditutup',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Open => 'bg-gold tx-gold',
            self::InProgress => 'bg-sky-l tx-navy',
            self::Resolved => 'bg-mint-l tx-mintt',
            self::Closed => 'bg-glass tx-mute',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Resolved, self::Closed], true);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
