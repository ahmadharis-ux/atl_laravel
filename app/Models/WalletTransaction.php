<?php

namespace App\Models;

use App\Enums\WalletTransactionDirection;
use App\Enums\WalletTransactionType;
use Database\Factories\WalletTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['user_id', 'type', 'direction', 'amount', 'balance_before', 'balance_after', 'reference_type', 'reference_id', 'description', 'metadata'])]
class WalletTransaction extends Model
{
    /** @use HasFactory<WalletTransactionFactory> */
    use HasFactory;

    /**
     * Ledger bersifat append-only: tidak ada kolom updated_at.
     */
    public const UPDATED_AT = null;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => WalletTransactionType::class,
            'direction' => WalletTransactionDirection::class,
            'amount' => 'integer',
            'balance_before' => 'integer',
            'balance_after' => 'integer',
            'metadata' => 'array',
        ];
    }
}
