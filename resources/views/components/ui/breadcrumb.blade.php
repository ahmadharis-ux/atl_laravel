@props(['items' => []])

@php
    $crumbs = collect($items)->map(function ($item) {
        if (is_string($item)) {
            return ['label' => $item, 'url' => null];
        }

        return ['label' => $item['label'] ?? '', 'url' => $item['url'] ?? null];
    })->filter(fn ($c) => $c['label'] !== '')->values();
@endphp

@if ($crumbs->isNotEmpty())
    <nav {{ $attributes->merge(['class' => 'crumb']) }} aria-label="Breadcrumb">
        <ol class="crumb-list">
            @foreach ($crumbs as $index => $crumb)
                @php($isLast = $index === $crumbs->count() - 1)
                <li class="crumb-item">
                    @if (! $isLast && $crumb['url'])
                        <a href="{{ $crumb['url'] }}" class="crumb-link crumb-label">
                            <span class="crumb-label-text">{{ $crumb['label'] }}</span>
                        </a>
                    @else
                        <span @class(['crumb-current', 'crumb-label' => $isLast]) @if ($isLast) aria-current="page" @endif>
                            <span class="crumb-label-text">{{ $crumb['label'] }}</span>
                        </span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
