@extends('layouts.landing')

@section('title', 'Segera Hadir - Atlantic Pedia')
@section('meta_description', 'Atlantic Pedia segera hadir untuk memenuhi kebutuhan transaksi digital Anda.')

@section('content')
    <section class="landing-hero d-flex align-items-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <span class="landing-badge">
                        <span class="ms ms-sm">rocket_launch</span>
                        Segera Hadir
                    </span>
                    <h1 class="landing-title mx-auto mt-4">Pengalaman transaksi digital yang lebih mudah segera hadir.</h1>
                    <p class="landing-subtitle mx-auto mt-3">
                        Kami sedang menyiapkan Atlantic Pedia untuk menemani kebutuhan pulsa, paket data, token listrik, dan produk digital Anda.
                    </p>

                    <div class="landing-preview mt-4">
                        <div class="landing-preview-card">
                            <span class="ibox ibox-40 bg-mint-l tx-mintt mx-auto">
                                <span class="ms">event_available</span>
                            </span>
                            <h2 class="fs-13 fw-bold mt-3 mb-1">Peluncuran dijadwalkan</h2>
                            <p class="fs-12 mb-0">
                                {{ $launchDate->format('d/m/Y \p\u\k\u\l H.i') }} WIB
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
