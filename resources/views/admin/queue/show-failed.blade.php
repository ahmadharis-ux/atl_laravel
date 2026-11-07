@extends('layouts.admin')

@section('title', 'Detail Failed Job')

@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h4 mb-0">Detail Failed Job</h1>
        <p class="text-muted mb-0 small">UUID: {{ $job->uuid }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.queue.index', ['tab'=>'failed']) }}" class="btn btn-sm btn-light">Kembali</a>
        <form method="POST" action="{{ route('admin.queue.retry', $job->uuid) }}" onsubmit="return confirm('Coba ulang job ini?')">
            @csrf
            <button class="btn btn-sm btn-primary">Retry</button>
        </form>
        <form method="POST" action="{{ route('admin.queue.forget', $job->uuid) }}" onsubmit="return confirm('Hapus job ini?')">
            @csrf
            <button class="btn btn-sm btn-danger">Forget</button>
        </form>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><strong>Info</strong></div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-4">Connection</dt><dd class="col-8">{{ $job->connection }}</dd>
                    <dt class="col-4">Queue</dt><dd class="col-8">{{ $job->queue }}</dd>
                    <dt class="col-4">Failed At</dt><dd class="col-8">{{ $job->failed_at }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><strong>Payload (decoded)</strong></div>
            <div class="card-body">
                <pre class="mb-0 small" style="max-height: 320px; overflow:auto;">{{ json_encode($payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card">
            <div class="card-header"><strong>Exception</strong></div>
            <div class="card-body">
                <pre class="mb-0 small" style="max-height: 420px; overflow:auto;">{{ $job->exception }}</pre>
            </div>
        </div>
    </div>
</div>
@endsection
