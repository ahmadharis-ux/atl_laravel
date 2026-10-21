@extends('layouts.admin')

@section('title', 'Laporan Ringkasan - Admin Atlantic Pedia')
@section('page-title', 'Laporan Ringkasan')

@section('content')
<div class="d-flex flex-column gap-0">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <h1 class="fw-bold fs-4 m-0">Transaksi &amp; Laba</h1>
            <p class="tx-mute fs-12 m-0">Ringkasan performa bisnis, tren, dan komposisi transaksi (SWOT).</p>
        </div>
    </div>

    @include('admin.report._filter', ['action' => route('admin.reports.overview'), 'exportRoute' => 'admin.reports.export', 'exportQuery' => array_merge(['section' => 'overview'], $exportQuery)])

    <p class="fs-12 tx-mute mt-n3 mb-4">Menampilkan periode <span class="fw-semibold">{{ $period->label() }}</span> ({{ $period->days() }} hari){{ $period->isDefault ? ' — default 30 hari terakhir' : '' }}.</p>

    <div class="row g-3 row-cols-1 row-cols-sm-2 row-cols-xl-3 m-0 mb-4">
        @foreach ($report['cards'] as $card)
            <div class="col"><x-ui.stat-card :label="$card['label']" :value="$card['value']" :icon="$card['icon']" :tone="$card['tone']" :sub="$card['sub']" /></div>
        @endforeach
    </div>

    <div class="card-ap r-card p-3 mb-4">
        <h3 class="fw-bold fs-6 m-0 mb-1">Tren 30 Hari Terakhir</h3>
        <p class="fs-12 tx-mute mb-3">Jumlah transaksi &amp; pendapatan harian (harian terbaru — tidak terpengaruh filter periode).</p>
        <x-ui.chart id="chartOverview" type="line" :config="$report['chart']" />
    </div>

    @foreach ($report['sections'] as $section)
        @include('admin.report._section', ['section' => $section])
    @endforeach
</div>
@endsection
