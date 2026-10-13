@extends('layouts.app')

@section('title', 'Profil - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-3">

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('settings.index') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-4 m-0">Profil</h1>
            <p class="tx-mute fs-12 m-0">Ubah informasi akun Anda.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="card-ap r-card p-3 d-flex align-items-center gap-2 bg-mint-l">
            <span class="ms tx-mintt">check_circle</span>
            <span class="fw-semibold fs-12">{{ session('status') }}</span>
        </div>
    @endif

    <x-ui.card title="Informasi Akun">
        <x-slot:header>
            @if ($user->hasVerifiedEmail())
                <span class="pill bg-mint-l tx-mintt"><span class="ms ms-sm">verified</span>Email terverifikasi</span>
            @else
                <a href="{{ route('verification.notice') }}" class="pill bg-gold tx-gold"><span class="ms ms-sm">error</span>Belum terverifikasi</a>
            @endif
        </x-slot:header>

        <form action="{{ route('settings.profile.update') }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <x-ui.input name="name" label="Nama lengkap" :value="$user->name" placeholder="Nama Anda" />
                </div>
                <div class="col-md-6">
                    <x-ui.input name="phone" label="Nomor HP" :value="$user->phone" placeholder="08xxxxxxxxxx" hint="Opsional." />
                </div>
                <div class="col-12">
                    <x-ui.input name="email" type="email" label="Email" :value="$user->email"
                        hint="Mengubah email akan meminta verifikasi ulang." />
                </div>
            </div>
            <div class="d-flex justify-content-end mt-2">
                <button type="submit" class="btn-navy">Simpan perubahan</button>
            </div>
        </form>
    </x-ui.card>
</div>
@endsection
