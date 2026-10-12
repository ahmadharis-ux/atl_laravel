@extends('layouts.admin')

@section('title', 'Sinkronisasi Digiflazz - Admin Atlantic Pedia')
@section('page-title', 'Sinkronisasi Digiflazz - Riwayat')

@section('content')
<div class="d-flex flex-column gap-4" data-ajax
     data-ajax-url="{{ route('admin.bot.digiflazz.sync.index') }}"
     data-persist-key="admin.bot.digiflazz.sync">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h1 class="fw-bold fs-4 m-0">Sinkronisasi Digiflazz</h1>
            <p class="tx-mute fs-12 m-0">Riwayat sinkronisasi produk dari Digiflazz ke database bot.</p>
        </div>
        @can('products.update')
            <button type="button" class="btn-navy" data-sync-start
                    data-url="{{ route('admin.bot.digiflazz.sync.start') }}"
                    data-config-id="{{ $activeConfig->id ?? 1 }}">
                <span class="ms ms-sm align-middle me-1">sync</span>Mulai Sinkronisasi
            </button>
        @endcan
    </div>

    <x-ui.card>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 18rem;">Job ID</th>
                        <th>Tipe</th>
                        <th style="width: 8rem;">Status</th>
                        <th>Mulai</th>
                        <th>Selesai</th>
                        <th style="width: 6rem;">Log</th>
                        <th style="width: 8rem;" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody data-ajax-rows>
                    @forelse ($logs as $log)
                        <tr data-job-id="{{ $log->job_id }}" data-status="{{ $log->status }}">
                            <td class="font-monospace fs-11">{{ $log->job_id }}</td>
                            <td>{{ ucfirst($log->type) }}</td>
                            <td>
                                @if ($log->status === 'running')
                                    <span class="pill bg-gold tx-gold">Berjalan</span>
                                @elseif ($log->status === 'completed')
                                    <span class="pill bg-mint-l tx-mintt">Selesai</span>
                                @else
                                    <span class="pill bg-errc-l tx-err">Gagal</span>
                                @endif
                            </td>
                            <td>{{ $log->started_at?->format('d/m/Y H:i:s') ?? '-' }}</td>
                            <td>{{ $log->finished_at?->format('d/m/Y H:i:s') ?? '-' }}</td>
                            <td class="fw-bold text-end">{{ $log->total_logs }}</td>
                            <td class="text-end">
                                <x-ui.actions :actions="[
                                    ['label' => 'Lihat Log', 'icon' => 'terminal', 'action' => 'view', 'href' => route('admin.bot.digiflazz.sync.show', $log)],
                                ]" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center tx-mute py-4">Belum ada riwayat sinkronisasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-slot:footer>
            <div class="d-flex justify-content-between align-items-center">
                <span class="fs-12 tx-mute">Menampilkan {{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }} dari {{ $logs->total() }} data</span>
                {{ $logs->links() }}
            </div>
        </x-slot:footer>
    </x-ui.card>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    function initSyncDigiflazzPage() {
        var startBtn = document.querySelector('[data-sync-start]');
        if (!startBtn) return;

        startBtn.addEventListener('click', function () {
            var btn = this;
            var url = btn.getAttribute('data-url');
            var configId = btn.getAttribute('data-config-id');

            btn.disabled = true;
            var originalHtml = btn.innerHTML;
            btn.innerHTML = '<span class="ms ms-sm align-middle me-1 spin">sync</span>Memulai...';

            fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ config_id: configId }),
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    AP.showToast(data.message ?? 'Sinkronisasi dimulai.', 'success');
                    setTimeout(function () { window.location.reload(); }, 1500);
                })
                .catch(function () {
                    AP.showToast('Gagal memulai sinkronisasi.', 'error');
                })
                .finally(function () {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                });
        });
    }

    if (document.readyState !== 'loading') initSyncDigiflazzPage();
    else document.addEventListener('DOMContentLoaded', initSyncDigiflazzPage);
})();
</script>
@endpush