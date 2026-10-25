@extends('layouts.app')

@section('title', 'Syarat & Ketentuan - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-4">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('home') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-4 m-0">Syarat & Ketentuan</h1>
            <p class="tx-mute fs-12 m-0">Terakhir diperbarui: {{ now()->format('d F Y') }}</p>
        </div>
    </div>

    <x-ui.card>
        <div class="d-flex flex-column gap-3 fs-13">
            <div>
                <h2 class="fs-5 fw-bold mb-2">1. Ketentuan Umum</h2>
                <p class="mb-0">Dengan mengakses dan menggunakan layanan Atlantic Pedia ("Layanan"), Anda menyetujui untuk terikat dengan Syarat & Ketentuan ini. Jika Anda tidak setuju dengan ketentuan ini, mohon untuk tidak menggunakan Layanan.</p>
            </div>

            <div>
                <h2 class="fs-5 fw-bold mb-2">2. Definisi</h2>
                <ul class="mb-0 ps-3">
                    <li>"Pengguna" adalah individu atau entitas yang mendaftar dan menggunakan Layanan.</li>
                    <li>"Transaksi" adalah pembelian produk digital atau layanan yang tersedia melalui Layanan.</li>
                    <li>"Saldo" adalah dana virtual yang telah diisi oleh Pengguna untuk digunakan dalam bertransaksi.</li>
                </ul>
            </div>

            <div>
                <h2 class="fs-5 fw-bold mb-2">3. Pendaftaran & Akun</h2>
                <ul class="mb-0 ps-3">
                    <li>Pengguna wajib memberikan informasi yang akurat, lengkap, dan terkini saat mendaftar.</li>
                    <li>Pengguna bertanggung jawab menjaga kerahasiaan kredensial akun.</li>
                    <li>Pengguna bertanggung jawab atas seluruh aktivitas yang terjadi melalui akunnya.</li>
                </ul>
            </div>

            <div>
                <h2 class="fs-5 fw-bold mb-2">4. Transaksi & Pembayaran</h2>
                <ul class="mb-0 ps-3">
                    <li>Semua harga yang tercantum sudah termasuk ketentuan yang berlaku.</li>
                    <li>Transaksi menggunakan Saldo atau metode pembayaran lain yang tersedia.</li>
                    <li>Biaya admin dapat dikenakan sesuai metode pembayaran yang dipilih.</li>
                    <li>Pesanan yang telah diproses tidak dapat dibatalkan kecuali kesalahan sistem.</li>
                </ul>
            </div>

            <div>
                <h2 class="fs-5 fw-bold mb-2">5. Penggunaan Layanan</h2>
                <ul class="mb-0 ps-3">
                    <li>Pengguna dilarang menggunakan Layanan untuk tujuan ilegal.</li>
                    <li>Pengguna dilarang melakukan penyalahgunaan sistem atau upaya peretasan.</li>
                    <li>Atlantic Pedia berhak membatasi atau menonaktifkan akun yang melanggar ketentuan.</li>
                </ul>
            </div>

            <div>
                <h2 class="fs-5 fw-bold mb-2">6. Batasan Tanggung Jawab</h2>
                <p class="mb-0">Atlantic Pedia tidak bertanggung jawab atas kerugian yang timbul akibat kesalahan input data oleh Pengguna atau gangguan dari penyedia layanan pihak ketiga.</p>
            </div>

            <div>
                <h2 class="fs-5 fw-bold mb-2">7. Perubahan Ketentuan</h2>
                <p class="mb-0">Atlantic Pedia dapat mengubah Syarat & Ketentuan ini sewaktu-waktu tanpa pemberitahuan terlebih dahulu. Penggunaan berkelanjutan atas Layanan berarti Pengguna menyetujui perubahan tersebut.</p>
            </div>
        </div>
    </x-ui.card>
</div>
@endsection
