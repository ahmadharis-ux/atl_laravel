@props(['action', 'exportRoute', 'exportQuery' => []])

<div class="card-ap r-card p-3 mb-4">
    <form method="GET" action="{{ $action }}" class="d-flex flex-wrap align-items-end gap-2">
        <div class="d-flex flex-column gap-1">
            <label class="fs-11 fw-semibold tx-mute" for="filter-from">Dari Tanggal</label>
            <input type="date" id="filter-from" name="from" value="{{ request('from') }}" class="ui-input" style="width:auto">
        </div>
        <div class="d-flex flex-column gap-1">
            <label class="fs-11 fw-semibold tx-mute" for="filter-to">Sampai Tanggal</label>
            <input type="date" id="filter-to" name="to" value="{{ request('to') }}" class="ui-input" style="width:auto">
        </div>
        <button type="submit" class="btn-navy">Terapkan</button>
        @if (request('from') || request('to'))
            <a href="{{ $action }}" class="btn-soft" data-tip="Reset filter"><span class="ms ms-sm align-middle">restart_alt</span> Reset</a>
        @endif

        <div class="d-flex gap-2 ms-auto">
            <a href="{{ route($exportRoute, array_merge($exportQuery, ['format' => 'xlsx'])) }}" class="btn-soft">
                <span class="ms ms-sm align-middle">table_view</span> Excel
            </a>
            <a href="{{ route($exportRoute, array_merge($exportQuery, ['format' => 'pdf'])) }}" class="btn-soft">
                <span class="ms ms-sm align-middle">picture_as_pdf</span> PDF
            </a>
        </div>
    </form>
</div>
