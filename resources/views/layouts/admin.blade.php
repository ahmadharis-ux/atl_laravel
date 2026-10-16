<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin - Atlantic Pedia')</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('assets/css/atlantic.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/components.css') }}" rel="stylesheet">
    <link rel="icon" href="{{ asset('assets/images/6.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/images/6.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/images/6.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('assets/images/site.webmanifest') }}">
    @stack('styles')
</head>
<body>
    @include('partials.admin-sidebar')
    @include('partials.admin-topbar')
    <div class="sidebar-backdrop" data-admin-sidebar-close></div>

    <main class="main">
        @yield('content')
    </main>

    <x-ui.loader />

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/js/atlantic.js') }}?v={{ filemtime(public_path('assets/js/atlantic.js')) }}"></script>
    <script src="{{ asset('assets/js/components.js') }}?v={{ filemtime(public_path('assets/js/components.js')) }}"></script>
    @stack('scripts')
</body>
</html>