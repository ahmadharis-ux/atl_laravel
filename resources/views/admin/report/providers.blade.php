@extends('layouts.admin')

@section('title', 'Laporan Provider - Admin Atlantic Pedia')
@section('page-title', 'Laporan Provider')

@section('content')
<div class="d-flex flex-column gap-0">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <h1 class="fw-bold fs-4 m-0">Provider</h1>
            <p class="tx-mute fs-12 m-0">Provider yang sering gangguan &amp; yang selalu aman pada periode berjalan.</p>
        </div>
    </div>

    @include('admin.report._filter', ['action' => route('admin.reports.providers'), 'exportRoute' => 'admin.reports.export', 'exportQuery' => array_merge(['section' => 'providers'], $exportQuery)])

    <p class="fs-12 tx-mute mt-n3 mb-4">Menampilkan periode <span class="fw-semibold">{{ $period->label() }}</span> ({{ $period->days() }} hari){{ $period->isDefault ? ' — default 30 hari terakhir' : '' }}.</p>

    <div class="row g-3 row-cols-1 row-cols-sm-2 row-cols-xl-4 m-0 mb-4">
        @foreach ($report['cards'] as $card)
            <div class="col"><x-ui.stat-card :label="$card['label']" :value="$card['value']" :icon="$card['icon']" :tone="$card['tone']" :sub="$card['sub']" /></div>
        @endforeach
    </div>

    @foreach ($report['sections'] as $section)
        @include('admin.report._section', ['section' => $section])
    @endforeach
</div>
@endsection
