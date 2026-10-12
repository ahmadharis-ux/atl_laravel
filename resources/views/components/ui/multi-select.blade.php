@props([
    'name',
    'label' => null,
    'value' => [],
    'options' => [],
    'placeholder' => 'Pilih...',
    'hint' => null,
    'remoteUrl' => null,
])

@php
    $selected = old($name, $value) ?? [];
    $selected = is_array($selected) ? $selected : [$selected];
    $selectedStrings = array_map('strval', $selected);

    // Gabungkan opsi terpilih (label tersimpan) dengan opsi yang diberikan.
    $rendered = $options;
    foreach ($selectedStrings as $sid) {
        if (! array_key_exists($sid, $rendered)) {
            $rendered[$sid] = $sid;
        }
    }
@endphp

<div {{ $attributes->only('class') }}>
    @if ($label)<label class="ui-label" for="{{ $name }}">{{ $label }}</label>@endif
    <select
        {{ $attributes->except('class')->merge(['class' => 'ui-select js-multi-select']) }}
        id="{{ $name }}"
        name="{{ $name }}[]"
        multiple
        data-placeholder="{{ $placeholder }}"
        @if ($remoteUrl) data-remote-url="{{ $remoteUrl }}" @endif
    >
        @foreach ($rendered as $val => $text)
            <option value="{{ $val }}" @selected(in_array((string) $val, $selectedStrings, true))>{{ $text }}</option>
        @endforeach
    </select>
    @if ($hint)<div class="ui-hint">{{ $hint }}</div>@endif
    @error($name)<div class="err show">{{ $message }}</div>@enderror
</div>
