@php($isRaw = $raw ?? false)
<div class="mb-3">
    <div class="ui-label mb-1">{{ $label }}</div>
    <div class="fs-12">{!! $isRaw ? $value : e($value) !!}</div>
</div>