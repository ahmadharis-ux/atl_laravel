@extends('layouts.app')

@section('title', 'Ubah PIN Transaksi - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-4">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('settings.pin') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-4 m-0">Ubah PIN Transaksi</h1>
            <p class="tx-mute fs-12 m-0">Verifikasi lewat email sebelum membuat PIN baru.</p>
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

    <x-ui.card title="Verifikasi Email">
        <form method="POST" action="{{ route('settings.pin.change.send') }}" class="d-flex flex-column gap-3">
            @csrf
            <p class="fs-12 tx-mute mb-0">
                Kami akan mengirim kode OTP 6 digit ke <strong>{{ auth()->user()->email }}</strong>.
                Kode berlaku singkat dan hanya bisa dipakai sekali.
            </p>
            @error('otp')<div class="err show">{{ $message }}</div>@enderror
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn-ap btn-mint justify-content-center">
                    <span class="ms ms-sm">mail</span> Kirim Kode OTP
                </button>
                <a href="{{ route('settings.pin') }}" class="btn-soft justify-content-center">Batal</a>
            </div>
        </form>
    </x-ui.card>
</div>
@endsection
