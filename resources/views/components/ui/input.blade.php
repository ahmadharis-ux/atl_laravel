@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'hint' => null])

<div {{ $attributes->only('class') }}>
    @if ($label)<label class="ui-label" for="{{ $name }}">{{ $label }}</label>@endif
    <input
        {{ $attributes->except('class')->merge(['class' => 'ui-input']) }}
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $value ?? old($name) }}"
    >
    @if ($hint)<div class="ui-hint">{{ $hint }}</div>@endif
    @error($name)<div class="err show">{{ $message }}</div>@enderror
</div>