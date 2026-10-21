@extends('layouts.app')

@section('title', 'Reseller - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-4">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('settings.index') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-4 m-0">Reseller</h1>
            <p class="tx-mute fs-12 m-0">Jual ulang produk Atlantic Pedia dengan harga khusus reseller.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="card-ap r-card p-3 d-flex align-items-center gap-2 bg-mint-l">
            <span class="ms tx-mintt">check_circle</span>
            <span class="fw-semibold fs-12">{{ session('status') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="card-ap r-card p-3 d-flex align-items-center gap-2 bg-errc-l">
            <span class="ms tx-err">error</span>
            <span class="fw-semibold fs-12 tx-err">{{ session('error') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="card-ap r-card p-3 d-flex align-items-center gap-2 bg-errc-l">
            <span class="ms tx-err">error</span>
            <span class="fw-semibold fs-12 tx-err">{{ $errors->all()[0] }}</span>
        </div>
    @endif

    @if (! $user->is_reseller)
        <x-ui.card title="Menjadi Reseller">
            <p class="fs-13 mb-3">
                Bergabung sebagai reseller Atlantic Pedia: beli produk dengan
                <span class="fw-semibold">harga lebih murah {{ rtrim(rtrim(number_format($discountPercent, 0, ',', '.'), '0'), '.') }}%</span>
                dari harga jual publik, lalu jual kembali ke pelangganmu sendiri dengan harga jualmu sendiri.
                Aktifkan sekarang — gratis dan bisa dinonaktifkan kapan saja.
            </p>

            <div class="row g-3 mb-3">
                <div class="col-sm-4">
                    <div class="card-ap r-sm p-3 h-100">
                        <span class="ibox ibox-48 bg-mint-l tx-mintt mb-2">5%</span>
                        <div class="fw-semibold fs-12">Harga Lebih Murah</div>
                        <div class="tx-mute fs-11">Diskon {{ rtrim(rtrim(number_format($discountPercent, 0, ',', '.'), '0'), '.') }}% dari harga jual untuk setiap produk yang kamu beli lewat API.</div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="card-ap r-sm p-3 h-100">
                        <span class="ibox ibox-48 bg-sky-l tx-navy mb-2"><span class="ms">api</span></span>
                        <div class="fw-semibold fs-12">Akses API & API Key</div>
                        <div class="tx-mute fs-11">Ambil daftar produk, cek harga, dan buat pesanan otomatis dari aplikasi/kasirmu sendiri.</div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="card-ap r-sm p-3 h-100">
                        <span class="ibox ibox-48 bg-gold tx-gold mb-2"><span class="ms">bar_chart</span></span>
                        <div class="fw-semibold fs-12">Laporan Transaksi</div>
                        <div class="tx-mute fs-11">Semua pesanan lewat API tercatat rapi dan bisa kamu pantau lewat riwayat transaksi.</div>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <form method="POST" action="{{ route('settings.reseller.activate') }}">
                    @csrf
                    <button type="submit" class="btn-navy">Aktifkan Menjadi Reseller</button>
                </form>
                <a href="{{ route('settings.reseller.docs') }}" class="btn-soft">Lihat Dokumentasi API</a>
            </div>
        </x-ui.card>
    @else
        <div class="card-ap r-card p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <span class="ibox ibox-48 bg-mint-l tx-mintt"><span class="ms">storefront</span></span>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-semibold fs-13">Reseller Aktif</span>
                        <span class="pill bg-mint-l tx-mintt">Aktif</span>
                    </div>
                    <div class="tx-mute fs-11">Menjadi reseller sejak {{ $user->reseller_since?->locale('id')->translatedFormat('d F Y') }} · Diskon {{ rtrim(rtrim(number_format($discountPercent, 0, ',', '.'), '0'), '.') }}% dari harga jual</div>
                </div>
            </div>
            <a href="{{ route('settings.reseller.docs') }}" class="btn-soft">Dokumentasi API</a>
        </div>

        <x-ui.card title="API Key">
            <x-slot:header>
                <span class="pill bg-sky-l tx-navy">Kredensial API</span>
            </x-slot:header>

            @if (session('api_key'))
                <p class="fs-12 tx-mute mb-2">
                    Ini API key kamu. <span class="fw-semibold">Salin dan simpan di tempat aman sekarang</span> —
                    key asli tidak akan ditampilkan lagi setelah halaman ini ditutup.
                </p>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <pre class="ap-log-pre flex-grow-1 mb-0">{{ session('api_key') }}</pre>
                    <button type="button" class="btn-soft" data-copy="{{ session('api_key') }}" data-copy-label="API Key">
                        <span class="ms ms-sm">content_copy</span> Salin
                    </button>
                </div>
            @else
                <p class="fs-12 tx-mute mb-2">
                    API key aktif: <span class="fw-semibold num">{{ $user->reseller_api_key_hint ?? '-' }}</span>
                    (hanya awalan yang ditampilkan). Key asli disimpan terenkripsi dan hanya tampil saat dibuat.
                    Belum menyimpan key? Buat ulang untuk mendapatkan key baru.
                </p>
            @endif

            <div class="d-flex flex-wrap gap-2">
                <form method="POST" action="{{ route('settings.reseller.regenerate') }}"
                      data-confirm="Buat ulang API key akan menonaktifkan key lama. Pastikan kamu sudah menyimpan key baru. Lanjutkan?">
                    @csrf
                    <button type="submit" class="btn-soft">
                        <span class="ms ms-sm">refresh</span> Buat Ulang API Key
                    </button>
                </form>
                <form method="POST" action="{{ route('settings.reseller.deactivate') }}"
                      data-confirm="Nonaktifkan keanggotaan reseller? API key akan dicabut dan akses API langsung berhenti."
                      data-confirm-title="Nonaktifkan Reseller" data-confirm-ok="Nonaktifkan" data-confirm-danger>
                    @csrf
                    <button type="submit" class="btn-soft tx-err">
                        <span class="ms ms-sm">person_off</span> Nonaktifkan
                    </button>
                </form>
            </div>
        </x-ui.card>

        <x-ui.card title="Cara Pakai Singkat">
            <ol class="fs-12 mb-3 ps-3">
                <li>Ambil daftar produk &amp; harga reseller lewat <code>GET {{ url('/api/v1/reseller/products') }}</code>.</li>
                <li>Pastikan saldo kamu cukup untuk membayar pesanan (harga reseller).</li>
                <li>Buat pesanan lewat <code>POST {{ url('/api/v1/reseller/orders') }}</code> dengan tujuan (target) produk.</li>
                <li>Pantau status pesanan lewat <code>GET {{ url('/api/v1/reseller/orders') }}</code>.</li>
            </ol>
            <a href="{{ route('settings.reseller.docs') }}" class="btn-navy">Buka Dokumentasi API Lengkap</a>
        </x-ui.card>
    @endif
</div>
@endsection
