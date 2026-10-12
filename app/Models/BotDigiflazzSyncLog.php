<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BotDigiflazzSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'config_id',
        'job_id',
        'type',
        'status',
        'message',
        'level',
        'context',
        'config_snapshot',
        'started_at',
        'finished_at',
        'total_logs',
    ];

    protected $casts = [
        'context' => 'array',
        'config_snapshot' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function config()
    {
        return $this->belongsTo(BotDigiflazzConfig::class, 'config_id');
    }

    public function logs()
    {
        return $this->hasMany(self::class, 'job_id', 'job_id')
            ->whereNotNull('message')
            ->orderBy('created_at');
    }

    public function scopeJobRecords($query)
    {
        return $query->whereNull('message');
    }

    public function scopeLogEntries($query)
    {
        return $query->whereNotNull('message');
    }

    public static function createJob(string $jobId, int $configId, array $configSnapshot = []): self
    {
        return self::create([
            'job_id' => $jobId,
            'config_id' => $configId,
            'type' => 'scrape_and_update',
            'status' => 'running',
            'started_at' => now(),
            'total_logs' => 0,
            'config_snapshot' => $configSnapshot,
        ]);
    }

    public function markCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'finished_at' => now(),
            'total_logs' => self::logEntries()->where('job_id', $this->job_id)->count(),
        ]);
    }

    public function markFailed(string $message = ''): void
    {
        $this->update([
            'status' => 'failed',
            'finished_at' => now(),
            'message' => $message,
            'level' => 'error',
            'total_logs' => self::logEntries()->where('job_id', $this->job_id)->count(),
        ]);
    }

    public function addLog(string $level, string $message, array $context = []): self
    {
        $this->increment('total_logs');

        return self::create([
            'job_id' => $this->job_id,
            'config_id' => $this->config_id,
            'type' => $this->type,
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ]);
    }
}
