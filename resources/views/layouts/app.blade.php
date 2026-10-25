<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Atlantic Pedia Tab')</title>

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
<body>
    @include('partials.sidebar')
    @include('partials.topbar')

    <main class="main">
        @yield('content')
    </main>

    @include('partials.bottom-nav')

    <x-ui.loader />

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/js/atlantic.js') }}?v={{ filemtime(public_path('assets/js/atlantic.js')) }}"></script>

    @php
        $firebaseClient = config('firebase.client');
        $firebaseConfig = array_filter([
            'apiKey' => $firebaseClient['api_key'] ?? null,
            'authDomain' => $firebaseClient['auth_domain'] ?? null,
            'projectId' => $firebaseClient['project_id'] ?? null,
            'storageBucket' => $firebaseClient['storage_bucket'] ?? null,
            'messagingSenderId' => $firebaseClient['messaging_sender_id'] ?? null,
            'appId' => $firebaseClient['app_id'] ?? null,
        ], fn ($value) => filled($value));
    @endphp
    <script>
        window.ATL_PUSH = {
            configured: {{ $firebaseConfig !== [] && filled(config('firebase.client.vapid_key')) ? 'true' : 'false' }},
            loggedIn: {{ auth()->check() ? 'true' : 'false' }},
            userId: @json(auth()->id()),
            config: @json($firebaseConfig),
            vapidKey: @json(config('firebase.client.vapid_key')),
            tokenUrl: @json(route('notifications.token.store')),
            deleteUrl: @json(route('notifications.token.destroy')),
            swUrl: @json(route('firebase.sw')),
        };
    </script>
    @if ($firebaseConfig !== [])
        <script src="https://www.gstatic.com/firebasejs/11.6.0/firebase-app-compat.js"></script>
        <script src="https://www.gstatic.com/firebasejs/11.6.0/firebase-messaging-compat.js"></script>
    @endif
    <script src="{{ asset('assets/js/push.js') }}?v={{ filemtime(public_path('assets/js/push.js')) }}"></script>

    <script src="{{ asset('assets/js/components.js') }}?v={{ filemtime(public_path('assets/js/components.js')) }}"></script>
    @stack('scripts')
</body>
</html>
