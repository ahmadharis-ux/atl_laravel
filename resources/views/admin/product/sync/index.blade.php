@extends('layouts.admin')

@section('title', 'Sinkronisasi Digiflazz - Admin Atlantic Pedia')
@section('page-title', 'Sinkronisasi Digiflazz')

@section('content')
<div class="d-flex flex-column gap-4" data-ajax
     data-ajax-url="{{ route('admin.products.sync-digiflazz.index') }}"
     data-persist-key="admin.products.sync-digiflazz">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h1 class="fw-bold fs-4 m-0">Sinkronisasi Digiflazz</h1>
            <p class="tx-mute fs-12 m-0">Riwayat sinkronisasi produk dari Digiflazz ke katalog lokal.</p>
        </div>
        @can('products.update')
            <button type="button" class="btn-navy" data-sync-start
                    data-url="{{ route('admin.products.sync-digiflazz.start') }}">
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
                        <th>Pascabayar</th>
                        <th>Mulai</th>
                        <th>Selesai</th>
                        <th style="width: 6rem;">Log</th>
                        <th style="width: 8rem;" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody data-ajax-rows>
                    @forelse ($jobs as $job)
                        <tr data-job-id="{{ $job->job_id }}" data-status="{{ $job->status }}">
                            <td class="font-monospace fs-11">{{ $job->job_id }}</td>
                            <td>{{ ucfirst($job->type) }}</td>
                            <td>
                                @if ($job->status === 'running')
                                    <span class="pill bg-gold tx-gold">Berjalan</span>
                                @elseif ($job->status === 'completed')
                                    <span class="pill bg-mint-l tx-mintt">Selesai</span>
                                @else
                                    <span class="pill bg-errc-l tx-err">Gagal</span>
                                @endif
                            </td>
                            <td>
                                @if ($job->include_pascabayar)
                                    <span class="pill bg-sky-l tx-skyd">Ya</span>
                                @else
                                    <span class="pill bg-glass tx-mute">Tidak</span>
                                @endif
                            </td>
                            <td>{{ $job->started_at?->format('d/m/Y H:i:s') ?? '-' }}</td>
                            <td>{{ $job->finished_at?->format('d/m/Y H:i:s') ?? '-' }}</td>
                            <td class="fw-bold text-end">{{ $job->total_logs }}</td>
                            <td class="text-end">
                                <x-ui.actions :actions="[
                                    ['label' => 'Lihat Log', 'icon' => 'terminal', 'action' => 'view', 'href' => route('admin.products.sync-digiflazz.show', $job)],
                                ]" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center tx-mute py-4">Belum ada riwayat sinkronisasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-slot:footer>
            <div class="d-flex justify-content-between align-items-center">
                <span class="fs-12 tx-mute">Menampilkan {{ $jobs->firstItem() ?? 0 }}–{{ $jobs->lastItem() ?? 0 }} dari {{ $jobs->total() }} data</span>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <span class="fs-12 tx-mute">Menampilkan {{ $jobs->firstItem() ?? 0 }}–{{ $jobs->lastItem() ?? 0 }} dari {{ $jobs->total() }} job</span>
                    @include('admin.partials.table-per-page')
                </div>
                {{ $jobs->links() }}
            </div>
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
                body: JSON.stringify({ include_pascabayar: false }),
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