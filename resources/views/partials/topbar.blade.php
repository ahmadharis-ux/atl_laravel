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
        <button class="sq rounded-circle shadow-sm flex-shrink-0" style="width:2.75rem;height:2.75rem" aria-label="Notifikasi">
            <span class="ms">notifications</span>
            <span class="dot bg-mintt" style="top:.65rem;right:.8rem"></span>
        </button>
    </div>
</header>
