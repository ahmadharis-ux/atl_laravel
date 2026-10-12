<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Membaca saldo (deposit) akun buyer Digiflazz untuk ditampilkan di dashboard
 * admin. Hasilnya di-cache sebentar supaya dashboard tidak memanggil API
 * setiap kali dimuat, sekaligus tidak memblokir halaman bila API sedang
 * bermasalah (kegagalan dianggap saldo tidak diketahui / null).
 */
final class DigiflazzBalance
{
    private const CACHE_KEY = 'digiflazz:balance';

    private const CACHE_TTL_MINUTES = 5;

    /**
     * Saldo deposit Digiflazz terkini (dalam rupiah), atau null bila tidak
     * bisa dibaca.
     */
    public static function current(bool $fresh = false): ?int
    {
        if ($fresh) {
            Cache::forget(self::CACHE_KEY);
        }

        /** @var int|null $balance */
        $balance = Cache::remember(
            self::CACHE_KEY,
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            function (): ?int {
                try {
                    $data = Digiflazz::make()->checkBalance();
                } catch (Throwable) {
                    return null;
                }

                $deposit = $data['deposit'] ?? null;

                return is_numeric($deposit) ? (int) $deposit : null;
            },
        );

        return $balance;
    }

    /**
     * Ambang batas saldo rendah (rupiah) yang memicu peringatan di dashboard.
     */
    public static function threshold(): int
    {
        return (int) config('services.digiflazz.low_balance_threshold', 100000);
    }

    /**
     * Apakah saldo berada di bawah ambang batas. Saldo yang tidak diketahui
     * tidak dianggap rendah.
     */
    public static function isLow(?int $balance = null): bool
    {
        $balance ??= self::current();

        return $balance !== null && $balance < self::threshold();
    }

    /**
     * Data siap tampil untuk dashboard admin.
     *
     * @return array{balance: int|null, threshold: int, low: bool}
     */
    public static function summary(): array
    {
        $balance = self::current();

        return [
            'balance' => $balance,
            'threshold' => self::threshold(),
            'low' => self::isLow($balance),
        ];
    }
}
