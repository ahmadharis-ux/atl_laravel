@props(['text', 'position' => 'top', 'truncate' => null])

<span {{ $attributes->merge(['class' => 'ap-tip']) }} data-tip="{{ $text }}" data-tip-pos="{{ $position }}">
    @if ($truncate)
        <span class="d-inline-block text-truncate align-bottom" style="max-width: {{ is_numeric($truncate) ? $truncate . 'px' : $truncate }}">{{ $slot }}</span>
    @else
        {{ $slot }}
    @endif
</span>