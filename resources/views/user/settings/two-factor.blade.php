@extends('layouts.app')

@section('title', 'Autentikasi Dua Faktor - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-3">

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('settings.index') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-4 m-0">Autentikasi Dua Faktor</h1>
            <p class="tx-mute fs-12 m-0">Amankan akun dengan kode dari aplikasi authenticator.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="card-ap r-card p-3 d-flex align-items-center gap-2 bg-mint-l">
            <span class="ms tx-mintt">check_circle</span>
            <span class="fw-semibold fs-12">{{ session('status') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="card-ap r-card p-3 d-flex align-items-center gap-2 bg-errc-l">
            <span class="ms tx-err">error</span>
            <span class="fw-semibold fs-12 tx-err">{{ $errors->first() }}</span>
        </div>
    @endif

    <x-ui.card title="Status 2FA">
        <x-slot:header>
            @if ($twoFactorEnabled)
                <span class="pill bg-mint-l tx-mintt">Aktif</span>
            @elseif ($twoFactorPending)
                <span class="pill bg-gold tx-gold">Menunggu konfirmasi</span>
            @else
                <span class="pill bg-errc-l tx-err">Nonaktif</span>
            @endif
        </x-slot:header>

        @if (! $twoFactorEnabled && ! $twoFactorPending)
            <p class="tx-mute fs-12 mb-3">Tambahkan lapisan keamanan ekstra dengan kode dari aplikasi authenticator (Google Authenticator, Authy, dsb).</p>
            <form action="{{ route('settings.two-factor.enable') }}" method="POST">
                @csrf
                <x-ui.input name="password" type="password" label="Konfirmasi kata sandi" autocomplete="current-password" />
                <button type="submit" class="btn-navy w-100 mt-1">Aktifkan 2FA</button>
            </form>
        @elseif ($twoFactorPending)
            <p class="tx-mute fs-12 mb-2">Tambahkan kode berikut ke aplikasi authenticator Anda:</p>
            <div class="p-3 r-sm bg-line mb-2">
                <div class="fs-11 fw-800 tx-mute text-uppercase mb-1">Secret</div>
                <code class="fs-12 tx-deep" style="word-break:break-all">{{ $twoFactorSecret }}</code>
            </div>
            <details class="mb-3">
                <summary class="fs-12 tx-teal fw-bold" style="cursor:pointer">Tampilkan URI otpauth</summary>
                <div class="p-2 r-sm bg-line mt-2"><code class="fs-11 tx-mute" style="word-break:break-all">{{ $twoFactorUri }}</code></div>
            </details>
            <form action="{{ route('settings.two-factor.confirm') }}" method="POST">
                @csrf
                <x-ui.input name="code" label="Kode verifikasi (6 digit)" inputmode="numeric" autocomplete="one-time-code" placeholder="000000" />
                <button type="submit" class="btn-navy w-100 mt-1">Konfirmasi &amp; aktifkan</button>
            </form>
        @else
            <p class="tx-mute fs-12 mb-3">2FA aktif. Anda memiliki <b class="tx-deep">{{ $recoveryCodesCount }}</b> kode pemulihan tersisa. Simpan dengan aman untuk berjaga-jaga bila kehilangan perangkat.</p>
            <form action="{{ route('settings.two-factor.disable') }}" method="POST">
                @csrf
                @method('DELETE')
                <x-ui.input name="password" type="password" label="Konfirmasi kata sandi untuk menonaktifkan" autocomplete="current-password" />
                <button type="submit" class="btn-soft w-100 mt-1 tx-err">Nonaktifkan 2FA</button>
            </form>
        @endif
    </x-ui.card>
</div>
@endsection
