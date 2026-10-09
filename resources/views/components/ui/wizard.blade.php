@props(['id' => 'wizard', 'steps' => [], 'submitLabel' => 'Kirim', 'pad' => 'p-3 p-lg-4'])

<div {{ $attributes->merge(['class' => 'card-ap r-card js-wizard ' . $pad]) }} id="{{ $id }}">
    <div class="wz-steps mb-4 js-wz-steps">
        @foreach ($steps as $i => $step)
            <div class="wz-step {{ $i === 0 ? 'active' : '' }}" data-step="{{ $i + 1 }}">{{ $i + 1 }}. {{ $step }}</div>
        @endforeach
    </div>

    {{ $slot }}

    <div class="d-flex justify-content-between gap-2 mt-4">
        <button type="button" class="btn-soft js-wz-prev" disabled>Kembali</button>
        <div class="d-flex gap-2">
            <button type="button" class="btn-navy js-wz-next">Lanjut</button>
            <button type="button" class="btn-navy d-none js-wz-submit" style="background:var(--mintt)">{{ $submitLabel }}</button>
        </div>
    </div>
</div>