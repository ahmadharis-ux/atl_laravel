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

    $unreadNotifications = auth()->user()?->unreadNotifications()->count() ?? 0;
@endphp

{{-- Header desktop --}}
<header class="topbar d-none d-lg-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3 min-w-0">
        <button class="sq js-sidebar-toggle flex-shrink-0" type="button" aria-label="Perluas / perkecil sidebar" title="Perluas / perkecil sidebar">
            <span class="ms ico-expanded">menu_open</span>
            <span class="ms ico-collapsed">menu</span>
        </button>
        @hasSection('breadcrumb')
            @yield('breadcrumb')
        @else
            <x-ui.breadcrumb :items="$pageTitle === 'Beranda'
                ? [['label' => 'Beranda']]
                : [['label' => 'Beranda', 'url' => route('dashboard')], ['label' => $pageTitle]]" />
        @endif
    </div>
    <div class="d-flex align-items-center gap-3 flex-shrink-0">
        <div class="notif" data-notif data-notif-url="{{ route('notifications.index') }}" data-notif-interval="15000" data-notif-user="{{ auth()->id() }}">
            <button class="sq" type="button" aria-label="Notifikasi" data-notif-toggle>
                <span class="ms">notifications</span>
                @if ($unreadNotifications > 0)
                    <span class="pill text-white" data-notif-dot style="position:absolute;top:-.25rem;right:-.25rem;min-width:1.25rem;height:1.25rem;font-size:.6875rem;padding:.125rem .25rem;border-radius:999px;line-height:1;display:flex;align-items:center;justify-content:center;background:var(--err)">
                        {{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}
                    </span>
                @endif
            </button>
            <div class="notif-pop" data-notif-pop hidden>
                <div class="notif-head">
                    <span class="fw-bold">Notifikasi</span>
                    <form method="POST" action="{{ route('notifications.read-all') }}" class="m-0">
                        @csrf
                        <button type="submit" class="notif-link">Tandai semua dibaca</button>
                    </form>
                </div>
                <div class="notif-list" data-notif-list>
                    <div class="notif-empty">Memuat…</div>
                </div>
                <a href="{{ route('notifications.index') }}" class="notif-foot">Semua notifikasi</a>
            </div>
        </div>
        <form action="{{ route('logout') }}" method="POST" class="d-flex m-0">
            @csrf
            <button type="submit" class="sq" aria-label="Keluar" title="Keluar">
                <span class="ms">logout</span>
            </button>
        </form>
        <a href="{{ route('settings.index') }}" class="ibox bg-deep rounded-circle" style="width:2rem;height:2rem" aria-label="Pengaturan akun" title="Pengaturan"><span class="ms ms-md text-white">person</span></a>
    </div>
</header>

{{-- Header mobile --}}
<header class="d-flex d-lg-none align-items-center gap-3 px-3 pt-3 pb-2">
    <a href="{{ route('settings.index') }}" class="ibox bg-deep rounded-circle" style="width:2rem;height:2rem" aria-label="Pengaturan akun" title="Pengaturan"><span class="ms ms-md text-white">person</span></a>
    <div class="d-flex align-items-center gap-3 min-w-0">
        @hasSection('breadcrumb')
            @yield('breadcrumb')
        @else
            <x-ui.breadcrumb :items="$pageTitle === 'Beranda'
                ? [['label' => 'Beranda']]
                : [['label' => 'Beranda', 'url' => route('dashboard')], ['label' => $pageTitle]]" />
        @endif
    </div>
    <div class="d-flex align-items-center gap-3 flex-shrink-0 ms-auto">
        <a href="{{ route('notifications.index') }}" class="sq rounded-circle shadow-sm flex-shrink-0 position-relative" style="width:2.75rem;height:2.75rem" aria-label="Notifikasi">
            <span class="ms">notifications</span>
            @if ($unreadNotifications > 0)
                <span class="pill text-white" data-notif-dot style="position:absolute;top:-.25rem;right:-.25rem;min-width:1.25rem;height:1.25rem;font-size:.6875rem;padding:.125rem .25rem;border-radius:999px;line-height:1;display:flex;align-items:center;justify-content:center;background:var(--err)">
                    {{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}
                </span>
            @endif
        </a>
        <form action="{{ route('logout') }}" method="POST" class="d-flex m-0">
            @csrf
            <button type="submit" class="sq rounded-circle shadow-sm flex-shrink-0" style="width:2.75rem;height:2.75rem" aria-label="Keluar" title="Keluar">
                <span class="ms">logout</span>
            </button>
        </form>
    </div>
</header>
