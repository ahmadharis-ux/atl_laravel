<?php

namespace App\Support;

use App\Enums\PaymentMethod;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Membuat instruksi pembayaran (kode bayar & kanal) untuk sebuah metode.
 *
 * Dipakai bersama oleh checkout pesanan maupun top up saldo agar format kode
 * bayar tetap konsisten. Pada mode development kode ini hanya simulasi dan
 * belum terhubung ke payment gateway sungguhan.
 */
final class PaymentInstruction
{
    /**
     * Kode bayar simulasi. `null` untuk metode saldo (tanpa kode).
     */
    public static function code(PaymentMethod $method): ?string
    {
        return match ($method) {
            PaymentMethod::VirtualAccount => '88'.str_pad((string) random_int(0, 9_999_999_999), 12, '0', STR_PAD_LEFT),
            PaymentMethod::Qris => 'QR'.now()->format('ymd').Str::upper(Str::random(6)),
            PaymentMethod::EWallet => 'EW'.Str::upper(Str::random(10)),
            PaymentMethod::Saldo => null,
        };
    }

    /**
     * Pastikan kanal sesuai metode; kosongkan bila metode tidak butuh kanal.
     */
    public static function channel(PaymentMethod $method, ?string $channel): ?string
    {
        if (! $method->requiresChannel()) {
            return null;
        }

        if ($channel === null || ! array_key_exists($channel, $method->channels())) {
            throw ValidationException::withMessages(['payment_channel' => 'Pilih kanal pembayaran.']);
        }

        return $channel;
    }
}
