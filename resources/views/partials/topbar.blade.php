{{-- Header desktop --}}
<header class="topbar d-none d-lg-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3">
        <button class="sq js-sidebar-toggle" type="button" aria-label="Perluas / perkecil sidebar" title="Perluas / perkecil sidebar">
            <span class="ms ico-expanded">menu_open</span>
            <span class="ms ico-collapsed">menu</span>
        </button>
        <div>
            <div class="d-flex align-items-center gap-2">
                <h1 class="fs-5 fw-semibold m-0">Halo, {{ $user['name'] }}</h1>
                <span class="pill bg-tealc tx-tealt fs-11">Online</span>
            </div>
            <span class="fs-11 fw-bold tx-mute">{{ $dateLong }}</span>
        </div>
    </div>
    <div class="d-flex align-items-center gap-3">
        <button class="sq" aria-label="Notifikasi">
            <span class="ms">notifications</span>
            <span class="dot" style="top:.5rem;right:.5rem;background:var(--err)"></span>
        </button>
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
<header class="d-flex d-lg-none align-items-center gap-3 px-3 pt-3 pb-2">
    <div class="ibox bg-navy text-white fw-bold fs-5 rounded-circle" style="width:3rem;height:3rem">{{ strtoupper(substr($user['name'], 0, 1)) }}</div>
    <div class="flex-fill text-truncate">
        <div class="fs-12 fw-semibold tx-mute text-truncate">{{ $dateShort }}</div>
        <div class="fw-bold fs-5 lh-sm text-truncate">Halo, {{ $user['name'] }} 👋</div>
    </div>
    <button class="sq rounded-circle shadow-sm" style="width:2.75rem;height:2.75rem" aria-label="Notifikasi">
        <span class="ms">notifications</span>
        <span class="dot bg-mintt" style="top:.65rem;right:.8rem"></span>
    </button>
</header>
