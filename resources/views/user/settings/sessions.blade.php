@extends('layouts.app')

@section('title', 'Perangkat Aktif - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-3">

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('settings.index') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-4 m-0">Perangkat Aktif</h1>
            <p class="tx-mute fs-12 m-0">Lihat dan kelola sesi login akun Anda.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="card-ap r-card p-3 d-flex align-items-center gap-2 bg-mint-l">
            <span class="ms tx-mintt">check_circle</span>
            <span class="fw-semibold fs-12">{{ session('status') }}</span>
        </div>
    @endif

    <x-ui.card title="Sesi Login">
        <x-slot:header>
            @if (count($sessions) > 1)
                <x-ui.icon-button icon="logout" tip="Keluar dari perangkat lain"
                    data-submit-form="formLogoutOthers"
                    data-confirm="Keluar dari semua perangkat lain?"
                    data-confirm-title="Keluar Perangkat Lain"
                    data-confirm-ok="Keluar" />
            @endif
        </x-slot:header>

        <form id="formLogoutOthers" action="{{ route('settings.sessions.logout-others') }}" method="POST" class="d-none">
            @csrf
        </form>

        @if (empty($sessions))
            <p class="tx-mute fs-12 m-0">Informasi perangkat tidak tersedia pada konfigurasi saat ini.</p>
        @else
            <div class="d-flex flex-column gap-3">
                @foreach ($sessions as $session)
                    <div class="d-flex align-items-center gap-3">
                        <div class="ibox ibox-44 {{ $session['is_current'] ? 'bg-mint-l tx-mintt' : 'bg-tealc-m tx-tealt' }}"><span class="ms">devices</span></div>
                        <div class="flex-fill">
                            <div class="fw-bold fs-12 d-flex align-items-center gap-1">
                                {{ $session['device'] }}
                                @if ($session['is_current'])<span class="pill bg-mint-l tx-mintt">Perangkat ini</span>@endif
                            </div>
                            <div class="fs-12 tx-mute">{{ $session['browser'] }} &middot; {{ $session['ip'] }}</div>
                        </div>
                        <span class="fs-11 tx-mute num">{{ $session['last_active']->diffForHumans() }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.card>
</div>
@endsection
