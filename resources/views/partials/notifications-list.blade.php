@forelse ($notifications as $notification)
    @php
        $level = data_get($notification->data, 'level', 'info');
        $icon = data_get($notification->data, 'icon', 'notifications');
    @endphp
    <a href="{{ route('notifications.open', $notification->id) }}"
       class="notif-row {{ $notification->read_at === null ? 'is-unread' : '' }}">
        <span class="ms notif-row-ic notif-{{ $level }}">{{ $icon }}</span>
        <span class="notif-row-tx">
            <span class="notif-row-title">{{ data_get($notification->data, 'title', 'Notifikasi') }}</span>
            <span class="notif-row-body">{{ data_get($notification->data, 'body') }}</span>
            <span class="notif-row-meta">{{ $notification->created_at->diffForHumans() }}</span>
        </span>
    </a>
@empty
    <div class="notif-empty">{{ $emptyMessage ?? 'Tidak ada notifikasi baru.' }}</div>
@endforelse
