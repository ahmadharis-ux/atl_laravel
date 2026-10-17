<?php

namespace App\Support;

/**
 * Sumber nilai dompet untuk sisi user.
 *
 * Saldo & poin reward kini berasal dari kolom `users` (lihat WalletLedger),
 * jadi kelas ini hanya menyisakan konfigurasi statis yang belum punya tabel.
 */
final class Wallet
{
    /**
     * Biaya admin per transaksi (0 = gratis).
     */
    public const ADMIN_FEE = 0;
}
