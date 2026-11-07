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

    $tierUser = auth()->user();
    $tier = $tierUser?->tier();
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
        @if ($tier)
            <a href="{{ route('merchandise.index') }}" class="pill tier-{{ $tier->value }} tier-badge fs-11 flex-shrink-0 text-decoration-none" data-tip="{{ number_format((int) $tierUser->points, 0, ',', '.') }} poin reward">
                <img src="{{ asset('assets/images/'.$tier->value.'.png') }}" class="tier-badge-image" alt="{{ $tier->label() }}">
            </a>
        @endif
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
        <div class="user-menu" data-user-menu>
            <button class="ibox bg-deep rounded-circle" style="width:2rem;height:2rem" type="button"
                aria-label="Menu pengguna" title="Menu pengguna" aria-expanded="false"
                aria-controls="userMenuDesktop" data-user-menu-toggle>
                <span class="ms ms-md text-white">person</span>
            </button>
            <div class="user-menu-pop" id="userMenuDesktop" hidden data-user-menu-pop>
                <a href="{{ route('settings.index') }}" class="user-menu-item">
                    <span class="ms">settings</span>Pengaturan akun
                </a>
                <form action="{{ route('logout') }}" method="POST" class="user-menu-form">
                    @csrf
                    <button type="submit" class="user-menu-item">
                        <span class="ms">logout</span>Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

{{-- Header mobile --}}
<header class="d-flex d-lg-none align-items-center gap-3 px-3 pt-3 pb-2">
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
        @if ($tier)
            <a href="{{ route('saldo.index') }}" class="pill tier-{{ $tier->value }} tier-badge fs-11 flex-shrink-0 text-decoration-none" data-tip="{{ number_format((int) $tierUser->points, 0, ',', '.') }} poin reward">
                <img src="{{ asset('assets/images/'.$tier->value.'.png') }}" class="tier-badge-image" alt="{{ $tier->label() }}">
            </a>
        @endif
        <a href="{{ route('notifications.index') }}" class="sq rounded-circle shadow-sm flex-shrink-0 position-relative" style="width:2.75rem;height:2.75rem" aria-label="Notifikasi">
            <span class="ms">notifications</span>
            @if ($unreadNotifications > 0)
                <span class="pill text-white" data-notif-dot style="position:absolute;top:-.25rem;right:-.25rem;min-width:1.25rem;height:1.25rem;font-size:.6875rem;padding:.125rem .25rem;border-radius:999px;line-height:1;display:flex;align-items:center;justify-content:center;background:var(--err)">
                    {{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}
                </span>
            @endif
        </a>
        <div class="user-menu flex-shrink-0" data-user-menu>
            <button class="ibox bg-deep rounded-circle shadow-sm flex-shrink-0" style="width:2.75rem;height:2.75rem" type="button"
                aria-label="Menu pengguna" title="Menu pengguna" aria-expanded="false"
                aria-controls="userMenuMobile" data-user-menu-toggle>
                <span class="ms text-white">person</span>
            </button>
            <div class="user-menu-pop" id="userMenuMobile" hidden data-user-menu-pop>
                <a href="{{ route('settings.index') }}" class="user-menu-item">
                    <span class="ms">settings</span>Pengaturan akun
                </a>
                <form action="{{ route('logout') }}" method="POST" class="user-menu-form">
                    @csrf
                    <button type="submit" class="user-menu-item">
                        <span class="ms">logout</span>Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
