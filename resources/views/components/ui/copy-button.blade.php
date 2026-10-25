@props([
    'text' => '',
    'label' => 'Salin',
    'class' => '',
])

<button type="button" {{ $attributes->merge(['class' => 'btn-icon '.$class]) }} data-copy-text="{{ $text }}" data-tip="{{ $label }}">
    <span class="ms ms-sm">content_copy</span>
</button>
