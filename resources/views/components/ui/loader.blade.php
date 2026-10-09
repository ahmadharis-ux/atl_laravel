@props(['id' => 'ap-loader', 'text' => 'Memproses...'])

<div id="{{ $id }}" class="ap-loader" data-loader hidden aria-hidden="true" role="status" aria-live="polite">
    <div class="ap-loader-box">
        <span class="ap-loader-spinner" aria-hidden="true"></span>
        <span class="ap-loader-label">{{ $text }}</span>
        <span class="ap-loader-time" data-loader-time>0.0s</span>
    </div>
</div>