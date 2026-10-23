<?php

namespace App\Models;

use App\Enums\TicketCategory;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'category',
        'subject',
        'description',
        'company_name',
        'company_phone',
        'status',
        'closed_at',
    ];

    protected $casts = [
        'category' => TicketCategory::class,
        'status' => TicketStatus::class,
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class)->oldest('id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [TicketStatus::Open, TicketStatus::InProgress]);
    }
}
