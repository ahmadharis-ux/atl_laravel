@extends('layouts.admin')

@section('title', 'Queue Monitor')

@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h4 mb-0">Queue Monitor</h1>
        <p class="text-muted mb-0 small">Pantau jobs, failed jobs & antrian</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <form method="POST" action="{{ route('admin.queue.retry-all') }}" onsubmit="return confirm('Coba ulang SEMUA failed job?')">
            @csrf
            <button class="btn btn-sm btn-outline-primary">Retry All Failed</button>
        </form>
        <form method="POST" action="{{ route('admin.queue.flush') }}" onsubmit="return confirm('Hapus SEMUA failed job?')">
            @csrf
            <button class="btn btn-sm btn-outline-danger">Flush Failed</button>
        </form>
    </div>
</div>

<div class="row g-2 mb-3">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Menunggu</div>
                    <div class="h4 mb-0">{{ number_format($stats['pending']) }}</div>
                </div>
                <span class="badge bg-warning text-dark">jobs</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Gagal</div>
                    <div class="h4 mb-0">{{ number_format($stats['failed']) }}</div>
                </div>
                <span class="badge bg-danger">failed_jobs</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Batches</div>
                    <div class="h4 mb-0">{{ number_format($stats['batches']) }}</div>
                </div>
                <span class="badge bg-secondary">job_batches</span>
            </div>
        </div>
    </div>
</div>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link @if($tab==='pending') active @endif" href="{{ route('admin.queue.index', ['tab'=>'pending']) }}">Pending Jobs ({{ $stats['pending'] }})</a>
    </li>
    <li class="nav-item">
        <a class="nav-link @if($tab==='failed') active @endif" href="{{ route('admin.queue.index', ['tab'=>'failed']) }}">Failed Jobs ({{ $stats['failed'] }})</a>
    </li>
</ul>

@if($tab === 'pending')
    <div class="card">
        <div class="card-body">
            @include('admin.partials.table-per-page')
        </div>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Queue</th>
                        <th>Job</th>
                        <th>Attempts</th>
                        <th>Reserved</th>
                        <th>Created</th>
                        <th>Available</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pending as $job)
                        <tr>
                            <td>{{ $job['id'] }}</td>
                            <td><span class="badge bg-light text-dark">{{ $job['queue'] }}</span></td>
                            <td class="text-truncate" style="max-width: 320px;">{{ $job['name'] }}</td>
                            <td>{{ $job['attempts'] }}</td>
                            <td>{{ $job['reserved_at'] ? 'Ya' : '-' }}</td>
                            <td>{{ $job['created_at'] }}</td>
                            <td>{{ $job['available_at'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada pending job</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="text-muted small">Menampilkan {{ $pending->firstItem() ?? 0 }}–{{ $pending->lastItem() ?? 0 }} dari {{ $pending->total() }}</div>
            {{ $pending->links() }}
        </div>
    </div>
@endif

@if($tab === 'failed')
    <div class="card">
        <div class="card-body">
            @include('admin.partials.table-per-page')
        </div>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Queue</th>
                        <th>Job</th>
                        <th>Connection</th>
                        <th>Failed At</th>
                        <th>Exception</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($failed as $job)
                        <tr>
                            <td>{{ $job['id'] }}</td>
                            <td><span class="badge bg-light text-dark">{{ $job['queue'] }}</span></td>
                            <td class="text-truncate" style="max-width: 280px;">{{ $job['name'] }}</td>
                            <td>{{ $job['connection'] }}</td>
                            <td>{{ $job['failed_at'] }}</td>
                            <td class="text-truncate" style="max-width: 360px;">{{ $job['exception'] }}</td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('admin.queue.failed.show', $job['uuid']) }}" class="btn btn-sm btn-outline-secondary">Lihat</a>
                                    <form method="POST" action="{{ route('admin.queue.retry', $job['uuid']) }}" onsubmit="return confirm('Coba ulang job ini?')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-primary">Retry</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.queue.forget', $job['uuid']) }}" onsubmit="return confirm('Hapus job ini dari failed list?')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger">Forget</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada failed job</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="text-muted small">Menampilkan {{ $failed->firstItem() ?? 0 }}–{{ $failed->lastItem() ?? 0 }} dari {{ $failed->total() }}</div>
            {{ $failed->links() }}
        </div>
    </div>
@endif
@endsection
