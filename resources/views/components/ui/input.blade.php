@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'hint' => null, 'id' => null])

@php($isPassword = $type === 'password')
@php($inputId = $id ?? $name)
@php($hasError = $errors->has($name))

<div {{ $attributes->only('class') }}>
    @if ($label)<label class="ui-label" for="{{ $inputId }}">{{ $label }}</label>@endif
    <div class="ui-input-wrap{{ $isPassword ? ' has-eye' : '' }}">
        <input
            {{ $attributes->except(['class', 'id'])->merge(['class' => 'ui-input']) }}
            id="{{ $inputId }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ $type === 'file' ? null : $value ?? old($name) }}"
            @if ($hasError) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
        >
        @if ($isPassword)
            <button type="button" class="ui-eye" data-toggle-password="{{ $name }}" tabindex="-1"
                aria-label="Tampilkan atau sembunyikan kata sandi">
                <span class="ms ms-sm">visibility</span>
            </button>
        @endif
    </div>
    @if ($hint)<div class="ui-hint">{{ $hint }}</div>@endif
    @error($name)<div class="err show" id="{{ $name }}-error">{{ $message }}</div>@enderror
</div>