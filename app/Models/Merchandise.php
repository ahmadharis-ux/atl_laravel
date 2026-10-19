<?php

namespace App\Models;

use Database\Factories\MerchandiseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Throwable;

#[Fillable(['name', 'description', 'points_cost', 'stock', 'image', 'is_active'])]
class Merchandise extends Model
{
    /** @use HasFactory<MerchandiseFactory> */
    use HasFactory;

    /**
     * Nama tabel (uncountable — bukan "merchandises").
     */
    protected $table = 'merchandise';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'points_cost' => 'integer',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(MerchandiseRedemption::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * URL publik gambar merchandise, atau null bila belum ada / berkas hilang.
     */
    public function imageUrl(): ?string
    {
        if ($this->image === null || $this->image === '') {
            return null;
        }

        try {
            $disk = Storage::disk((string) config('merchandise.disk', 'merchandise'));

            return $disk->exists($this->image) ? $disk->url($this->image) : null;
        } catch (Throwable) {
            return null;
        }
    }
}
