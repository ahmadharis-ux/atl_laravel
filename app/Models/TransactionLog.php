<?php

namespace App\Models;

use App\Enums\TransactionLogLevel;
use Database\Factories\TransactionLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'user_id', 'level', 'event', 'message', 'context', 'stack_trace'])]
class TransactionLog extends Model
{
    /** @use HasFactory<TransactionLogFactory> */
    use HasFactory;

    /**
     * Log bersifat append-only: tidak ada kolom updated_at.
     */
    public const UPDATED_AT = null;

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => TransactionLogLevel::class,
            'context' => 'array',
        ];
    }
}
