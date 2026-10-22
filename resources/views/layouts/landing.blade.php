<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Atlantic Pedia — PPOB & Top Up Digital')</title>
    <meta name="description" content="@yield('meta_description', 'Atlantic Pedia — layanan PPOB, pulsa, paket data, token listrik, dan produk digital dengan proses instan.')">

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('assets/css/atlantic.css') }}?v={{ filemtime(public_path('assets/css/atlantic.css')) }}" rel="stylesheet">
    <link href="{{ asset('assets/css/components.css') }}?v={{ filemtime(public_path('assets/css/components.css')) }}" rel="stylesheet">
    <link rel="icon" href="{{ asset('assets/images/logo.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/images/logo.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/images/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('assets/images/site.webmanifest') }}">
    @stack('styles')
</head>
<body class="landing-body">
    <header class="landing-nav">
        <div class="container d-flex align-items-center justify-content-between gap-3">
            <a href="{{ route('landing') }}" class="d-flex align-items-center gap-2" aria-label="Atlantic Pedia">
                <img src="{{ asset('assets/images/logo_with_right_text.png') }}" alt="Atlantic Pedia" style="height:2.75rem;width:auto;border-radius:.6rem">
            </a>

            <nav class="landing-nav-links d-none d-md-flex align-items-center gap-4" aria-label="Navigasi landing page">
                <a href="#layanan">Layanan</a>
                <a href="#tentang">Tentang</a>
                <a href="#testimoni">Testimoni</a>
                <a href="#faq">FAQ</a>
            </nav>

            <div class="d-flex align-items-center gap-2">
                @auth
                    <a href="{{ auth()->user()->canAccessAdmin() ? route('admin.dashboard') : route('dashboard') }}" class="btn-ap btn-navy btn-sm">Buka Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn-ap btn-ap-outline btn-sm d-none d-sm-inline-flex">Masuk</a>
                    <a href="{{ route('register') }}" class="btn-ap btn-navy btn-sm">Daftar</a>
                @endauth
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="landing-footer">
        <div class="container">
            <div class="row g-4 align-items-start">
                <div class="col-lg-5">
                    <img src="{{ asset('assets/images/logo_with_right_text.png') }}" alt="Atlantic Pedia" style="height:3rem;width:auto;border-radius:.6rem">
                    <p class="fs-13 mt-3 mb-2" style="max-width:26rem">Mitra transaksi digital untuk pulsa, paket data, token listrik, voucher game, dan kebutuhan PPOB lainnya.</p>
                    <p class="fs-12 mb-0">© {{ now()->year }} Atlantic Pedia. Semua hak dilindungi.</p>
                </div>
                <div class="col-6 col-lg-2">
                    <h2 class="landing-footer-title">Produk</h2>
                    <ul class="list-unstyled d-flex flex-column gap-2 fs-13 mb-0">
                        <li><a href="#layanan">Pulsa &amp; Data</a></li>
                        <li><a href="#layanan">Token Listrik</a></li>
                        <li><a href="#layanan">Voucher Game</a></li>
                        <li><a href="#layanan">Deposit Saldo</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-2">
                    <h2 class="landing-footer-title">Bantuan</h2>
                    <ul class="list-unstyled d-flex flex-column gap-2 fs-13 mb-0">
                        <li><a href="#faq">FAQ</a></li>
                        <li><a href="{{ route('login') }}">Masuk</a></li>
                        <li><a href="{{ route('register') }}">Daftar</a></li>
                        <li><a href="mailto:support@atlanticpedia.test">Email Support</a></li>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h2 class="landing-footer-title">Butuh bantuan?</h2>
                    <p class="fs-13 mb-3">Tim CS kami siap membantu setiap hari.</p>
                    @if ($waLink = \App\Support\SupportWhatsapp::generalUrl())
                        <a href="{{ $waLink }}" target="_blank" rel="noopener" class="btn-ap btn-navy">
                            <span class="ms ms-sm me-1">chat</span>Chat WhatsApp
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/js/atlantic.js') }}?v={{ filemtime(public_path('assets/js/atlantic.js')) }}"></script>
    <script src="{{ asset('assets/js/components.js') }}?v={{ filemtime(public_path('assets/js/components.js')) }}"></script>
    @stack('scripts')
</body>
</html>
