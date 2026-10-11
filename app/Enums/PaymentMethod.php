<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Saldo = 'saldo';
    case Qris = 'qris';
    case VirtualAccount = 'va';
    case EWallet = 'ewallet';

    public function label(): string
    {
        return match ($this) {
            self::Saldo => 'Saldo',
            self::Qris => 'QRIS',
            self::VirtualAccount => 'Transfer Bank / VA',
            self::EWallet => 'E-Wallet',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Saldo => 'account_balance_wallet',
            self::Qris => 'qr_code_2',
            self::VirtualAccount => 'account_balance',
            self::EWallet => 'wallet',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Saldo => 'Bayar langsung dari saldo akunmu.',
            self::Qris => 'Scan QR dengan aplikasi pembayaran apa pun.',
            self::VirtualAccount => 'Transfer ke nomor Virtual Account.',
            self::EWallet => 'Bayar lewat dompet digital.',
        };
    }

    /**
     * Pembayaran yang langsung lunas tanpa langkah konfirmasi.
     */
    public function isInstant(): bool
    {
        return $this === self::Saldo;
    }

    /**
     * Membutuhkan pilihan kanal (bank / e-wallet).
     */
    public function requiresChannel(): bool
    {
        return in_array($this, [self::VirtualAccount, self::EWallet], true);
    }

    /**
     * @return array<string, string>
     */
    public function channels(): array
    {
        return match ($this) {
            self::VirtualAccount => [
                'bca' => 'BCA Virtual Account',
                'bni' => 'BNI Virtual Account',
                'bri' => 'BRI Virtual Account',
                'mandiri' => 'Mandiri Virtual Account',
                'permata' => 'Permata Virtual Account',
            ],
            self::EWallet => [
                'dana' => 'DANA',
                'ovo' => 'OVO',
                'gopay' => 'GoPay',
                'shopeepay' => 'ShopeePay',
            ],
            default => [],
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

    /**
     * Semua kanal dari seluruh metode (key unik), untuk pilihan & validasi.
     *
     * @return array<string, string>
     */
    public static function channelOptions(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            foreach ($case->channels() as $value => $label) {
                $options[$value] = $label;
            }
        }

        return $options;
    }

    public function channelLabel(?string $channel): ?string
    {
        if ($channel === null) {
            return null;
        }

        return $this->channels()[$channel] ?? ucfirst($channel);
    }
}
