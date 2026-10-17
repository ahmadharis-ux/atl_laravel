<?php

namespace App\Models;

use Database\Factories\LoginBannerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Throwable;

#[Fillable(['title', 'body', 'image', 'link', 'cta_label', 'is_active', 'sort_order', 'starts_at', 'ends_at'])]
class LoginBanner extends Model
{
    /** @use HasFactory<LoginBannerFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * Banner yang boleh tampil: aktif dan berada dalam rentang jadwal.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    /**
     * URL publik gambar banner, atau null bila belum ada / berkas hilang.
     */
    public function imageUrl(): ?string
    {
        if ($this->image === null || $this->image === '') {
            return null;
        }

        try {
            $disk = Storage::disk((string) config('banners.disk', 'banners'));

            return $disk->exists($this->image) ? $disk->url($this->image) : null;
        } catch (Throwable) {
            return null;
        }
    }
}
