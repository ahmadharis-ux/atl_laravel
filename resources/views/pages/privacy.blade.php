@extends('layouts.app')

@section('title', 'Kebijakan Privasi - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-4">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('home') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-4 m-0">Kebijakan Privasi</h1>
            <p class="tx-mute fs-12 m-0">Terakhir diperbarui: {{ now()->format('d F Y') }}</p>
        </div>
    </div>

    <x-ui.card>
        <div class="d-flex flex-column gap-3 fs-13">
            <div>
                <h2 class="fs-5 fw-bold mb-2">1. Pendahuluan</h2>
                <p class="mb-0">Atlantic Pedia menghormati privasi Anda dan berkomitmen melindungi data pribadi yang Anda berikan saat menggunakan Layanan.</p>
            </div>

            <div>
                <h2 class="fs-5 fw-bold mb-2">2. Data yang Kami Kumpulkan</h2>
                <ul class="mb-0 ps-3">
                    <li><strong>Data Akun:</strong> Nama, alamat email, nomor telepon, dan informasi pendaftaran lainnya.</li>
                    <li><strong>Data Transaksi:</strong> Riwayat pembelian, metode pembayaran, dan detail transaksi.</li>
                    <li><strong>Data Teknis:</strong> Alamat IP, jenis perangkat, browser, dan log aktivitas.</li>
                </ul>
            </div>

            <div>
                <h2 class="fs-5 fw-bold mb-2">3. Penggunaan Data</h2>
                <ul class="mb-0 ps-3">
                    <li>Menyediakan, mengoperasikan, dan memelihara Layanan.</li>
                    <li>Memproses transaksi dan verifikasi pembayaran.</li>
                    <li>Meningkatkan keamanan, mencegah penipuan, dan kepatuhan hukum.</li>
                    <li>Menghubungi Anda terkait akun atau transaksi.</li>
                </ul>
            </div>

            <div>
                <h2 class="fs-5 fw-bold mb-2">4. Perlindungan Data</h2>
                <p class="mb-0">Kami menerapkan langkah keamanan teknis dan organisasi yang wajar untuk melindungi data pribadi Anda dari akses, pengungkapan, atau perubahan yang tidak sah.</p>
            </div>

            <div>
                <h2 class="fs-5 fw-bold mb-2">5. Berbagi Data</h2>
                <p class="mb-0">Kami tidak menjual data pribadi Anda. Data hanya dibagikan kepada pihak yang diperlukan untuk memproses transaksi atau jika diwajibkan oleh hukum.</p>
            </div>

            <div>
                <h2 class="fs-5 fw-bold mb-2">6. Hak Pengguna</h2>
                <ul class="mb-0 ps-3">
                    <li>Mengakses, memperbarui, atau menghapus data pribadi Anda.</li>
                    <li>Meminta salinan data yang kami miliki.</li>
                    <li>Menarik persetujuan atau membatasi pemrosesan data.</li>
                </ul>
            </div>

            <div>
                <h2 class="fs-5 fw-bold mb-2">7. Perubahan Kebijakan</h2>
                <p class="mb-0">Kebijakan Privasi ini dapat diperbarui sewaktu-waktu. Perubahan akan diumumkan melalui Layanan dan berlaku sejak tanggal pembaruan.</p>
            </div>

            <div>
                <h2 class="fs-5 fw-bold mb-2">8. Hubungi Kami</h2>
                <p class="mb-0">Jika Anda memiliki pertanyaan terkait Kebijakan Privasi ini, silakan hubungi kami melalui layanan dukungan yang tersedia.</p>
            </div>
        </div>
    </x-ui.card>
</div>
@endsection
