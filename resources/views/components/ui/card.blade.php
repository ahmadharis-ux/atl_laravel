@props(['title' => null, 'pad' => 'p-3'])

<div {{ $attributes->merge(['class' => 'card-ap r-card ' . $pad]) }}>
    @if ($title || isset($header))
        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
            @if ($title)<h3 class="fw-bold fs-6 m-0">{{ $title }}</h3>@endif
            {{ $header ?? '' }}
        </div>
    @endif
    {{ $slot }}
</div>