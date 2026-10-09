@props(['actions' => [], 'threshold' => 3])

@php
    $many = count($actions) > $threshold;

    $renderItem = function (array $a, bool $withText) {
        $icon = $a['icon'] ?? 'chevron_right';
        $label = $a['label'] ?? '';
        $tone = $a['tone'] ?? 'tx-tealt';
        $href = $a['href'] ?? null;
        $confirm = $a['confirm'] ?? null;
        $action = $a['action'] ?? null;

        $base = $withText ? 'ap-menu-item ' . $tone : 'ap-icon-btn ' . $tone;
        $attrs = '';
        if ($href) {
            $attrs .= ' href="' . e($href) . '"';
        }
        if ($confirm) {
            $attrs .= ' data-confirm="' . e($confirm) . '"';
        }
        if (! empty($a['confirm_danger'])) {
            $attrs .= ' data-confirm-danger';
        }
        if (! empty($a['form'])) {
            $attrs .= ' data-submit-form="' . e($a['form']) . '"';
        }
        if ($action) {
            $attrs .= ' data-action="' . e($action) . '"';
        }
        if (! $href && ! $confirm && ! $action) {
            $attrs .= ' disabled';
        }
        $tip = $withText ? '' : ' data-tip="' . e($label) . '"';
        $inner = '<span class="ms ms-md">' . e($icon) . '</span>' . ($withText ? '<span>' . e($label) . '</span>' : '');

        return $href
            ? '<a class="' . $base . '"' . $attrs . $tip . '>' . $inner . '</a>'
            : '<button type="button" class="' . $base . '"' . $attrs . $tip . '>' . $inner . '</button>';
    };
@endphp

@if ($many)
    <div {{ $attributes->merge(['class' => 'ap-actions d-inline-block']) }} data-actions>
        <button type="button" class="ap-icon-btn" data-actions-toggle data-tip="Aksi lain" data-tip-pos="left" aria-haspopup="true" aria-expanded="false">
            <span class="ms ms-md">more_vert</span>
        </button>
        <template class="ap-actions-template">
            @foreach ($actions as $a)
                {!! $renderItem($a, true) !!}
            @endforeach
        </template>
    </div>
@else
    <div {{ $attributes->merge(['class' => 'ap-actions d-inline-flex gap-1']) }}>
        @foreach ($actions as $a)
            {!! $renderItem($a, false) !!}
        @endforeach
    </div>
@endif