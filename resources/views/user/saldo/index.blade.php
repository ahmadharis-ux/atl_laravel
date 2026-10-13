@extends('layouts.app')

@section('title', 'Saldo - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-3">

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('dashboard') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-4 m-0">Saldo</h1>
            <p class="tx-mute fs-12 m-0">Saldo, poin reward &amp; biaya admin kamu.</p>
        </div>
    </div>

    <section class="balance r-card d-flex flex-column gap-3">
        <div class="d-flex align-items-center justify-content-between gap-2">
            <span class="fs-11 fw-bold text-uppercase tx-tealc txt-cap">Saldo Tersedia</span>
            <span class="pill bg-glass tx-mint"><span class="pulse"></span>Akun Aktif</span>
        </div>
        <div class="d-flex align-items-baseline gap-2">
            <span class="tx-tealc fw-bold fs-4">Rp</span>
            <span class="num saldo" id="saldo">{{ number_format($balance, 0, ',', '.') }}</span>
            <button id="eye" class="btn-eye ms-auto" aria-label="Tampilkan atau sembunyikan saldo">
                <span class="ms ms-md">visibility</span>
            </button>
        </div>
        <span class="fs-12 tx-skyd balance-meta">Terakhir diperbarui 2 menit yang lalu</span>
        <div class="d-flex gap-3">
            <a href="{{ route('deposits.create') }}" class="btn-ap btn-mint flex-fill"><span class="ms">add</span>Top Up Saldo</a>
            <a href="{{ route('saldo.mutations') }}" class="btn-ap btn-glass flex-fill"><span class="ms">receipt_long</span>Mutasi</a>
        </div>
    </section>

    <nav class="card-ap r-card p-3 row g-2 m-0 text-center">
        @foreach ($shortcuts as $s)
            <a href="#" class="col-3 sc d-flex flex-column align-items-center gap-1 p-1 rounded-3">
                <span class="ibox ibox-48 {{ $s['tone'] }}"><span class="ms">{{ $s['icon'] }}</span></span>
                <span class="fs-12 fw-semibold">{{ $s['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="row g-3">
        <div class="col-sm-6">
            <div class="mini-poin r-sm p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="d-flex align-items-center gap-1 fw-bold tx-gold"><span class="ms ms-sm f">star</span>Poin Reward</span>
                    <a href="#" class="fs-12 fw-bold tx-gold px-2 py-1 rounded-pill bg-gold-soft">Tukar</a>
                </div>
                <div class="num fw-800 fs-4 mt-2">{{ number_format($points, 0, ',', '.') }} <span class="tx-gold fs-6">Poin</span></div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mini-admin r-sm p-3 h-100">
                <span class="d-flex align-items-center gap-1 fw-bold tx-tealt"><span class="ms ms-sm">swap_horiz</span>Biaya Admin</span>
                <div class="d-flex align-items-center justify-content-between mt-2 gap-1">
                    <span class="fw-bold">Gratis Transfer</span>
                    <span class="num fw-bold tx-tealt bg-tealc px-2 rounded-2">Rp {{ number_format($adminFee, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card-ap r-card p-3">
        <div class="d-flex align-items-center justify-content-between pb-2">
            <h3 class="fw-bold fs-5 m-0">Transaksi Terakhir</h3>
            <a href="{{ route('orders.index') }}" class="fw-semibold tx-teal">Selengkapnya</a>
        </div>
        <ul class="trx list-unstyled m-0">
            @forelse ($transactions as $t)
                <li data-row-href="{{ $t['url'] }}">
                    <span class="ibox ibox-44 bg-mint-l tx-mintt"><span class="ms" style="font-size:20px">{{ $t['icon'] }}</span></span>
                    <div class="t">
                        <div class="fw-bold text-truncate">{{ $t['title'] }}</div>
                        <div class="fs-12 tx-mute text-truncate">{{ $t['detail'] }}</div>
                    </div>
                    <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
                        <span class="num fw-bold">Rp {{ number_format($t['amount'], 0, ',', '.') }}</span>
                        <span class="pill fs-11 {{ $t['status'] === 'Sukses' ? 'bg-mint-l tx-mintt' : 'bg-gold tx-gold' }}">{{ $t['status'] }}</span>
                    </div>
                </li>
            @empty
                <li class="d-block border-0 p-0">
                    <x-ui.empty-state icon="receipt_long" title="Belum ada transaksi" description="Riwayat transaksi kamu akan tampil di sini." />
                </li>
            @endforelse
        </ul>
    </div>

</div>
@endsection