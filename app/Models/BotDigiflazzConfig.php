<?php

namespace App\Models;

use App\Services\Digiflazz\DigiflazzClient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BotDigiflazzConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'cookies_content',
        'csrf_token',
        'socket_id',
        'xsrf_token',
        'is_active',
        'scraper_config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'scraper_config' => 'array',
    ];

    public function syncLogs()
    {
        return $this->hasMany(BotDigiflazzSyncLog::class, 'config_id');
    }

    public function products()
    {
        return $this->hasMany(BotDigiflazzProduct::class, 'config_id');
    }

    public function sellers()
    {
        return $this->hasMany(BotDigiflazzSeller::class, 'config_id');
    }

    public function getClient(): DigiflazzClient
    {
        return new DigiflazzClient(
            (string) $this->cookies_content,
            (string) $this->csrf_token,
            (string) ($this->socket_id ?? ''),
            $this->xsrf_token
        );
    }
}
