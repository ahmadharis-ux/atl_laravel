@extends('layouts.app')

@section('title', 'Notifikasi - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-3">

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('settings.index') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-4 m-0">Notifikasi</h1>
            <p class="tx-mute fs-12 m-0">Terima pemberitahuan transaksi langsung di perangkat Anda.</p>
        </div>
    </div>

    <x-ui.card title="Push Notification">
        <p class="tx-mute fs-12">
            Aktifkan untuk mendapat notifikasi saat isi saldo atau pembelian selesai &mdash; termasuk ketika
            aplikasi sedang tertutup. Berjalan di Android/Chrome dan iOS 16.4+ yang sudah
            <em>Add to Home Screen</em>.
        </p>

        <div class="alert alert-warning fs-12 d-none" data-push-unsupported>
            Perangkat atau browser ini tidak mendukung notifikasi push, atau layanan belum dikonfigurasi.
        </div>

        <div class="d-flex align-items-center gap-3 flex-wrap">
            <button type="button" class="btn-navy" data-push-toggle>Aktifkan Notifikasi</button>
            <span class="pill bg-tealc-m tx-tealt" data-push-status>Belum aktif</span>
        </div>
    </x-ui.card>
</div>
@endsection
