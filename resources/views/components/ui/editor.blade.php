@props(['id' => 'editor', 'name' => null, 'value' => null])

<div {{ $attributes->only('class') }}>
    <div id="{{ $id }}" class="js-editor" data-name="{{ $name }}">{!! $value !!}</div>
    @if ($name)<input type="hidden" name="{{ $name }}" class="js-editor-input">@endif
</div>