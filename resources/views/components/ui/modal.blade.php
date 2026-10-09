@props(['id', 'title' => null, 'size' => null, 'closeBtn' => true])

@php $sizeClass = $size ? 'ap-modal-' . $size : ''; @endphp

<div {{ $attributes->merge(['class' => 'ap-modal']) }} id="{{ $id }}" hidden>
    <div class="ap-modal-backdrop" data-modal-close></div>
    <div class="ap-modal-dialog {{ $sizeClass }}" role="dialog" aria-modal="true" @if ($title) aria-label="{{ $title }}" @endif>
        @if ($title || $closeBtn)
            <div class="ap-modal-head">
                <h3>{{ $title }}</h3>
                @if ($closeBtn)
                    <button type="button" class="ap-icon-btn" data-modal-close data-tip="Tutup"><span class="ms ms-md">close</span></button>
                @endif
            </div>
        @endif
        <div class="ap-modal-body">{{ $slot }}</div>
        @isset($footer)
            <div class="ap-modal-foot">{{ $footer }}</div>
        @endisset
    </div>
</div>