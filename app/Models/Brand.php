<?php

namespace App\Models;

use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Throwable;

#[Fillable(['external_id', 'name', 'slug', 'logo'])]
class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory;

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * URL publik logo brand, atau null bila belum ada / berkas hilang.
     */
    public function logoUrl(): ?string
    {
        if ($this->logo === null || $this->logo === '') {
            return null;
        }

        try {
            $disk = Storage::disk((string) config('brand-logos.disk', 'brands'));

            return $disk->exists($this->logo) ? $disk->url($this->logo) : null;
        } catch (Throwable) {
            return null;
        }
    }
}
