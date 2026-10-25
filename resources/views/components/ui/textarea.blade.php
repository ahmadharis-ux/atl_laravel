@props(['name', 'label' => null, 'rows' => 3, 'value' => null, 'hint' => null])
@php($hasError = $errors->has($name))

<div {{ $attributes->only('class') }}>
    @if ($label)<label class="ui-label" for="{{ $name }}">{{ $label }}</label>@endif
    <textarea
        {{ $attributes->except('class')->merge(['class' => 'ui-textarea']) }}
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
    >{{ $value ?? old($name) }}</textarea>
    @if ($hint)<div class="ui-hint">{{ $hint }}</div>@endif
    @error($name)<div class="err show" id="{{ $name }}-error">{{ $message }}</div>@enderror
</div>