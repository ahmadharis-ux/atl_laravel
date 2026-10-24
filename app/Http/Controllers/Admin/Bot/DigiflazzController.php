<?php

namespace App\Http\Controllers\Admin\Bot;

use App\Http\Controllers\Admin\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Jobs\BotDigiflazzScrapeJob;
use App\Models\BotDigiflazzConfig;
use App\Models\BotDigiflazzProduct;
use App\Models\BotDigiflazzSeller;
use App\Models\BotDigiflazzSyncLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DigiflazzController extends Controller
{
    use HasPerPage;

    public function index(Request $request): View
    {
        $configs = BotDigiflazzConfig::latest()->paginate($this->perPage($request))->withQueryString();

        return view('admin.bot.digiflazz.index', [
            'configs' => $configs,
        ]);
    }

    public function create(): View
    {
        return view('admin.bot.digiflazz.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $input = $request->all();

        // 1. Decode scraper_config JSON string to array pada $input
        if (isset($input['scraper_config']) && is_string($input['scraper_config'])) {
            $decoded = json_decode($input['scraper_config'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $input['scraper_config'] = $decoded;
            } else {
                Log::warning('Store Digiflazz Config - Gagal decode JSON scraper_config', [
                    'raw_scraper_config' => $input['scraper_config'],
                    'json_error' => json_last_error_msg(),
                ]);
                $input['scraper_config'] = null;
            }
        }

        // 2. Gunakan Validator manual agar memvalidasi variabel $input yang sudah di-decode
        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'max:100'],
            'cookies_content' => ['required', 'string'],
            'csrf_token' => ['required', 'string', 'max:255'],
            'socket_id' => ['nullable', 'string', 'max:255'],
            'xsrf_token' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable'], // diubah jadi nullable/boolean agar aman dari string "1"
            'scraper_config' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            Log::error('Store Digiflazz Config - Gagal Validasi:', $validator->errors()->toArray());

            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        // Konversi is_active ke boolean murni (true/false) untuk database
        $validated['is_active'] = filter_var($validated['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // --- TAMBAHAN LOG DEBUG: Data setelah lolos validasi ---
        Log::info('Store Digiflazz Config - Validated Data:', [
            'validated' => $validated,
        ]);

        // Jika is_active true, nonaktifkan yang lain
        if ($validated['is_active']) {
            BotDigiflazzConfig::where('is_active', true)->update(['is_active' => false]);
        }

        $config = BotDigiflazzConfig::create($validated);

        Log::info('Store Digiflazz Config - Berhasil dibuat:', [
            'config_id' => $config->id ?? null,
        ]);

        return redirect()
            ->route('admin.bot.digiflazz.index')
            ->with('status', 'Konfigurasi Digiflazz berhasil dibuat.');
    }

    public function edit(BotDigiflazzConfig $digiflazz): View
    {
        return view('admin.bot.digiflazz.edit', [
            'config' => $digiflazz,
        ]);
    }

    public function update(Request $request, BotDigiflazzConfig $digiflazz): RedirectResponse
    {
        $input = $request->all();

        // Decode scraper_config JSON string to array
        if (isset($input['scraper_config']) && is_string($input['scraper_config'])) {
            $decoded = json_decode($input['scraper_config'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $input['scraper_config'] = $decoded;
            } else {
                $input['scraper_config'] = null;
            }
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'cookies_content' => ['required', 'string'],
            'csrf_token' => ['required', 'string', 'max:255'],
            'socket_id' => ['nullable', 'string', 'max:255'],
            'xsrf_token' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'scraper_config' => ['nullable', 'array'],
        ]);

        if ($validated['is_active'] ?? false) {
            BotDigiflazzConfig::where('id', '!=', $digiflazz->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $digiflazz->update($validated);

        return redirect()
            ->route('admin.bot.digiflazz.index')
            ->with('status', 'Konfigurasi Digiflazz berhasil diperbarui.');
    }

    public function destroy(Request $request, BotDigiflazzConfig $digiflazz): RedirectResponse|JsonResponse
    {
        // Hapus data terkait terlebih dahulu (cascade delete manual)
        BotDigiflazzSyncLog::where('config_id', $digiflazz->id)->delete();
        BotDigiflazzSeller::where('config_id', $digiflazz->id)->delete();
        BotDigiflazzProduct::where('config_id', $digiflazz->id)->delete();

        $digiflazz->delete();

        // Jika request dari AJAX (mendukung atribut data-ajax frontend)
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Konfigurasi Digiflazz beserta data terkait dihapus.',
            ]);
        }

        return redirect()
            ->route('admin.bot.digiflazz.index')
            ->with('status', 'Konfigurasi Digiflazz beserta data terkait dihapus.');
    }

    // ── Sync Logs ────────────────────────────────────────────────

    public function syncIndex(Request $request): View
    {
        $logs = BotDigiflazzSyncLog::query()
            ->where('type', 'scrape_and_update')
            ->jobRecords()
            ->latest('started_at')
            ->paginate($this->perPage($request))
            ->withQueryString();

        $activeConfig = BotDigiflazzConfig::where('is_active', true)->first();

        return view('admin.bot.digiflazz.sync.index', [
            'logs' => $logs,
            'activeConfig' => $activeConfig,
        ]);
    }

    public function syncShow(BotDigiflazzSyncLog $syncLog): View|JsonResponse
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

        if (request()->ajax()) {
            return response()->json([
                'job' => [
                    'job_id' => $syncLog->job_id,
                    'type' => $syncLog->type,
                    'status' => $syncLog->status,
                    'started_at' => $syncLog->started_at?->format('Y-m-d H:i:s'),
                    'finished_at' => $syncLog->finished_at?->format('Y-m-d H:i:s'),
                    'total_logs' => $syncLog->total_logs,
                ],
                'logs' => $logs,
            ]);
        }

        return view('admin.bot.digiflazz.sync.show', [
            'job' => $syncLog,
            'logs' => $logs,
        ]);
    }

    public function startSync(Request $request): JsonResponse
    {
        $request->validate([
            'config_id' => ['required', 'exists:bot_digiflazz_configs,id'],
        ]);

        $config = BotDigiflazzConfig::findOrFail($request->integer('config_id'));

        if (! $config->is_active) {
            return response()->json([
                'message' => 'Konfigurasi tidak aktif. Aktifkan dulu.',
            ], 400);
        }

        $jobId = (string) Str::uuid();
        BotDigiflazzScrapeJob::dispatch($config->id)->onQueue('default');

        return response()->json([
            'message' => 'Sinkronisasi Digiflazz dimulai di latar belakang.',
            'job_id' => $jobId,
        ]);
    }
}
