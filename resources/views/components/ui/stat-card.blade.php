@props(['label', 'value', 'icon' => 'insights', 'tone' => 'bg-tealc-m tx-tealt', 'trend' => null])

<div {{ $attributes->merge(['class' => 'card-ap r-sm p-3 h-100 d-flex flex-row align-items-center gap-3']) }}>
    <span class="ibox ibox-48 {{ $tone }}"><span class="ms">{{ $icon }}</span></span>
    <div style="min-width:0">
        <div class="fs-12 tx-mute text-truncate">{{ $label }}</div>
        <div class="num fw-800 fs-4 lh-1">{{ $value }}</div>
        @if ($trend)
            <div class="fs-11 fw-bold {{ str_starts_with($trend, '+') ? 'tx-mintt' : 'tx-err' }}">{{ $trend }}</div>
        @endif
    </div>
</div>