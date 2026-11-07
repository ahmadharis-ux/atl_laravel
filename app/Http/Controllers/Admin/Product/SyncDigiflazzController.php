<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Admin\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Jobs\SyncDigiflazzProductsJob;
use App\Models\DigiflazzSyncLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncDigiflazzController extends Controller
{
    use HasPerPage;

    public function index(Request $request): View
    {
        $jobs = DigiflazzSyncLog::query()
            ->where('type', 'sync')
            ->latest('started_at')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('admin.product.sync.index', [
            'jobs' => $jobs,
        ]);
    }

    public function show(Request $request, DigiflazzSyncLog $syncLog): View|JsonResponse
    {
        $logs = $syncLog->logs->map(function ($log) {
            return [
                'id' => $log->id,
                'level' => $log->level,
                'message' => $log->message,
                'context' => $log->context,
                'time' => $log->created_at->format('H:i:s'),
            ];
        });

        if ($request->ajax()) {
            return response()->json([
                'job' => [
                    'job_id' => $syncLog->job_id,
                    'type' => $syncLog->type,
                    'status' => $syncLog->status,
                    'include_pascabayar' => $syncLog->include_pascabayar,
                    'started_at' => $syncLog->started_at?->format('Y-m-d H:i:s'),
                    'finished_at' => $syncLog->finished_at?->format('Y-m-d H:i:s'),
                    'total_logs' => $syncLog->total_logs,
                ],
                'logs' => $logs,
            ]);
        }

        return view('admin.product.sync.show', [
            'job' => $syncLog,
            'logs' => $logs,
        ]);
    }

    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'include_pascabayar' => ['boolean'],
        ]);

        SyncDigiflazzProductsJob::dispatch(
            $request->boolean('include_pascabayar')
        );

        return response()->json([
            'message' => 'Sinkronisasi produk Digiflazz dimulai di latar belakang.',
        ]);
    }
}
