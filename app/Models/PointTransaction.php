<?php

namespace App\Models;

use App\Enums\PointTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Ledger perubahan poin reward: setiap perubahan points pada User dicatat
 * di sini (bonus order/topup, tukar merchandise, pengembalian poin).
 */
class PointTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'points_change',
        'points_after',
        'reference_type',
        'reference_id',
        'description',
    ];

    protected $casts = [
        'type' => PointTransactionType::class,
        'points_change' => 'integer',
        'points_after' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
