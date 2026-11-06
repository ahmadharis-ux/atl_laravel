@extends('layouts.app')

@section('title', 'Detail Transaksi Reseller - Atlantic Pedia Tab')

@section('content')
@php
    $source = match ($order->source) {
        'api' => ['API reseller', 'bg-gold tx-gold'],
        'store_cash' => ['Toko · Tunai customer', 'bg-sky-l tx-navy'],
        'store_qris' => ['Toko · QRIS customer', 'bg-mint-l tx-mintt'],
        default => ['Lainnya', 'bg-glass tx-mute'],
    };
    $storefront = $order->metadata['storefront'] ?? [];
    $isStorefront = in_array($order->source, ['store_cash', 'store_qris'], true);
    $sellingPrice = (int) ($storefront['selling_price'] ?? $order->amount);
    $resellerPrice = (int) ($storefront['reseller_price'] ?? $order->amount);
    $resellerMargin = max(0, $sellingPrice - $resellerPrice);
@endphp

<div class="d-flex flex-column gap-4">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('settings.reseller.store.transactions') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-4 m-0">Detail Transaksi</h1>
            <p class="tx-mute fs-12 m-0">{{ $order->order_number }}</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <x-ui.card title="Ringkasan Pesanan">
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <div class="fw-bold fs-5">{{ $order->product?->name ?? 'Produk tidak tersedia' }}</div>
                            <div class="tx-mute fs-12">{{ $order->order_number }} · {{ $order->created_at->locale('id')->translatedFormat('d M Y, H:i') }}</div>
                        </div>
                        <div class="d-flex gap-2">
                            <span class="pill {{ $source[1] }}">{{ $source[0] }}</span>
                            <span class="pill {{ $order->status->tone() }}">{{ $order->status->label() }}</span>
                        </div>
                    </div>
                    <dl class="row g-2 fs-13 mb-0">
                        <dt class="col-5 tx-mute">Tujuan</dt><dd class="col-7 mb-0">{{ $order->target }}@if ($order->target_zone) · {{ $order->target_zone }}@endif</dd>
                        <dt class="col-5 tx-mute">Metode pembayaran</dt><dd class="col-7 mb-0">{{ $order->payment_method?->label() ?? '-' }}</dd>
                        <dt class="col-5 tx-mute">Nominal transaksi</dt><dd class="col-7 mb-0 num fw-semibold">Rp {{ number_format((int) $order->amount, 0, ',', '.') }}</dd>
                        <dt class="col-5 tx-mute">Provider</dt><dd class="col-7 mb-0">{{ $order->provider ?? '-' }}</dd>
                        <dt class="col-5 tx-mute">Nomor seri</dt><dd class="col-7 mb-0">{{ $order->provider_sn ?? '-' }}</dd>
                    </dl>
                </div>
            </x-ui.card>
        </div>
        <div class="col-lg-5">
            <x-ui.card title="Alur Dana">
                <div class="d-flex flex-column gap-2 fs-12">
                    @if ($order->source === 'api')
                        <div class="card-ap r-sm p-3">Saldo reseller dipotong sebesar <span class="num fw-semibold">Rp {{ number_format((int) $order->amount, 0, ',', '.') }}</span> saat pesanan dibuat.</div>
                    @elseif ($order->source === 'store_cash')
                        <div class="card-ap r-sm p-3">Customer menyerahkan uang tunai kepada reseller. Saldo reseller dipotong saat konfirmasi tunai.</div>
                        <dl class="row g-2 fs-12 mb-0">
                            <dt class="col-6 tx-mute">Harga customer</dt><dd class="col-6 mb-0 text-end num">Rp {{ number_format($sellingPrice, 0, ',', '.') }}</dd>
                            <dt class="col-6 tx-mute">Harga reseller</dt><dd class="col-6 mb-0 text-end num">Rp {{ number_format($resellerPrice, 0, ',', '.') }}</dd>
                            <dt class="col-6 fw-semibold">Keuntungan langsung</dt><dd class="col-6 mb-0 text-end num fw-semibold tx-mintt">Rp {{ number_format($resellerMargin, 0, ',', '.') }}</dd>
                        </dl>
                    @elseif ($order->source === 'store_qris')
                        <div class="card-ap r-sm p-3">Customer membayar melalui Midtrans. Saldo reseller tidak dipotong.</div>
                        <dl class="row g-2 fs-12 mb-0">
                            <dt class="col-6 tx-mute">Harga customer</dt><dd class="col-6 mb-0 text-end num">Rp {{ number_format($sellingPrice, 0, ',', '.') }}</dd>
                            <dt class="col-6 tx-mute">Harga reseller</dt><dd class="col-6 mb-0 text-end num">Rp {{ number_format($resellerPrice, 0, ',', '.') }}</dd>
                            <dt class="col-6 fw-semibold">Selisih komisi</dt><dd class="col-6 mb-0 text-end num fw-semibold tx-mintt">Rp {{ number_format($resellerMargin, 0, ',', '.') }}</dd>
                        </dl>
                        @if ($order->resellerCommission)
                            <div class="card-ap r-sm p-3 bg-mint-l tx-mintt">Komisi tercatat: <span class="num fw-semibold">Rp {{ number_format((int) $order->resellerCommission->amount, 0, ',', '.') }}</span>, yaitu selisih harga customer dan harga reseller.</div>
                        @else
                            <div class="card-ap r-sm p-3">Komisi dicatat setelah pembayaran dan fulfillment berhasil.</div>
                        @endif
                    @else
                        <div class="card-ap r-sm p-3">Detail alur dana tidak tersedia untuk sumber transaksi ini.</div>
                    @endif
                </div>
            </x-ui.card>
        </div>
    </div>

    @if ($order->walletTransactions->isNotEmpty())
        <x-ui.card title="Mutasi Saldo">
            <div class="d-flex flex-column gap-2">
                @foreach ($order->walletTransactions as $transaction)
                    <div class="d-flex justify-content-between gap-3 fs-12">
                        <span>{{ $transaction->description ?? 'Mutasi saldo' }}</span>
                        <span class="num {{ $transaction->direction->value === 'debit' ? 'tx-err' : 'tx-mintt' }}">{{ $transaction->direction->value === 'debit' ? '-' : '+' }}Rp {{ number_format((int) $transaction->amount, 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    @endif
</div>
@endsection
