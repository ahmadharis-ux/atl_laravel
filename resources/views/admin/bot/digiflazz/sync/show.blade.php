@extends('layouts.admin')

@section('title', 'Log Sinkronisasi {{ $job->job_id }} - Admin Atlantic Pedia')
@section('page-title', 'Log Sinkronisasi Digiflazz')

@section('content')
<div class="d-flex flex-column gap-4" data-ajax
     data-ajax-url="{{ route('admin.bot.digiflazz.sync.show', $job) }}"
     data-persist-key="admin.bot.digiflazz.sync.show.{{ $job->job_id }}">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h1 class="fw-bold fs-4 m-0">Log Sinkronisasi</h1>
            <p class="tx-mute fs-12 m-0">Job ID: <code class="fs-11">{{ $job->job_id }}</code></p>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if ($job->status === 'running')
                <span class="pill bg-gold tx-gold">Berjalan</span>
            @elseif ($job->status === 'completed')
                <span class="pill bg-mint-l tx-mintt">Selesai</span>
            @else
                <span class="pill bg-errc-l tx-err">Gagal</span>
            @endif
            <a href="{{ route('admin.bot.digiflazz.sync.index') }}" class="btn-soft">
                <span class="ms ms-sm align-middle me-1">arrow_back</span>Kembali
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <x-ui.card title="Info Job">
                <div class="row g-2 fs-12">
                    <div class="col-5 fw-bold tx-mute">Job ID</div>
                    <div class="col-7 font-monospace fs-11">{{ $job->job_id }}</div>

                    <div class="col-5 fw-bold tx-mute">Tipe</div>
                    <div class="col-7">{{ ucfirst($job->type) }}</div>

                    <div class="col-5 fw-bold tx-mute">Status</div>
                    <div class="col-7">
                        @if ($job->status === 'running')
                            <span class="pill bg-gold tx-gold">Berjalan</span>
                        @elseif ($job->status === 'completed')
                            <span class="pill bg-mint-l tx-mintt">Selesai</span>
                        @else
                            <span class="pill bg-errc-l tx-err">Gagal</span>
                        @endif
                    </div>

                    <div class="col-5 fw-bold tx-mute">Mulai</div>
                    <div class="col-7">{{ $job->started_at?->format('d/m/Y H:i:s') ?? '-' }}</div>

                    <div class="col-5 fw-bold tx-mute">Selesai</div>
                    <div class="col-7">{{ $job->finished_at?->format('d/m/Y H:i:s') ?? '-' }}</div>

                    <div class="col-5 fw-bold tx-mute">Total Log</div>
                    <div class="col-7 fw-bold">{{ $job->total_logs }}</div>
                </div>
            </x-ui.card>
        </div>

        <div class="col-lg-8">
            <x-ui.card title="Log Sinkronisasi">
                <div class="d-flex align-items-center gap-2 mb-3">
                    @if ($job->status === 'running')
                        <span class="pill bg-gold tx-gold fs-11">Auto-refresh aktif (2 detik)</span>
                    @endif
                    <div class="ms-auto">
                        <button type="button" class="btn-soft btn-sm" data-log-refresh>
                            <span class="ms ms-sm align-middle me-1">refresh</span>Refresh
                        </button>
                        <button type="button" class="btn-soft btn-sm" data-log-clear>
                            <span class="ms ms-sm align-middle me-1">delete</span>Bersihkan
                        </button>
                    </div>
                </div>

                <div class="sync-log-container" style="max-height: 500px; overflow-y: auto; background: #0d1117; border-radius: 0.5rem; padding: 1rem; font-family: monospace; font-size: 0.75rem;"
                     data-job-id="{{ $job->job_id }}"
                     data-logs-url="{{ route('admin.bot.digiflazz.sync.show', $job) }}">
                    <div class="tx-mute text-center py-4" id="syncLogEmpty">Memuat log...</div>
                    <pre id="syncLogContent" style="margin: 0; white-space: pre-wrap; word-break: break-word;"></pre>
                </div>
            </x-ui.card>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    function initSyncLogDetail() {
        var container = document.querySelector('.sync-log-container');
        if (!container) return;

        var contentEl = document.getElementById('syncLogContent');
        var emptyEl = document.getElementById('syncLogEmpty');
        var refreshBtn = document.querySelector('[data-log-refresh]');
        var clearBtn = document.querySelector('[data-log-clear]');
        var jobId = container.getAttribute('data-job-id');
        var baseUrl = container.getAttribute('data-logs-url');
        var pollInterval = null;
        var lastLogId = 0;

        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function renderLog(log) {
            var time = log.time || '';
            var level = log.level || 'info';
            var message = escapeHtml(log.message);
            var context = log.context ? ' <span class="tx-mute">'+escapeHtml(JSON.stringify(log.context))+'</span>' : '';
            var levelClass = level === 'error' ? 'tx-err' : (level === 'warning' ? 'tx-gold' : 'tx-mintt');
            return '<span class="tx-mute">['+time+']</span> <span class="'+levelClass+'">['+level.toUpperCase()+']</span> '+message+context;
        }

        function appendLogs(logs) {
            if (!logs.length) return;
            if (emptyEl) emptyEl.style.display = 'none';
            logs.forEach(function (log) {
                if (log.id > lastLogId) {
                    var line = document.createElement('div');
                    line.innerHTML = renderLog(log);
                    contentEl.appendChild(line);
                    lastLogId = log.id;
                }
            });
            contentEl.scrollTop = contentEl.scrollHeight;
        }

        function loadLogs(append) {
            var url = new URL(baseUrl, window.location.origin);
            url.searchParams.set('ajax', '1');

            fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.logs) {
                        if (!append) {
                            contentEl.innerHTML = '';
                            lastLogId = 0;
                        }
                        appendLogs(data.logs);
                    }
                    if (data.job && data.job.status !== 'running') {
                        stopPolling();
                    }
                })
                .catch(function () {
                    AP.showToast('Gagal memuat log.', 'error');
                });
        }

        function startPolling() {
            stopPolling();
            loadLogs(false);
            pollInterval = setInterval(function () { loadLogs(true); }, 2000);
        }

        function stopPolling() {
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
            }
        }

        var baseUrl = container.getAttribute('data-logs-url') || window.location.href;

        if (jobId) {
            startPolling();
        }

        refreshBtn?.addEventListener('click', function () {
            loadLogs(false);
        });

        clearBtn?.addEventListener('click', function () {
            contentEl.innerHTML = '';
            lastLogId = 0;
            if (emptyEl) emptyEl.style.display = 'block';
        });

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) stopPolling();
            else if (jobId) startPolling();
        });
    }

    if (document.readyState !== 'loading') initSyncLogDetail();
    else document.addEventListener('DOMContentLoaded', initSyncLogDetail);
})();
</script>
@endpush