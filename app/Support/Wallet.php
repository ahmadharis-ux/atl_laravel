<?php

namespace App\Support;

/**
 * Sumber nilai dompet untuk sisi user (saldo, poin, biaya admin).
 *
 * Selama belum ada tabel saldo/deposit, nilainya masih statis — begitu
 * modul deposit masuk, pindahkan ke query model tanpa mengubah pemakaian
 * di controller.
 */
final class Wallet
{
    /**
     * Saldo tersedia, dalam rupiah.
     */
    public const BALANCE = 1250000;

    /**
     * Poin reward yang belum ditukar.
     */
    public const POINTS = 480;

    /**
     * Biaya admin per transaksi (0 = gratis).
     */
    public const ADMIN_FEE = 0;
}
