@props(['name' => null, 'value' => '1', 'label', 'checked' => false, 'id' => null])

@php
    $inputId = $id ?? ($name ? \Illuminate\Support\Str::slug(str_replace(['[', ']', '_'], '-', $name)).'-'.preg_replace('/[^a-z0-9]+/i', '-', $value) : null);
    $disabled = filter_var($attributes->get('disabled', false), FILTER_VALIDATE_BOOLEAN);
@endphp

<label @class(['check-card d-flex align-items-center gap-2', 'opacity-50' => $disabled])>
    <input
        {{ $attributes->except('disabled')->merge(['class' => 'form-check-input mt-0']) }}
        @if($inputId) id="{{ $inputId }}" @endif
        type="checkbox"
        @if($name) name="{{ $name }}" @endif
        value="{{ $value }}"
        @checked($checked)
        @disabled($disabled)
    >
    <span>{{ $label }}</span>
</label>