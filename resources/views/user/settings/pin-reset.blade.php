@extends('layouts.app')

@section('title', 'Buat PIN Baru - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-4">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('settings.pin') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-4 m-0">Buat PIN Baru</h1>
            <p class="tx-mute fs-12 m-0">Kode OTP sudah terverifikasi. Tentukan PIN baru.</p>
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

    <x-ui.card title="PIN Baru">
        <form method="POST" action="{{ route('settings.pin.change.reset.store') }}" class="d-flex flex-column gap-3">
            @csrf
            <p class="fs-12 tx-mute mb-0">
                Gunakan 6 angka yang unik dan mudah kamu ingat, tetapi sulit ditebak orang lain.
                PIN baru harus berbeda dari PIN lama.
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
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn-ap btn-mint justify-content-center">
                    <span class="ms ms-sm">save</span> Simpan PIN Baru
                </button>
                <a href="{{ route('settings.pin') }}" class="btn-soft justify-content-center">Batal</a>
            </div>
        </form>
    </x-ui.card>

    <div class="card-ap r-card p-3 bg-glass">
        <p class="fs-12 tx-mute mb-0">
            Setelah PIN diubah, semua perangkat lain akan dikeluarkan. Transaksi di atas
            Rp {{ number_format((int) config('pin.high_value_threshold'), 0, ',', '.') }} dibatasi sementara
            selama {{ (int) config('pin.high_value_window_hours') }} jam.
        </p>
    </div>
</div>
@endsection
