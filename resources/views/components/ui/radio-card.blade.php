@props(['name', 'value', 'label', 'checked' => false])
@php($hasError = $errors->has($name))

<label class="radio-card d-flex align-items-center gap-2">
    <input class="form-check-input mt-0" type="radio" name="{{ $name }}" value="{{ $value }}"
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif @checked($checked)>
    <span>{{ $label }}</span>
</label>