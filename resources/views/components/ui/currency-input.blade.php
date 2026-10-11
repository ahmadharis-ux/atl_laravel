@props(['name', 'label' => null, 'value' => null, 'hint' => null, 'prefix' => 'Rp'])

@php
    $raw = preg_replace('/\D/', '', (string) ($value ?? old($name, '')));
    $display = $raw === '' ? '' : preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $raw);
@endphp

<div {{ $attributes->only('class') }}>
    @if ($label)<label class="ui-label" for="{{ $name }}">{{ $label }}</label>@endif
    <div class="ui-currency" data-currency>
        @if ($prefix)<span class="ui-currency-prefix">{{ $prefix }}</span>@endif
        <input
            {{ $attributes->except('class') }}
            class="ui-currency-input"
            id="{{ $name }}"
            type="text"
            inputmode="numeric"
            autocomplete="off"
            placeholder="0"
            data-currency-display
            value="{{ $display }}"
        >
        <input type="hidden" name="{{ $name }}" data-currency-value value="{{ $raw }}">
    </div>
    @if ($hint)<div class="ui-hint">{{ $hint }}</div>@endif
    @error($name)<div class="err show">{{ $message }}</div>@enderror
</div>
