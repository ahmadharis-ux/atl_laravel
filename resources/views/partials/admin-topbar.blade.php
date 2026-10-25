@php
    use Illuminate\Support\Facades\View;

    $titleSection = trim((string) View::yieldContent('title', ''));
    $pageTitle = trim((string) View::yieldContent('page-title', ''));

    if ($pageTitle === '') {
        $pageTitle = $titleSection !== ''
            ? trim(preg_replace('/\s*[-\x{2013}]\s*Atlantic Pedia Tab\s*$/u', '', $titleSection))
            : 'Beranda';
    }

    if ($pageTitle === '') {
        $pageTitle = 'Beranda';
    }

    $unreadNotificationsAdmin = auth()->user()?->unreadNotifications()->count() ?? 0;

    $rp = auth()->user()?->is_admin ? 'admin.' : '';

@endphp
{{-- Header desktop --}}
<header class="topbar d-none d-lg-flex align-items-center justify-content-between">
    <button class="sq js-sidebar-toggle" type="button" aria-label="Perluas / perkecil sidebar" title="Perluas / perkecil sidebar">
        <span class="ms ico-expanded">menu_open</span>
        <span class="ms ico-collapsed">menu</span>
    </button>
    <div class="d-flex align-items-center gap-3">
        <div class="admin-search position-relative" data-admin-search data-search-url="{{ route('admin.search') }}">
            <div class="d-flex align-items-center bg-white r-sm px-3" style="width:16rem;height:2.5rem">
                <span class="ms ms-md tx-mute me-2">search</span>
                <input class="border-0 bg-transparent w-100 fs-12 admin-search-input" style="outline:0"
                    placeholder="Cari pengguna, transaksi, produk..." autocomplete="off" aria-label="Cari">
            </div>
            <div class="ui-search-pop" data-admin-search-pop hidden></div>
        </div>
         <div class="notif" data-notif data-notif-url="{{ route($rp . 'notifications.index') }}" data-notif-interval="15000" data-notif-user="{{ auth()->id() }}">
            <button class="sq" type="button" aria-label="Notifikasi" data-notif-toggle>
                <span class="ms">notifications</span>
                @if ($unreadNotificationsAdmin > 0)
                    <span class="pill text-white" data-notif-dot style="position:absolute;top:-.25rem;right:-.25rem;min-width:1.25rem;height:1.25rem;font-size:.6875rem;padding:.125rem .25rem;border-radius:999px;line-height:1;display:flex;align-items:center;justify-content:center;background:var(--err)">
                        {{ $unreadNotificationsAdmin > 99 ? '99+' : $unreadNotificationsAdmin }}
                    </span>
                @endif
            </button>
            <div class="notif-pop" data-notif-pop hidden>
                <div class="notif-head">
                    <span class="fw-bold">Notifikasi</span>
                    <form method="POST" action="{{ route($rp . 'notifications.read-all') }}" class="m-0">
                        @csrf
                        <button type="submit" class="notif-link">Tandai semua dibaca</button>
                    </form>
                </div>
                <div class="notif-list" data-notif-list>
                    <div class="notif-empty">Memuat…</div>
                </div>
                <a href="{{ route('admin.notifications.index') }}" class="notif-foot">Semua notifikasi</a>
            </div>
        </div>
        {{-- <button class="sq" aria-label="Notifikasi">
            <span class="ms">notifications</span>
            <span class="dot" style="top:.5rem;right:.5rem;background:var(--err)"></span>
        </button> --}}
        <form action="{{ route('logout') }}" method="POST" class="d-flex m-0">
            @csrf
            <button type="submit" class="sq" aria-label="Keluar" title="Keluar">
                <span class="ms">logout</span>
            </button>
        </form>
        <div class="ibox bg-deep rounded-circle" style="width:2rem;height:2rem"><span class="ms ms-md text-white">person</span></div>
    </div>
</header>

{{-- Header mobile --}}
<header class="d-flex d-lg-none align-items-center justify-content-between gap-3 px-3 pt-3 pb-2">
    <div class="d-flex align-items-center gap-2">
        <img src="{{ asset('assets/images/logo_with_right_text.png') }}" alt="Atlantic Pedia" style="height:2.25rem;width:auto;border-radius:.65rem">
    </div>
    <div class="d-flex align-items-center gap-2">
        <button class="sq rounded-circle shadow-sm" style="width:2.75rem;height:2.75rem" aria-label="Notifikasi">
            <span class="ms">notifications</span>
            <span class="dot bg-mintt" style="top:.65rem;right:.8rem"></span>
        </button>
        <button class="sq rounded-circle shadow-sm js-sidebar-mobile-toggle" style="width:2.75rem;height:2.75rem"
            type="button" aria-label="Buka menu navigasi" aria-controls="adminSidebar" aria-expanded="false">
            <span class="ms">menu</span>
        </button>
    </div>
</header>
