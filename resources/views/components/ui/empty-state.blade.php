@props([
    'icon' => 'search_off',
    'title' => 'Tidak ada data',
    'description' => null,
    'tone' => 'bg-tealc-m tx-tealt',
])

<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    <span class="empty-state-ic {{ $tone }}"><span class="ms">{{ $icon }}</span></span>
    <p class="empty-state-title m-0">{{ $title }}</p>
    @if ($description)
        <p class="empty-state-desc">{{ $description }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="empty-state-actions">{{ $slot }}</div>
    @endif
</div>