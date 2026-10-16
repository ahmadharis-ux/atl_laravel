@php
    $auth = auth()->user();
    $menu = [
        ['label' => 'Dashboard',         'icon' => 'space_dashboard',    'url' => route('admin.dashboard'),   'active' => request()->routeIs('admin.dashboard'), 'show' => true],
        [
            'label' => 'Customer Topup',
            'icon' => 'point_of_sale',
            'active' => request()->routeIs('admin.topup.*') || request()->routeIs('admin.product.*'),
            'show' => ($auth?->can('topups.view') || $auth?->can('products.view')) ?? false,
            'children' => [
                ['label' => 'Topup',   'url' => route('admin.topup.index'),   'active' => request()->routeIs('admin.topup.*'),   'show' => $auth?->can('topups.view') ?? false],
                ['label' => 'Product', 'url' => route('admin.product.index'), 'active' => request()->routeIs('admin.product.*'), 'show' => $auth?->can('products.view') ?? false],
            ],
        ],
        ['label' => 'Pengguna',          'icon' => 'group',              'url' => route('admin.users.index'), 'active' => request()->routeIs('admin.users.*'),   'show' => $auth?->can('users.view') ?? false],
        ['label' => 'Role & Permission', 'icon' => 'admin_panel_settings','url' => route('admin.roles.index'), 'active' => request()->routeIs('admin.roles.*'),   'show' => $auth?->can('roles.view') ?? false],
        [
            'label' => 'Bot Digiflazz',
            'icon' => 'smart_toy',
            'active' => request()->routeIs('admin.bot.digiflazz.*'),
            'show' => $auth?->can('products.view') ?? false,
            'children' => [
                ['label' => 'Konfigurasi',      'url' => route('admin.bot.digiflazz.index'),      'active' => request()->routeIs('admin.bot.digiflazz.index'),      'show' => $auth?->can('products.view') ?? false],
                ['label' => 'Sinkronisasi',     'url' => route('admin.bot.digiflazz.sync.index'), 'active' => request()->routeIs('admin.bot.digiflazz.sync.index'), 'show' => $auth?->can('products.view') ?? false],
            ],
        ],
        [
            'label' => 'Produk',
            'icon' => 'inventory_2',
            'active' => request()->routeIs('admin.products.*'),
            'show' => $auth?->can('products.view') ?? false,
            'children' => [
                ['label' => 'Prabayar',       'url' => route('admin.products.prepay.index'),       'active' => request()->routeIs('admin.products.prepay.*'),       'show' => $auth?->can('products.view') ?? false],
                ['label' => 'Pascabayar',     'url' => route('admin.products.postpaid.index'),     'active' => request()->routeIs('admin.products.postpaid.*'),     'show' => $auth?->can('products.view') ?? false],
                ['label' => 'Sinkron Digiflazz','url' => route('admin.products.sync-digiflazz.index'), 'active' => request()->routeIs('admin.products.sync-digiflazz.*'), 'show' => $auth?->can('products.view') ?? false],
            ],
        ],
        [
            'label' => 'Transaksi',
            'icon' => 'receipt_long',
            'active' => request()->routeIs('admin.transactions.*'),
            'show' => $auth?->can('transactions.view') ?? false,
            'children' => [
                ['label' => 'Semua Transaksi', 'url' => route('admin.transactions.index'), 'active' => request()->routeIs('admin.transactions.*'), 'show' => $auth?->can('transactions.view') ?? false],
            ],
        ],
        ['label' => 'Laporan',           'icon' => 'bar_chart',          'url' => '#',                        'active' => false,                                'show' => true],
        ['label' => 'Komponen',          'icon' => 'widgets',            'url' => route('admin.components'),  'active' => request()->routeIs('admin.components'), 'show' => true],
        ['label' => 'Master Brand',      'icon' => 'branding_watermark',  'url' => route('admin.master-brands.index'), 'active' => request()->routeIs('admin.master-brands.*'), 'show' => $auth?->can('master-brands.view') ?? false],
        ['label' => 'Master Bank',       'icon' => 'account_balance',     'url' => route('admin.master-banks.index'), 'active' => request()->routeIs('admin.master-banks.*'), 'show' => $auth?->can('master-banks.view') ?? false],
    ];
@endphp

<aside class="sidebar d-flex flex-column justify-content-between" id="adminSidebar" data-admin-sidebar>
    <div class="d-flex flex-column gap-4">
        <div class="side-head d-flex align-items-center justify-content-between gap-2 px-3 py-2">
            <div class="d-flex align-items-center gap-2 brand">
                <div class="ibox bg-tealc tx-teal" style="width:2.25rem;height:2.25rem;border-radius:.75rem"><span class="ms">shield_person</span></div>
                <div class="d-flex flex-column brand-text">
                    <span class="fw-bold text-white lh-1">Atlantic</span>
                    <span class="fs-11 fw-bold tx-mint text-uppercase mt-1" style="letter-spacing:.1em">Admin</span>
                </div>
            </div>
            <button class="btn btn-sm text-white border-0 d-none d-lg-flex align-items-center justify-content-center js-sidebar-toggle" type="button" aria-label="Perluas / perkecil sidebar" title="Perluas / perkecil sidebar" style="cursor:pointer;width:2rem;height:2rem;background:rgba(255,255,255,.1);border-radius:.5rem">
                <span class="ms ms-sm ico-expanded">menu_open</span>
                <span class="ms ms-sm ico-collapsed">menu</span>
            </button>
        </div>
        <nav class="d-flex flex-column gap-1">
            @foreach ($menu as $item)
                @if ($item['show'])
                    @if (! empty($item['children']))
                        @php($open = collect($item['children'])->contains(fn ($c) => $c['active']))
                        <div class="side-group {{ $open ? 'open' : '' }}">
                            <button type="button" class="side-link side-parent {{ $item['active'] ? 'active' : '' }}" data-side-toggle title="{{ $item['label'] }}" aria-expanded="{{ $open ? 'true' : 'false' }}">
                                <span class="ms">{{ $item['icon'] }}</span>
                                <span class="side-label">{{ $item['label'] }}</span>
                                <span class="ms ms-sm side-caret">expand_more</span>
                            </button>
                            <div class="side-sub">
                                @foreach ($item['children'] as $child)
                                    @if ($child['show'])
                                        <a href="{{ $child['url'] }}" class="side-sub-link mt-2 ms-0 {{ $child['active'] ? 'active' : '' }}" @if($child['active']) aria-current="page" @endif>{{ $child['label'] }}</a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a href="{{ $item['url'] }}" class="side-link {{ $item['active'] ? 'active' : '' }}" @if($item['active']) aria-current="page" @endif title="{{ $item['label'] }}">
                            <span class="ms">{{ $item['icon'] }}</span><span class="side-label">{{ $item['label'] }}</span>
                        </a>
                    @endif
                @endif
            @endforeach
        </nav>
    </div>

    <div class="d-flex align-items-center gap-3 p-2 r-sm bg-glass">
        <div class="ibox bg-mint tx-mintd fw-bold rounded-circle" style="width:2.25rem;height:2.25rem">{{ strtoupper(substr($auth?->name ?? 'A', 0, 1)) }}</div>
        <div class="d-flex flex-column flex-fill text-truncate user-info">
            <span class="fs-12 fw-semibold text-white text-truncate">{{ $auth?->name ?? 'Admin' }}</span>
            <span class="fs-11 fw-bold tx-tealc text-truncate">{{ $auth?->roleLabel() ?? 'Administrator' }}</span>
        </div>
        <span class="ms ms-md tx-skyd">verified</span>
    </div>
</aside>