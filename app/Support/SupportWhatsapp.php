<?php

namespace App\Support;

use App\Models\Deposit;
use App\Models\LandingSetting;
use App\Models\Order;
use Throwable;

/**
 * Tautan bantuan WhatsApp dengan pesan yang sudah terisi otomatis, sehingga
 * pengguna tidak perlu mengetik detail transaksi saat melapor.
 */
final class SupportWhatsapp
{
    public static function number(): ?string
    {
        $raw = trim((string) config('support.whatsapp_number'));

        if ($raw === '') {
            try {
                $raw = trim((string) LandingSetting::current()->whatsapp_number);
            } catch (Throwable) {
                $raw = '';
            }
        }

        $digits = (string) preg_replace('/\D+/', '', $raw);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        return $digits;
    }

    public static function url(string $message): ?string
    {
        $number = self::number();

        return $number === null
            ? null
            : 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    }

    public static function generalUrl(): ?string
    {
        return self::url('Halo Admin Atlantic Pedia, saya ingin bertanya mengenai layanan.');
    }

    public static function orderIssueUrl(Order $order): ?string
    {
        $order->loadMissing(['product', 'user']);

        $lines = [
            'Halo Admin Atlantic Pedia, saya ingin melaporkan kendala transaksi.',
            '',
            'No. Pesanan: '.$order->order_number,
            'Produk: '.($order->product?->name ?? '-'),
            'Kode Produk: '.($order->product?->code ?? '-'),
            'Tujuan: '.$order->target.(filled($order->target_zone) ? ' (Zone: '.$order->target_zone.')' : ''),
            'Nominal: Rp '.number_format($order->totalDue(), 0, ',', '.'),
            'Status: '.$order->status->label(),
            'Metode: '.($order->payment_method?->label() ?? '-'),
            'Waktu: '.$order->created_at?->locale('id')->translatedFormat('d M Y, H:i'),
            'Akun: '.($order->user?->name ?? '-').' ('.($order->user?->email ?? '-').')',
            '',
            'Detail: '.route('orders.show', $order),
            '',
            'Mohon dibantu pengecekan. Terima kasih.',
        ];

        return self::url(implode("\n", $lines));
    }

    public static function depositIssueUrl(Deposit $deposit): ?string
    {
        $deposit->loadMissing('user');

        $lines = [
            'Halo Admin Atlantic Pedia, saya ingin melaporkan kendala top up saldo.',
            '',
            'No. Referensi: '.$deposit->reference,
            'Nominal: Rp '.number_format($deposit->totalDue(), 0, ',', '.'),
            'Status: '.$deposit->status->label(),
            'Metode: '.($deposit->payment_method?->label() ?? '-'),
            'Waktu: '.$deposit->created_at?->locale('id')->translatedFormat('d M Y, H:i'),
            'Akun: '.($deposit->user?->name ?? '-').' ('.($deposit->user?->email ?? '-').')',
            '',
            'Detail: '.route('deposits.show', $deposit),
            '',
            'Mohon dibantu pengecekan. Terima kasih.',
        ];

        return self::url(implode("\n", $lines));
    }
}
