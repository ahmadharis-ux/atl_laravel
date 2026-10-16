@props(['name', 'label' => null, 'options' => [], 'value' => null, 'placeholder' => null, 'hint' => null, 'id' => null])

@php($selected = $value ?? old($name))
@php($selectId = $id ?? $name)

<div {{ $attributes->only('class') }}>
    @if ($label)<label class="ui-label" for="{{ $selectId }}">{{ $label }}</label>@endif
    <select
        {{ $attributes->except(['class', 'id'])->merge(['class' => 'ui-select']) }}
        id="{{ $selectId }}"
        name="{{ $name }}"
    >
        @if ($placeholder)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($options as $val => $text)
            <option value="{{ $val }}" @selected(! is_null($selected) && (string) $selected === (string) $val)>{{ $text }}</option>
        @endforeach
    </select>
    @if ($hint)<div class="ui-hint">{{ $hint }}</div>@endif
    @error($name)<div class="err show">{{ $message }}</div>@enderror
</div>