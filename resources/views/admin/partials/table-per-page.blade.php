@php
    $options = [10, 50, 100, 150, 200, 250];
    $current = (int) request()->query('per_page', 10);
    if (!in_array($current, $options, true)) {
        $current = 10;
    }
@endphp
<div class="d-flex align-items-center gap-2 flex-wrap">
    <form method="GET" action="{{ request()->url() }}" class="d-flex align-items-center gap-2" id="admin-per-page-form">
        @foreach(request()->query() as $key => $value)
            @if($key === 'per_page' || $key === 'page')
                @continue
            @endif
            @if(is_array($value))
                @foreach($value as $v)
                    <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                @endforeach
            @else
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
        <span class="text-muted small">Tampilkan</span>
        <select name="per_page" class="form-select form-select-sm" style="width:auto; min-width: 70px;" onchange="document.getElementById('admin-per-page-form').submit()">
            @foreach($options as $opt)
                <option value="{{ $opt }}" @selected($current === $opt)>{{ $opt }}</option>
            @endforeach
        </select>
        <span class="text-muted small">entri</span>
    </form>
</div>
