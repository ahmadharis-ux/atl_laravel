<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DigiflazzSyncLog extends Model
{
    protected $fillable = [
        'job_id', 'type', 'message', 'level', 'context',
        'started_at', 'finished_at', 'status', 'include_pascabayar', 'total_logs',
    ];

    protected $casts = [
        'context' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'include_pascabayar' => 'boolean',
    ];

    public function scopeForJob($query, string $jobId)
    {
        return $query->where('job_id', $jobId)->orderBy('created_at');
    }

    public function scopeJobRecords($query)
    {
        return $query->whereNull('message');
    }

    public function scopeLogEntries($query)
    {
        return $query->whereNotNull('message');
    }

    public static function createJob(string $jobId, bool $includePascabayar = false): self
    {
        return self::create([
            'job_id' => $jobId,
            'type' => 'sync',
            'status' => 'running',
            'include_pascabayar' => $includePascabayar,
            'started_at' => now(),
            'total_logs' => 0,
        ]);
    }

    public function markCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'finished_at' => now(),
            'total_logs' => self::forJob($this->job_id)->logEntries()->count(),
        ]);
    }

    public function markFailed(): void
    {
        $this->update([
            'status' => 'failed',
            'finished_at' => now(),
            'total_logs' => self::forJob($this->job_id)->logEntries()->count(),
        ]);
    }

    public function addLog(string $level, string $message, array $context = []): self
    {
        $this->increment('total_logs');

        return self::create([
            'job_id' => $this->job_id,
            'type' => $this->type,
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'include_pascabayar' => $this->include_pascabayar,
        ]);
    }

    public function logs()
    {
        return $this->hasMany(self::class, 'job_id', 'job_id')
            ->whereNotNull('message')
            ->orderBy('created_at');
    }
}
