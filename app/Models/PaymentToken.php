<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Token e-wallet tersimpan (Midtrans Direct Debit / tokenization).
 *
 * Masih berupa scaffolding: tabel & model disiapkan agar integrasi Direct
 * Debit bisa diaktifkan tanpa migrasi baru.
 */
#[Fillable(['user_id', 'provider', 'token_id', 'masked_account', 'status', 'metadata', 'expires_at'])]
class PaymentToken extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_REVOKED = 'revoked';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
