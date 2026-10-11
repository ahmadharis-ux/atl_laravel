@props([
    'name',
    'label' => null,
    'options' => [],
    'value' => null,
    'placeholder' => 'Pilih...',
    'hint' => null,
    'remote' => false,
    'remoteUrl' => null,
    'selectedLabel' => null,
    'minChars' => 1,
    'dependsOn' => null,
])

@php($selected = $value ?? old($name))

<div {{ $attributes->only('class') }}>
    @if ($label)<label class="ui-label" for="{{ $name }}">{{ $label }}</label>@endif
    <select
        {{ $attributes->except('class')->merge(['class' => 'ui-select js-search-select']) }}
        id="{{ $name }}"
        name="{{ $name }}"
        data-placeholder="{{ $placeholder }}"
        @if ($remote)
            data-remote-url="{{ $remoteUrl }}"
            data-min-chars="{{ $minChars }}"
            data-hint-limit="5"
            @if ($dependsOn) data-depends-on="{{ $dependsOn }}" @endif
        @endif
    >
        <option value="">{{ $placeholder }}</option>
        @if ($remote)
            @if ($selected !== null && $selected !== '')
                <option value="{{ $selected }}" selected>{{ $selectedLabel ?? $selected }}</option>
            @endif
        @else
            @foreach ($options as $val => $text)
                <option value="{{ $val }}" @selected(! is_null($selected) && (string) $selected === (string) $val)>{{ $text }}</option>
            @endforeach
        @endif
    </select>
    @if ($hint)<div class="ui-hint">{{ $hint }}</div>@endif
    
</div>