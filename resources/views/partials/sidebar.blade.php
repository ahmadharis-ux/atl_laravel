@php
    // Ganti '#' dengan route() saat halaman lain sudah dibuat.
    $menu = [
        ['label' => 'Beranda',    'icon' => 'grid_view',              'url' => route('dashboard'), 'active' => request()->routeIs('dashboard')],
        ['label' => 'Produk',     'icon' => 'storefront',            'url' => route('products.index'), 'active' => request()->routeIs('products.*')],
        ['label' => 'Riwayat',    'icon' => 'receipt_long',           'url' => '#', 'active' => false],
        ['label' => 'Kontak',     'icon' => 'contact_phone',          'url' => '#', 'active' => false],
        ['label' => 'Deposit',    'icon' => 'account_balance_wallet', 'url' => route('saldo.index'), 'active' => request()->routeIs('saldo.*')],
        ['label' => 'Pengaturan', 'icon' => 'settings',               'url' => '#', 'active' => false],
    ];
@endphp

<aside class="sidebar d-none d-lg-flex flex-column justify-content-between">
    <div class="d-flex flex-column gap-4">
        <div class="side-head d-flex align-items-center gap-2 px-3 py-2">
            <div class="d-flex align-items-center gap-2 brand">
                <img src="{{ asset('assets/images/9.png') }}" alt="Atlantic Pedia" class="brand-logo d-none d-lg-block" style="width:7.5rem;height:3.25rem;border-radius:.75rem;object-fit:cover">
                <img src="{{ asset('assets/images/8.png') }}" alt="Atlantic Pedia" class="brand-logo-sm d-none d-lg-block" style="width:2.25rem;height:2.25rem;border-radius:.75rem;object-fit:cover;display:none">
            </div>
        </div>
        <nav class="d-flex flex-column gap-1">
            @foreach ($menu as $item)
                <a href="{{ $item['url'] }}" class="side-link {{ $item['active'] ? 'active' : '' }}" @if($item['active']) aria-current="page" @endif title="{{ $item['label'] }}">
                    <span class="ms">{{ $item['icon'] }}</span><span class="side-label">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <div class="d-flex align-items-center gap-3 p-2 r-sm bg-glass">
        <div class="ibox bg-mint tx-mintd fw-bold rounded-circle" style="width:2.25rem;height:2.25rem">{{ strtoupper(substr($user['name'], 0, 1)) }}</div>
        <div class="d-flex flex-column flex-fill text-truncate user-info">
            <span class="fs-12 fw-semibold text-white text-truncate">{{ $user['name'] }}</span>
            <span class="fs-11 fw-bold tx-tealc text-truncate">{{ $user['role'] }}</span>
        </div>
        <span class="ms ms-md tx-skyd">verified</span>
    </div>
</aside>