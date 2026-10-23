@props(['section', 'id' => null])

@php
    $columns = $section['columns'];
    $rows = $section['rows'];
@endphp

<div class="card-ap r-card p-3 mb-4" {{ $id ? "id={$id}" : '' }}>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
        <h3 class="fw-bold fs-6 m-0">{{ $section['title'] }}</h3>
        <span class="fs-11 tx-mute">{{ count($rows) }} baris</span>
    </div>

    @if (! empty($section['note']))
        <p class="fs-12 tx-mute mb-3">{{ $section['note'] }}</p>
    @endif

    @if (count($rows) === 0)
        <x-ui.empty-state icon="bar_chart" title="Belum ada data"
            description="Tidak ada data pada filter/periode yang dipilih." />
    @else
        <div class="table-responsive">
            <table class="table table-ui align-middle">
                <thead>
                    <tr>
                        @foreach ($columns as $column)
                            <th class="{{ ($column['align'] ?? 'l') === 'r' ? 'text-end' : '' }}">{{ $column['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            @foreach (array_values($row) as $index => $cell)
                                @php $column = $columns[$index] ?? ['type' => 'text']; @endphp
                                <td class="{{ ($column['align'] ?? 'l') === 'r' ? 'text-end num' : '' }}">
                                    {{ \App\Support\Report\ReportFormatter::display($column['type'] ?? 'text', $cell) }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
