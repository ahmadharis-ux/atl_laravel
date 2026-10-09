@props(['name', 'value', 'label', 'checked' => false])

<label class="radio-card d-flex align-items-center gap-2">
    <input class="form-check-input mt-0" type="radio" name="{{ $name }}" value="{{ $value }}" @checked($checked)>
    <span>{{ $label }}</span>
</label>