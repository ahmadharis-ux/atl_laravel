<?php

namespace App\Http\Controllers\Admin\Queue;

use App\Http\Controllers\Admin\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class QueueMonitorController extends Controller
{
    use HasPerPage;

    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'pending');

        $stats = [
            'pending' => DB::table('jobs')->count(),
            'failed' => DB::table('failed_jobs')->count(),
            'batches' => DB::table('job_batches')->exists() ? DB::table('job_batches')->count() : 0,
        ];

        $pending = [];
        if ($tab === 'pending') {
            $pending = DB::table('jobs')
                ->orderBy('id')
                ->paginate($this->perPage($request, 50))
                ->withQueryString()
                ->through(function ($job) {
                    $payload = [];
                    try {
                        $payload = json_decode($job->payload, true) ?: [];
                    } catch (Throwable $e) {
                        $payload = [];
                    }

                    $displayName = $payload['displayName'] ?? ($payload['data']['commandName'] ?? class_basename($payload['data']['command'] ?? ($payload['job'] ?? 'Unknown')));

                    return [
                        'id' => $job->id,
                        'queue' => $job->queue,
                        'attempts' => $job->attempts,
                        'created_at' => $job->created_at ? date('Y-m-d H:i:s', $job->created_at) : '-',
                        'available_at' => $job->available_at ? date('Y-m-d H:i:s', $job->available_at) : '-',
                        'reserved_at' => $job->reserved_at ? date('Y-m-d H:i:s', $job->reserved_at) : null,
                        'name' => $displayName,
                        'payload' => $payload,
                    ];
                });
        }

        $failed = [];
        if ($tab === 'failed') {
            $failed = DB::table('failed_jobs')
                ->orderByDesc('failed_at')
                ->paginate($this->perPage($request, 50))
                ->withQueryString()
                ->through(function ($job) {
                    $payload = [];
                    try {
                        $payload = json_decode($job->payload, true) ?: [];
                    } catch (Throwable $e) {
                        $payload = [];
                    }

                    $displayName = $payload['displayName'] ?? ($payload['data']['commandName'] ?? class_basename($payload['data']['command'] ?? ($payload['job'] ?? 'Unknown')));
                    $exception = $job->exception ? Str::limit($job->exception, 200) : null;

                    return [
                        'id' => $job->id,
                        'uuid' => $job->uuid,
                        'connection' => $job->connection,
                        'queue' => $job->queue,
                        'failed_at' => $job->failed_at,
                        'name' => $displayName,
                        'payload' => $payload,
                        'exception' => $exception,
                        'full_exception' => $job->exception,
                    ];
                });
        }

        return view('admin.queue.index', [
            'tab' => $tab,
            'stats' => $stats,
            'pending' => $pending,
            'failed' => $failed,
        ]);
    }

    public function showFailed(Request $request, string $uuid): View
    {
        $job = DB::table('failed_jobs')->where('uuid', $uuid)->firstOrFail();
        $payload = [];
        try {
            $payload = json_decode($job->payload, true) ?: [];
        } catch (Throwable $e) {
            $payload = [];
        }

        return view('admin.queue.show-failed', [
            'job' => $job,
            'payload' => $payload,
        ]);
    }

    public function retry(Request $request, string $uuid): RedirectResponse
    {
        try {
            Artisan::call('queue:retry', ['id' => [$uuid]]);
        } catch (Throwable $e) {
            // ignore
        }

        return back()->with('success', 'Job gagal dicoba ulang.');
    }

    public function forget(Request $request, string $uuid): RedirectResponse
    {
        try {
            Artisan::call('queue:forget', ['id' => [$uuid]]);
        } catch (Throwable $e) {
            // ignore
        }

        return back()->with('success', 'Job gagal dihapus.');
    }

    public function flush(): RedirectResponse
    {
        try {
            Artisan::call('queue:flush');
        } catch (Throwable $e) {
            // ignore
        }

        return back()->with('success', 'Semua failed job dihapus.');
    }

    public function retryAll(): RedirectResponse
    {
        try {
            Artisan::call('queue:retry', ['id' => ['all']]);
        } catch (Throwable $e) {
            // ignore
        }

        return back()->with('success', 'Semua failed job dicoba ulang.');
    }
}
