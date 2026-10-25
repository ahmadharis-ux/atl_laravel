<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['deposit_id', 'disk', 'file_path', 'submitted_at', 'reviewed_by', 'review_action', 'review_reason', 'reviewed_at'])]
#[Hidden(['file_path'])]
class DepositProof extends Model
{
    public const ACTION_APPROVED = 'approved';

    public const ACTION_NEEDS_REVISION = 'needs_revision';

    public const ACTION_REJECTED = 'rejected';

    public function deposit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }
}
