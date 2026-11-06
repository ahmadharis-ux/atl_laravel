<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'legacy_user_id', 'username', 'email', 'password_hash', 'balance', 'points',
    'legacy_level', 'legacy_status', 'imported_at', 'migrated_at', 'migrated_user_id',
])]
#[Hidden(['password_hash'])]
class LegacyUser extends Model
{
    public function migratedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'migrated_user_id');
    }

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'points' => 'integer',
            'imported_at' => 'datetime',
            'migrated_at' => 'datetime',
        ];
    }
}
