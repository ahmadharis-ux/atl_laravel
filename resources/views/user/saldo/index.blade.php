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

    @if ($pendingDeposits->isNotEmpty())
        <div class="card-ap r-card p-3">
            <div class="d-flex align-items-center gap-2 pb-2">
                <span class="ibox ibox-40 bg-gold tx-gold"><span class="ms">schedule</span></span>
                <div>
                    <div class="fw-semibold fs-13">{{ $pendingDeposits->count() }} top up menunggu pembayaran</div>
                    <div class="tx-mute fs-12">Selesaikan sebelum kedaluwarsa.</div>
                </div>
                <a href="{{ route('deposits.index') }}" class="ms-auto fw-semibold tx-teal fs-12">Lihat semua</a>
            </div>
            <ul class="trx list-unstyled m-0">
                @foreach ($pendingDeposits->take(3) as $d)
                    @php($method = $d->payment_method)
                    <li data-row-href="{{ route('deposits.show', $d) }}">
                        <span class="ibox ibox-44 bg-gold tx-gold"><span class="ms" style="font-size:20px">{{ $method?->icon() }}</span></span>
                        <div class="t">
                            <div class="fw-bold text-truncate">{{ $method?->label() }}@if ($d->payment_channel) &middot; {{ $d->payment_channel }}@endif</div>
                            <div class="fs-12 tx-mute text-truncate">{{ $d->reference }}</div>
                        </div>
                        <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
                            <span class="num fw-bold">Rp {{ number_format($d->amount, 0, ',', '.') }}</span>
                            @if ($d->expires_at)
                                <span class="fs-11 tx-skyd">s/d {{ $d->expires_at->locale('id')->translatedFormat('d M H:i') }}</span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-sm-6">
            <div class="mini-poin r-sm {{ $tier->cssClass() }} p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="d-flex align-items-center gap-1 fw-bold tx-gold"><span class="ms ms-sm f">star</span>Poin Reward</span>
                    <a href="{{ route('merchandise.index') }}" class="fs-12 fw-bold tx-gold px-2 py-1 rounded-pill bg-gold-soft">Tukar</a>
                </div>
                <div class="num fw-800 fs-4 mt-2">{{ number_format($points, 0, ',', '.') }} <span class="tx-gold fs-6">Poin</span></div>
                <div class="d-flex align-items-center gap-1 mt-2">
                    <span class="pill tier-{{ $tier->value }} fs-11"><span class="ms ms-sm me-1" style="font-size:12px">{{ $tier->icon() }}</span>{{ $tier->label() }}</span>
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