@php
    $auth = auth()->user();
    $menu = [
        ['label' => 'Dashboard',         'icon' => 'space_dashboard',    'url' => route('admin.dashboard'),   'active' => request()->routeIs('admin.dashboard'), 'show' => true],
        [
            'label' => 'Landing Page',
            'icon' => 'web',
            'active' => request()->routeIs('admin.landing.*'),
            'show' => $auth?->can('landing.view') ?? false,
            'children' => [
                ['label' => 'Pengaturan', 'url' => route('admin.landing.settings'),     'active' => request()->routeIs('admin.landing.settings'),     'show' => $auth?->can('landing.view') ?? false],
                ['label' => 'Layanan',    'url' => route('admin.landing.services'),     'active' => request()->routeIs('admin.landing.services'),     'show' => $auth?->can('landing.view') ?? false],
                ['label' => 'Testimoni',  'url' => route('admin.landing.testimonials'), 'active' => request()->routeIs('admin.landing.testimonials'), 'show' => $auth?->can('landing.view') ?? false],
                ['label' => 'FAQ',        'url' => route('admin.landing.faqs'),         'active' => request()->routeIs('admin.landing.faqs'),         'show' => $auth?->can('landing.view') ?? false],
            ],
        ],
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
        ['label' => 'Tiket',              'icon' => 'support_agent',      'url' => route('admin.tickets.index'), 'active' => request()->routeIs('admin.tickets.*'), 'show' => $auth?->can('tickets.view') ?? false],
        [
            'label' => 'Laporan',
            'icon' => 'bar_chart',
            'active' => request()->routeIs('admin.reports.*') || request()->routeIs('admin.resellers.*'),
            'show' => ($auth?->can('reports.view') || $auth?->can('resellers.view')) ?? false,
            'children' => [
                ['label' => 'Ringkasan',  'url' => route('admin.reports.overview'),  'active' => request()->routeIs('admin.reports.overview'),  'show' => $auth?->can('reports.view') ?? false],
                ['label' => 'Produk',     'url' => route('admin.reports.products'),  'active' => request()->routeIs('admin.reports.products'),  'show' => $auth?->can('reports.view') ?? false],
                ['label' => 'Provider',   'url' => route('admin.reports.providers'), 'active' => request()->routeIs('admin.reports.providers'), 'show' => $auth?->can('reports.view') ?? false],
                ['label' => 'Pelanggan',  'url' => route('admin.reports.customers'), 'active' => request()->routeIs('admin.reports.customers'), 'show' => $auth?->can('reports.view') ?? false],
                ['label' => 'Reseller',   'url' => route('admin.resellers.index'),   'active' => request()->routeIs('admin.resellers.*'),      'show' => $auth?->can('resellers.view') ?? false],
            ],
        ],
        ['label' => 'Komponen',          'icon' => 'widgets',            'url' => route('admin.components'),  'active' => request()->routeIs('admin.components'), 'show' => true],
        ['label' => 'Master Brand',      'icon' => 'branding_watermark',  'url' => route('admin.master-brands.index'), 'active' => request()->routeIs('admin.master-brands.*'), 'show' => $auth?->can('master-brands.view') ?? false],
        ['label' => 'Master Bank',       'icon' => 'account_balance',     'url' => route('admin.master-banks.index'), 'active' => request()->routeIs('admin.master-banks.*'), 'show' => $auth?->can('master-banks.view') ?? false],
        ['label' => 'Banner Login',      'icon' => 'campaign',            'url' => route('admin.login-banners.index'), 'active' => request()->routeIs('admin.login-banners.*'), 'show' => $auth?->can('login-banners.view') ?? false],
        [
            'label' => 'Merchandise',
            'icon' => 'redeem',
            'active' => request()->routeIs('admin.merchandise.*') || request()->routeIs('admin.redemptions.*') || request()->routeIs('admin.loyalty-settings.*'),
            'show' => ($auth?->can('merchandise.view') || $auth?->can('redemptions.view') || $auth?->can('loyalty.view')) ?? false,
            'children' => [
                ['label' => 'Katalog',      'url' => route('admin.merchandise.index'),  'active' => request()->routeIs('admin.merchandise.*'),  'show' => $auth?->can('merchandise.view') ?? false],
                ['label' => 'Penukaran',    'url' => route('admin.redemptions.index'),  'active' => request()->routeIs('admin.redemptions.*'),  'show' => $auth?->can('redemptions.view') ?? false],
                ['label' => 'Pengaturan Tier', 'url' => route('admin.loyalty-settings.index'), 'active' => request()->routeIs('admin.loyalty-settings.*'), 'show' => $auth?->can('loyalty.view') ?? false],
            ],
        ],
        ['label' => 'Banner Dashboard',  'icon' => 'view_carousel',       'url' => route('admin.dashboard-banners.index'), 'active' => request()->routeIs('admin.dashboard-banners.*'), 'show' => $auth?->can('dashboard-banners.view') ?? false],
    ];
@endphp

<aside class="sidebar d-flex flex-column justify-content-between" id="adminSidebar" data-admin-sidebar>
    <div class="d-flex flex-column gap-4">
        <div class="side-head d-flex align-items-center justify-content-between gap-2 px-3 py-2">
            <div class="d-flex align-items-center gap-2 brand">
                <img src="{{ asset('assets/images/logo_with_right_text.png') }}" alt="Atlantic Pedia" class="brand-logo" style="width:7.5rem;height:3.25rem;border-radius:.75rem;object-fit:cover">
                <img src="{{ asset('') }}" alt="Atlantic Pedia" class="brand-logo-sm" style="width:2.25rem;height:2.25rem;border-radius:.75rem;object-fit:cover">
            </div>
            {{-- <button class="btn btn-sm text-white border-0 d-none d-lg-flex align-items-center justify-content-center js-sidebar-toggle" type="button" aria-label="Perluas / perkecil sidebar" title="Perluas / perkecil sidebar" style="cursor:pointer;width:2rem;height:2rem;background:rgba(255,255,255,.1);border-radius:.5rem">
                <span class="ms ms-sm ico-expanded">menu_open</span>
                <span class="ms ms-sm ico-collapsed">menu</span>
            </button> --}}
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
        <form action="{{ route('logout') }}" method="POST" class="d-flex m-0">
            @csrf
            <button type="submit" class="side-logout" aria-label="Keluar" title="Keluar">
                <span class="ms ms-md">logout</span>
            </button>
        </form>
    </div>
</aside>