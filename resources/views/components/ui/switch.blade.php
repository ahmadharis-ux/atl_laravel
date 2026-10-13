@props(['name', 'label' => null, 'checked' => false])

<div class="d-flex align-items-center gap-3">
    <div class="form-check form-switch m-0">
        <input class="form-check-input" type="checkbox" name="{{ $name }}" id="{{ $name }}" role="switch" value="1" @checked($checked)>
    </div>
    @if ($label)<label class="ui-label m-0" for="{{ $name }}">{{ $label }}</label>@endif
</div>