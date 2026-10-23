@extends('layouts.admin')

@section('title', 'Laporan Pelanggan - Admin Atlantic Pedia')
@section('page-title', 'Laporan Pelanggan')

@php
    $segmentOptions = ['' => 'Semua Segmen', 'Baru' => 'Baru', 'Aktif' => 'Aktif', 'Berisiko Churn' => 'Berisiko Churn', 'Tidak Aktif' => 'Tidak Aktif'];
@endphp

@section('content')
<div class="d-flex flex-column gap-0">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <h1 class="fw-bold fs-4 m-0">Pelanggan &amp; CRM</h1>
            <p class="tx-mute fs-12 m-0">Aktivitas transaksi per pelanggan, top spender, dan segmen retensi.</p>
        </div>
    </div>

    <div class="card-ap r-card p-3 mb-4">
        <form method="GET" action="{{ route('admin.reports.customers') }}" class="d-flex flex-wrap align-items-end gap-2">
            <div class="d-flex flex-column gap-1">
                <label class="fs-11 fw-semibold tx-mute" for="customer-segment">Segmen</label>
                <select id="customer-segment" name="segment" class="ui-input" style="width:auto">
                    @foreach ($segmentOptions as $value => $label)
                        <option value="{{ $value }}" @selected($activeSegment === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex flex-column gap-1">
                <label class="fs-11 fw-semibold tx-mute" for="customer-q">Cari</label>
                <input type="text" id="customer-q" name="q" value="{{ request('q') }}" class="ui-input" style="width:auto" placeholder="Nama atau email…">
            </div>
            <button type="submit" class="btn-navy">Terapkan</button>
            @if ($activeSegment !== '' || request('q'))
                <a href="{{ route('admin.reports.customers') }}" class="btn-soft" data-tip="Reset filter"><span class="ms ms-sm align-middle">restart_alt</span> Reset</a>
            @endif

            <div class="d-flex gap-2 ms-auto">
                <a href="{{ route('admin.reports.export', array_merge($exportQuery, ['section' => 'customers', 'format' => 'xlsx'])) }}" class="btn-soft">
                    <span class="ms ms-sm align-middle">table_view</span> Excel
                </a>
                <a href="{{ route('admin.reports.export', array_merge($exportQuery, ['section' => 'customers', 'format' => 'pdf'])) }}" class="btn-soft">
                    <span class="ms ms-sm align-middle">picture_as_pdf</span> PDF
                </a>
            </div>
        </form>
    </div>

    <div class="row g-3 row-cols-1 row-cols-sm-2 row-cols-xl-4 m-0 mb-4">
        @foreach ($report['cards'] as $card)
            <div class="col"><x-ui.stat-card :label="$card['label']" :value="$card['value']" :icon="$card['icon']" :tone="$card['tone']" :sub="$card['sub']" /></div>
        @endforeach
    </div>

    @include('admin.report._section', ['section' => $report['sections'][0]])

    @php $main = $report['sections'][1]; @endphp
    <div class="card-ap r-card p-3 mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
            <h3 class="fw-bold fs-6 m-0">{{ $main['title'] }}</h3>
            <span class="fs-11 tx-mute">{{ number_format($report['paginator']->total(), 0, ',', '.') }} pelanggan</span>
        </div>
        <p class="fs-12 tx-mute mb-3">{{ $main['note'] }}</p>

        @if ($report['paginator']->isEmpty())
            <x-ui.empty-state icon="group" title="Belum ada data"
                description="Tidak ada pelanggan pada filter/segmen yang dipilih." />
        @else
            <div class="table-responsive">
                <table class="table table-ui align-middle">
                    <thead>
                        <tr>
                            @foreach ($main['columns'] as $column)
                                <th class="{{ ($column['align'] ?? 'l') === 'r' ? 'text-end' : '' }}">{{ $column['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report['paginator'] as $row)
                            <tr>
                                @foreach (array_values($row) as $index => $cell)
                                    @php $column = $main['columns'][$index] ?? ['type' => 'text']; @endphp
                                    <td class="{{ ($column['align'] ?? 'l') === 'r' ? 'text-end num' : '' }}">
                                        {{ \App\Support\Report\ReportFormatter::display($column['type'] ?? 'text', $cell) }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $report['paginator']->appends(request()->query())->links() }}
        @endif
    </div>
</div>
@endsection
