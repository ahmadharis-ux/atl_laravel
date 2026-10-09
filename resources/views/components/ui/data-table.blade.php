@props([
    'columns' => [],
    'filters' => [],
    'bulk' => false,
    'search' => true,
    'searchPlaceholder' => 'Cari...',
    'title' => null,
    'bulkActions' => [
        ['value' => 'delete', 'label' => 'Hapus', 'icon' => 'delete', 'tone' => 'text-danger border-0 fw-bold'],
    ],
])

@php($id = $attributes->get('id', 'dt-'.\Illuminate\Support\Str::random(6)))

<div {{ $attributes->except('id')->merge(['class' => 'card-ap r-card p-3']) }} id="{{ $id }}" data-datatable="{{ $id }}">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h3 class="fw-bold fs-6 m-0">{{ $title ?? '' }}</h3>
        <div class="d-flex flex-wrap align-items-center gap-2">
            @foreach ($filters as $f)
                <select class="ui-select" style="width:auto" data-dt-filter data-col="{{ $f['col'] ?? $f['name'] }}" aria-label="{{ $f['label'] ?? 'Filter' }}">
                    <option value="">{{ $f['label'] ?? 'Semua' }}</option>
                    @foreach (($f['options'] ?? []) as $val => $text)
                        <option value="{{ $val }}">{{ $text }}</option>
                    @endforeach
                </select>
            @endforeach
            @if ($search)
                <div class="d-flex align-items-center bg-white r-sm px-3" style="border:1px solid #d3e3e1;height:2.5rem">
                    <span class="ms ms-md tx-mute me-2">search</span>
                    <input class="border-0 bg-transparent fs-12 js-dt-search" style="outline:0;width:12rem" placeholder="{{ $searchPlaceholder }}">
                </div>
            @endif
        </div>
    </div>

    @if ($bulk)
        <div class="bulk-bar d-none align-items-center justify-content-between gap-2 mb-3 p-2 rounded-3 flex-wrap js-dt-bulk">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="pill bg-tealc-m tx-tealt"><span class="js-dt-count">0</span> dipilih</span>
                @foreach ($bulkActions as $a)
                    <button type="button" class="btn btn-sm {{ $a['tone'] ?? 'btn-soft' }}" data-dt-action="{{ $a['value'] }}">
                        @if (! empty($a['icon']))<span class="ms ms-sm align-middle">{{ $a['icon'] }}</span>@endif
                        {{ $a['label'] }}
                    </button>
                @endforeach
            </div>
            <button type="button" class="btn btn-sm btn-link tx-mute js-dt-clear">Batalkan pilihan</button>
        </div>
    @endif

    <div class="table-responsive">
        <table class="table table-ui align-middle">
            <thead>
                <tr>
                    @if ($bulk)
                        <th class="chk-cell"><input class="form-check-input m-0 js-dt-checkall" type="checkbox" aria-label="Pilih semua"></th>
                    @endif
                    @foreach ($columns as $c)
                        <th class="{{ $c['class'] ?? '' }}">{{ $c['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody data-ajax-rows>{{ $slot }}</tbody>
        </table>
    </div>

    @isset($footer)
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2" data-ajax-footer>{{ $footer }}</div>
    @endisset
</div>