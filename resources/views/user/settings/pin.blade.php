@extends('layouts.app')

@section('title', 'PIN Transaksi - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-4">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('settings.index') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-4 m-0">PIN Transaksi</h1>
            <p class="tx-mute fs-12 m-0">Amankan pembayaran yang memotong saldo akun.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="card-ap r-card p-3 d-flex align-items-center gap-2 bg-mint-l">
            <span class="ms tx-mintt">check_circle</span><span class="fw-semibold fs-12">{{ session('status') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="card-ap r-card p-3 d-flex align-items-center gap-2 bg-errc-l">
            <span class="ms tx-err">error</span><span class="fw-semibold fs-12 tx-err">{{ session('error') }}</span>
        </div>
    @endif

    @if ($hasPin)
        <x-ui.card title="PIN Aktif">
            <div class="d-flex flex-column gap-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="pill bg-mint-l tx-mintt">Aktif</span>
                    <span class="fs-12 tx-mute">PIN 6 digit sudah terpasang di akunmu.</span>
                </div>
                <p class="fs-12 tx-mute mb-0">
                    PIN dipakai saat membayar dengan saldo dan saat membuat pesanan lewat API reseller.
                    Untuk mengubah PIN, kami akan mengirim kode OTP ke email terdaftar.
                </p>
                <div>
                    <a href="{{ route('settings.pin.change') }}" class="btn-navy">
                        <span class="ms ms-sm">lock_reset</span> Ubah PIN
                    </a>
                </div>
            </div>
        </x-ui.card>
    @else
        <x-ui.card title="Atur PIN Transaksi">
            <form method="POST" action="{{ route('settings.pin.store') }}" class="d-flex flex-column gap-3">
                @csrf
                <p class="fs-12 tx-mute mb-0">
                    Buat PIN 6 digit yang unik. Hindari angka berurutan, angka berulang, atau tanggal lahir.
                    PIN akan diminta setiap kali saldo akun dipotong.
                </p>
                <x-ui.input
                    name="pin"
                    type="password"
                    label="PIN baru *"
                    inputmode="numeric"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    autocomplete="new-password"
                    hint="Tepat 6 angka."
                    required
                />
                <x-ui.input
                    name="pin_confirmation"
                    type="password"
                    label="Ulangi PIN baru *"
                    inputmode="numeric"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    autocomplete="new-password"
                    required
                />
                <div>
                    <button type="submit" class="btn-ap btn-mint justify-content-center">
                        <span class="ms ms-sm">save</span> Simpan PIN
                    </button>
                </div>
            </form>
        </x-ui.card>
    @endif

    <div class="card-ap r-card p-3 bg-glass">
        <div class="d-flex align-items-start gap-2">
            <span class="ms tx-tealc">shield</span>
            <p class="fs-12 tx-mute mb-0">
                Jangan bagikan PIN kepada siapa pun, termasuk yang mengaku sebagai tim Atlantic Pedia.
                Setelah PIN direset, transaksi besar dibatasi sementara untuk keamanan.
            </p>
        </div>
    </div>
</div>
@endsection
