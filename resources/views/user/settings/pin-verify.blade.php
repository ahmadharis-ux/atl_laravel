@extends('layouts.app')

@section('title', 'Verifikasi OTP PIN - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-4">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('settings.pin.change') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-4 m-0">Verifikasi OTP</h1>
            <p class="tx-mute fs-12 m-0">Masukkan kode 6 digit yang dikirim ke emailmu.</p>
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

    <x-ui.card title="Kode OTP">
        <form method="POST" action="{{ route('settings.pin.change.verify.store') }}" class="d-flex flex-column gap-3">
            @csrf
            <x-ui.input
                name="otp"
                label="Kode OTP *"
                inputmode="numeric"
                maxlength="6"
                pattern="[0-9]{6}"
                autocomplete="one-time-code"
                placeholder="Contoh: 123456"
                hint="Kode berlaku selama {{ config('pin.otp_ttl_minutes') }} menit."
                required
            />
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn-ap btn-mint justify-content-center">
                    <span class="ms ms-sm">verified</span> Verifikasi Kode
                </button>
                <a href="{{ route('settings.pin.change') }}" class="btn-soft justify-content-center">Batal</a>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card title="Belum Menerima Kode?">
        <form method="POST" action="{{ route('settings.pin.change.send') }}" class="d-flex flex-column gap-2">
            @csrf
            <p class="fs-12 tx-mute mb-0">Periksa folder spam, atau kirim ulang kode OTP ke email terdaftar.</p>
            <div>
                <button type="submit" class="btn-soft justify-content-center">
                    <span class="ms ms-sm">forward_to_inbox</span> Kirim Ulang OTP
                </button>
            </div>
        </form>
    </x-ui.card>
</div>
@endsection
