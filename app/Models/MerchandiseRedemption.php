<?php

namespace App\Models;

use App\Enums\MerchandiseRedemptionStatus;
use Database\Factories\MerchandiseRedemptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'merchandise_id', 'points_cost', 'status', 'notes'])]
class MerchandiseRedemption extends Model
{
    /** @use HasFactory<MerchandiseRedemptionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'points_cost' => 'integer',
            'status' => MerchandiseRedemptionStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function merchandise(): BelongsTo
    {
        return $this->belongsTo(Merchandise::class);
    }
}
