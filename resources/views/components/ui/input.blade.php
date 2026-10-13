@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'hint' => null])

@php($isPassword = $type === 'password')

<div {{ $attributes->only('class') }}>
    @if ($label)<label class="ui-label" for="{{ $name }}">{{ $label }}</label>@endif
    <div class="ui-input-wrap{{ $isPassword ? ' has-eye' : '' }}">
        <input
            {{ $attributes->except('class')->merge(['class' => 'ui-input']) }}
            id="{{ $name }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ $value ?? old($name) }}"
        >
        @if ($isPassword)
            <button type="button" class="ui-eye" data-toggle-password="{{ $name }}" tabindex="-1"
                aria-label="Tampilkan atau sembunyikan kata sandi">
                <span class="ms ms-sm">visibility</span>
            </button>
        @endif
    </div>
    @if ($hint)<div class="ui-hint">{{ $hint }}</div>@endif
    @error($name)<div class="err show">{{ $message }}</div>@enderror
</div>