<?php

namespace App\Services;

use App\Models\PaymentToken;
use App\Models\User;
use RuntimeException;

/**
 * Scaffolding E-Wallet Tokenization / Direct Debit (Midtrans).
 *
 * Kerangka ini menyiapkan alur linking akun & charge tanpa token. Aktifkan
 * lebih dulu lewat config `midtrans.tokenization_enabled` setelah akun
 * Midtrans disetujui untuk Direct Debit.
 */
class EwalletTokenizationService
{
    public function __construct(private MidtransService $midtrans) {}

    public function isEnabled(): bool
    {
        return (bool) config('midtrans.tokenization_enabled') && $this->midtrans->isConfigured();
    }

    /**
     * Minta URL/token untuk menautkan akun e-wallet pengguna.
     *
     * @return array{redirect_url: string}|never
     */
    public function startLinking(User $user, string $provider): array
    {
        $this->guardEnabled();

        throw new RuntimeException('Alur linking e-wallet belum diimplementasikan.');
    }

    /**
     * Simpan token e-wallet setelah pengguna menyelesaikan linking.
     */
    public function store(User $user, string $provider, string $tokenId, ?string $maskedAccount = null): PaymentToken
    {
        $this->guardEnabled();

        throw new RuntimeException('Penyimpanan token e-wallet belum diimplementasikan.');
    }

    /**
     * Lakukan charge memakai token tersimpan (Direct Debit).
     */
    public function charge(PaymentToken $token, int $amount, string $orderId): never
    {
        $this->guardEnabled();

        throw new RuntimeException('Direct Debit charge belum diimplementasikan.');
    }

    private function guardEnabled(): void
    {
        if (! $this->isEnabled()) {
            throw new RuntimeException('E-wallet tokenization belum diaktifkan.');
        }
    }
}
