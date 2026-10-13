@extends('layouts.app')

@section('title', 'Ubah Kata Sandi - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-3">

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('settings.index') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-4 m-0">Ubah Kata Sandi</h1>
            <p class="tx-mute fs-12 m-0">Perbarui kata sandi akun Anda.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="card-ap r-card p-3 d-flex align-items-center gap-2 bg-mint-l">
            <span class="ms tx-mintt">check_circle</span>
            <span class="fw-semibold fs-12">{{ session('status') }}</span>
        </div>
    @endif

    <x-ui.card title="Kata Sandi">
        <form action="{{ route('settings.password.update') }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-12">
                    <x-ui.input name="current_password" type="password" label="Kata sandi saat ini" autocomplete="current-password" />
                </div>
                <div class="col-md-6">
                    <x-ui.input name="password" type="password" label="Kata sandi baru" autocomplete="new-password" hint="Minimal 8 karakter." />
                </div>
                <div class="col-md-6">
                    <x-ui.input name="password_confirmation" type="password" label="Konfirmasi kata sandi baru" autocomplete="new-password" />
                </div>
            </div>
            <div class="d-flex justify-content-end mt-2">
                <button type="submit" class="btn-navy">Perbarui kata sandi</button>
            </div>
        </form>
    </x-ui.card>
</div>
@endsection
