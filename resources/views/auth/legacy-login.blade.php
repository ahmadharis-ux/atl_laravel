@extends('layouts.app')

@section('title', 'Migrasi Akun Lama - Atlantic Pedia')

@section('content')
<div class="d-flex justify-content-center py-4">
    <div class="w-100" style="max-width: 30rem">
        <div class="d-flex align-items-center gap-2 mb-3">
            <a href="{{ route('login') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
            <div>
                <h1 class="fw-bold fs-4 m-0">User Lama?</h1>
                <p class="tx-mute fs-12 m-0">Pindahkan saldo dan poin dari aplikasi sebelumnya.</p>
            </div>
        </div>

        @if (session('error'))
            <div class="alert-ap r-sm bg-errc-l tx-err mb-3">{{ session('error') }}</div>
        @endif

        <x-ui.card title="Cocokkan Akun Lama">
            <form method="POST" action="{{ route('legacy-migration.verify') }}" class="d-flex flex-column gap-3">
                @csrf
                <p class="fs-12 tx-mute mb-0">Masukkan username atau email dan password yang digunakan di aplikasi lama.</p>
                <x-ui.input name="identifier" label="Username atau Email" autocomplete="username" required />
                <x-ui.input name="password" type="password" label="Password lama" autocomplete="current-password" required />
                <button type="submit" class="btn-navy justify-content-center">Lanjutkan</button>
            </form>
        </x-ui.card>
    </div>
</div>
@endsection
