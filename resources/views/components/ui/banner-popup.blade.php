@props(['id', 'image' => null, 'title' => null, 'body' => null, 'link' => null, 'ctaLabel' => null])

@php
    $isExternal = $link && \Illuminate\Support\Str::startsWith($link, ['http://', 'https://']);
@endphp

<div {{ $attributes->merge(['class' => 'ap-modal ap-banner-pop']) }} id="{{ $id }}" hidden>
    <div class="ap-modal-backdrop ap-banner-pop-backdrop" data-modal-close></div>
    <div class="ap-banner-pop-body" role="dialog" aria-modal="true" @if ($title) aria-label="{{ $title }}" @endif>
        <button type="button" class="ap-banner-pop-close" data-modal-close aria-label="Tutup"><span class="ms ms-sm">close</span></button>

        @if ($image)
            @if ($link)
                <a href="{{ $link }}" @if ($isExternal) target="_blank" rel="noopener" @endif>
                    <img src="{{ $image }}" alt="{{ $title }}" class="ap-banner-pop-img">
                </a>
            @else
                <img src="{{ $image }}" alt="{{ $title }}" class="ap-banner-pop-img">
            @endif
        @else
            <div class="ap-banner-pop-card">
                @if ($title)
                    <h3 class="fw-bold fs-5 m-0">{{ $title }}</h3>
                @endif
                @if ($body)
                    <p class="tx-mute fs-12 mt-2 mb-0">{{ $body }}</p>
                @endif
                @if ($link)
                    <a href="{{ $link }}" class="btn-navy mt-3" @if ($isExternal) target="_blank" rel="noopener" @endif>{{ $ctaLabel ?: 'Selengkapnya' }}</a>
                @endif
            </div>
        @endif
    </div>
</div>
