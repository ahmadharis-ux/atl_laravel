<?php

namespace App\Http\Controllers\Admin\Bot;

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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DigiflazzController extends Controller
{
    public function index(Request $request): View
    {
        $configs = BotDigiflazzConfig::latest()->paginate(10)->withQueryString();

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

        // 2. Gabungkan pilihan kategori/brand/type ke dalam scraper_config
        $input['scraper_config'] = $this->mergeSelections(
            is_array($input['scraper_config'] ?? null) ? $input['scraper_config'] : [],
            $request
        );

        // 3. Gunakan Validator manual agar memvalidasi variabel $input yang sudah di-decode
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
        $scraperConfig = $digiflazz->scraper_config ?? [];

        return view('admin.bot.digiflazz.edit', [
            'config' => $digiflazz,
            'selectedOptions' => [
                'categories' => $this->selectedOptionLabels($digiflazz, 'categories', $scraperConfig['categories'] ?? []),
                'brands' => $this->selectedOptionLabels($digiflazz, 'brands', $scraperConfig['brands'] ?? []),
                'types' => $this->selectedOptionLabels($digiflazz, 'types', $scraperConfig['types'] ?? []),
            ],
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

        // Gabungkan pilihan kategori/brand/type ke dalam scraper_config
        $input['scraper_config'] = $this->mergeSelections(
            is_array($input['scraper_config'] ?? null) ? $input['scraper_config'] : [],
            $request
        );

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

    // ── Kategori / Brand / Type (picker) ─────────────────────────

    /** @var array<int, string> */
    private const OPTION_TYPES = ['categories', 'brands', 'types'];

    /**
     * Endpoint pencarian kategori/brand/type Digiflazz untuk multi-select.
     * Memakai konfigurasi aktif, atau `config_id` bila diberikan.
     */
    public function options(Request $request, string $type): JsonResponse
    {
        if (! in_array($type, self::OPTION_TYPES, true)) {
            abort(404);
        }

        $config = $request->filled('config_id')
            ? BotDigiflazzConfig::find($request->integer('config_id'))
            : BotDigiflazzConfig::where('is_active', true)->first();

        if (! $config) {
            return response()->json([
                'results' => [],
                'message' => 'Belum ada konfigurasi Digiflazz aktif.',
            ]);
        }

        $term = Str::lower(trim((string) $request->query('q', '')));
        $limit = min(max($request->integer('limit', 20), 1), 50);

        $results = collect($this->optionMap($config, $type))
            ->when($term !== '', fn ($items) => $items->filter(
                fn ($name) => str_contains(Str::lower((string) $name), $term)
            ))
            ->take($limit)
            ->map(fn ($name, $id): array => ['value' => (string) $id, 'text' => (string) $name])
            ->values();

        return response()->json(['results' => $results]);
    }

    /**
     * Gabungkan pilihan kategori/brand/type dari request ke dalam scraper_config.
     * Array kosong dihilangkan agar job memperlakukannya sebagai "semua".
     *
     * @param  array<string, mixed>  $scraperConfig
     * @return array<string, mixed>
     */
    private function mergeSelections(array $scraperConfig, Request $request): array
    {
        foreach (self::OPTION_TYPES as $type) {
            $values = array_values(array_filter(array_map(
                fn ($value) => is_scalar($value) ? trim((string) $value) : null,
                (array) $request->input($type, [])
            ), fn ($value) => $value !== null && $value !== ''));

            if (! empty($values)) {
                $scraperConfig[$type] = $values;
            } else {
                unset($scraperConfig[$type]);
            }
        }

        return $scraperConfig;
    }

    /**
     * Label pilihan tersimpan (id => nama) untuk ditampilkan sebelum picker dimuat.
     * Membaca dari cache saja — tidak memanggil API.
     *
     * @param  array<int, string|int>  $ids
     * @return array<string, string>
     */
    private function selectedOptionLabels(BotDigiflazzConfig $config, string $type, array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $map = Cache::get("bot_digiflazz.options.{$config->id}.{$type}", []);
        $labels = [];

        foreach ($ids as $id) {
            $key = (string) $id;
            $labels[$key] = (string) ($map[$id] ?? $id);
        }

        return $labels;
    }

    /** @return array<array-key, string> id => nama (di-cache 6 jam) */
    private function optionMap(BotDigiflazzConfig $config, string $type): array
    {
        return Cache::remember(
            "bot_digiflazz.options.{$config->id}.{$type}",
            now()->addHours(6),
            function () use ($config, $type): array {
                try {
                    $map = match ($type) {
                        'categories' => $config->getClient()->listCategories(),
                        'brands' => $config->getClient()->listBrands(),
                        'types' => $config->getClient()->listTypes(),
                        default => [],
                    };
                } catch (\Throwable $e) {
                    Log::warning('Gagal memuat opsi Digiflazz', [
                        'config_id' => $config->id,
                        'type' => $type,
                        'error' => $e->getMessage(),
                    ]);

                    return [];
                }

                asort($map);

                return $map;
            }
        );
    }

    // ── Sync Logs ────────────────────────────────────────────────

    public function syncIndex(Request $request): View
    {
        $logs = BotDigiflazzSyncLog::query()
            ->where('type', 'scrape_and_update')
            ->jobRecords()
            ->latest('started_at')
            ->paginate(20)
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
