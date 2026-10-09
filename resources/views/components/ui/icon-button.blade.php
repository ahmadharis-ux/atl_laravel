@props(['icon', 'tip' => null, 'tone' => 'tx-tealt', 'href' => null])

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'ap-icon-btn ' . $tone]) }} @if ($tip) data-tip="{{ $tip }}" @endif>
        <span class="ms ms-md">{{ $icon }}</span>
    </a>
@else
    <button type="button" {{ $attributes->merge(['class' => 'ap-icon-btn ' . $tone]) }} @if ($tip) data-tip="{{ $tip }}" @endif>
        <span class="ms ms-md">{{ $icon }}</span>
    </button>
@endif