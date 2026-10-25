@props(['name', 'label' => null, 'options' => [], 'value' => null, 'placeholder' => null, 'hint' => null, 'id' => null])

@php($selected = $value ?? old($name))
@php($selectId = $id ?? $name)
@php($hasError = $errors->has($name))

<div {{ $attributes->only('class') }}>
    @if ($label)<label class="ui-label" for="{{ $selectId }}">{{ $label }}</label>@endif
    <select
        {{ $attributes->except(['class', 'id'])->merge(['class' => 'ui-select']) }}
        id="{{ $selectId }}"
        name="{{ $name }}"
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
    >
        @if ($placeholder)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($options as $val => $text)
            <option value="{{ $val }}" @selected(! is_null($selected) && (string) $selected === (string) $val)>{{ $text }}</option>
        @endforeach
    </select>
    @if ($hint)<div class="ui-hint">{{ $hint }}</div>@endif
    @error($name)<div class="err show" id="{{ $name }}-error">{{ $message }}</div>@enderror
</div>