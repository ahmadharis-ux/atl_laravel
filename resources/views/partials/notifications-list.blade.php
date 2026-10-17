@php
    $reorderProducts = $reorderProducts ?? [];
@endphp
@forelse ($notifications as $notification)
    @php
        $level = data_get($notification->data, 'level', 'info');
        $icon = data_get($notification->data, 'icon', 'notifications');
        $context = (array) data_get($notification->data, 'context', []);
        $reorderProductId = data_get($context, 'product_id');
        $canReorder = data_get($context, 'type') === 'order'
            && $reorderProductId
            && (($reorderProducts[$reorderProductId] ?? false) === true);
        $reorderQuery = array_filter([
            'target' => data_get($context, 'target'),
            'zone' => data_get($context, 'target_zone'),
        ], fn ($value) => $value !== null && $value !== '');
        $reorderUrl = $canReorder
            ? route('checkout.show', $reorderProductId).($reorderQuery !== [] ? '?'.http_build_query($reorderQuery) : '')
            : null;
    @endphp
    <div class="notif-row {{ $notification->read_at === null ? 'is-unread' : '' }}">
        <a href="{{ route('notifications.open', $notification->id) }}" class="notif-row-link">
            <span class="ms notif-row-ic notif-{{ $level }}">{{ $icon }}</span>
            <span class="notif-row-tx">
                <span class="notif-row-title">{{ data_get($notification->data, 'title', 'Notifikasi') }}</span>
                <span class="notif-row-body">{{ data_get($notification->data, 'body') }}</span>
                <span class="notif-row-meta">{{ $notification->created_at->diffForHumans() }}</span>
            </span>
        </a>
        @if ($reorderUrl)
            <a href="{{ $reorderUrl }}" class="notif-reorder" data-tip="Beli produk yang sama">
                <span class="ms ms-sm" aria-hidden="true">refresh</span>Beli Lagi
            </a>
        @endif
    </div>
@empty
    <div class="notif-empty">{{ $emptyMessage ?? 'Tidak ada notifikasi baru.' }}</div>
@endforelse
