<?php

namespace App\Models;

use App\Enums\DepositStatus;
use App\Enums\PaymentMethod;
use Database\Factories\DepositFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

#[Fillable(['user_id', 'reference', 'amount', 'payment_method', 'payment_channel', 'payment_code', 'master_bank_id', 'status', 'expires_at', 'paid_at', 'metadata'])]
class Deposit extends Model
{
    /** @use HasFactory<DepositFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function masterBank(): BelongsTo
    {
        return $this->belongsTo(MasterBank::class);
    }

    /**
     * @return MorphOne<WalletTransaction, $this>
     */
    public function walletTransaction(): MorphOne
    {
        return $this->morphOne(WalletTransaction::class, 'reference')->latestOfMany();
    }

    public function isPending(): bool
    {
        return $this->status === DepositStatus::Pending;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'payment_method' => PaymentMethod::class,
            'status' => DepositStatus::class,
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
