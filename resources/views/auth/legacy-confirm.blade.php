@extends('layouts.app')

@section('title', 'Konfirmasi Migrasi Akun - Atlantic Pedia')

@section('content')
<div class="d-flex justify-content-center py-4">
    <div class="w-100" style="max-width: 30rem">
        <div class="d-flex align-items-center gap-2 mb-3">
            <a href="{{ route('legacy-migration.login') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
            <div>
                <h1 class="fw-bold fs-4 m-0">Konfirmasi Migrasi</h1>
                <p class="tx-mute fs-12 m-0">Periksa penjelasan sebelum melanjutkan.</p>
            </div>
        </div>

        <x-ui.card title="Data Akun Lama Ditemukan">
            <div class="d-flex flex-column gap-3">
                <div class="card-ap r-sm p-3 fs-12">
                    Akun yang cocok: <strong>{{ $legacyUser->username ?: $legacyUser->email }}</strong>
                </div>
                <div class="alert-ap r-sm bg-errc-l tx-err fs-12" role="alert">
                    Data-data aplikasi lama akan direset, termasuk riwayat transaksi, riwayat isi saldo, dan riwayat lainnya.
                    Yang dikembalikan hanya <strong>saldo terakhir</strong> dan <strong>poin terakhir</strong>.
                </div>
                <form method="POST" action="{{ route('legacy-migration.complete') }}" class="d-flex flex-column gap-3">
                    @csrf
                    <label class="d-flex align-items-start gap-2 fs-12">
                        <input type="checkbox" name="agree" value="1" class="form-check-input mt-0" required>
                        <span>Saya memahami dan menyetujui bahwa hanya saldo dan poin terakhir yang dipindahkan.</span>
                    </label>
                    @error('agree')<div class="err show">{{ $message }}</div>@enderror
                    <button type="submit" class="btn-navy justify-content-center">Lanjutkan Migrasi</button>
                </form>
            </div>
        </x-ui.card>
    </div>
</div>
@endsection
