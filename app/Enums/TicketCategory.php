<?php

namespace App\Enums;

enum TicketCategory: string
{
    case Complaint = 'complaint';
    case Suggestion = 'suggestion';
    case Supplier = 'supplier';

    public function label(): string
    {
        return match ($this) {
            self::Complaint => 'Aduan',
            self::Suggestion => 'Saran',
            self::Supplier => 'Pengajuan Supplier',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Complaint => 'Laporkan kendala transaksi, akun, atau layanan.',
            self::Suggestion => 'Kirim ide & masukan untuk Atlantic Pedia.',
            self::Supplier => 'Ajukan kerja sama sebagai penyedia produk/stok.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Complaint => 'report',
            self::Suggestion => 'lightbulb',
            self::Supplier => 'handshake',
        };
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
