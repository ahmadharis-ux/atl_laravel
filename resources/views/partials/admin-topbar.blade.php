@php
    $user = $user ?? ['name' => 'Super Admin', 'role' => 'Administrator'];
    $dateLong = $dateLong ?? now()->locale('id')->translatedFormat('l, d F Y');
@endphp

{{-- Header desktop --}}
<header class="topbar d-none d-lg-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3">
        <button class="sq js-sidebar-toggle" type="button" aria-label="Perluas / perkecil sidebar" title="Perluas / perkecil sidebar">
            <span class="ms ico-expanded">menu_open</span>
            <span class="ms ico-collapsed">menu</span>
        </button>
        <div>
            <div class="d-flex align-items-center gap-2">
                <h1 class="fs-5 fw-semibold m-0">@yield('page-title', 'Dashboard')</h1>
                <span class="pill bg-tealc tx-tealt fs-11">Admin</span>
            </div>
            <span class="fs-11 fw-bold tx-mute">{{ $dateLong }}</span>
        </div>
    </div>
    <div class="d-flex align-items-center gap-3">
        <div class="d-flex align-items-center bg-white r-sm px-3" style="width:16rem;height:2.5rem">
            <span class="ms ms-md tx-mute me-2">search</span>
            <input class="border-0 bg-transparent w-100 fs-12" style="outline:0" placeholder="Cari pengguna, transaksi...">
        </div>
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
    <div class="ibox bg-navy text-white fw-bold fs-5 rounded-circle" style="width:3rem;height:3rem"><span class="ms">shield_person</span></div>
    <div class="flex-fill text-truncate">
        <div class="fs-12 fw-semibold tx-mute text-truncate">{{ $dateLong }}</div>
        <div class="fw-bold fs-5 lh-sm text-truncate">@yield('page-title', 'Dashboard')</div>
    </div>
    <button class="sq rounded-circle shadow-sm" style="width:2.75rem;height:2.75rem" aria-label="Notifikasi">
        <span class="ms">notifications</span>
        <span class="dot bg-mintt" style="top:.65rem;right:.8rem"></span>
    </button>
    <button class="sq rounded-circle shadow-sm js-sidebar-mobile-toggle" style="width:2.75rem;height:2.75rem"
        type="button" aria-label="Buka menu navigasi" aria-controls="adminSidebar" aria-expanded="false">
        <span class="ms">menu</span>
    </button>
</header>