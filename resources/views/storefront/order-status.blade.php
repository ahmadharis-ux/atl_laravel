@extends('layouts.app')

@section('title', 'Status Pesanan '.$order->order_number.' - Atlantic Pedia Tab')

@section('content')
@php
    $paymentMethod = $storefront['payment_method'] ?? ($order->payment_method->value === 'qris' ? 'qris' : 'cash');
    $sellingPrice = (int) ($storefront['selling_price'] ?? $order->amount);
    $isPending = $order->status === \App\Enums\OrderStatus::Pending;
@endphp

<div class="row g-3">
<div class="col-lg-8 mx-auto">
<div class="d-flex flex-column gap-3">
    <x-ui.card title="Status Pesanan">
        <div class="d-flex flex-column gap-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <div class="tx-mute fs-11">Nomor pesanan</div>
                    <div class="fw-bold num">{{ $order->order_number }}</div>
                </div>
                <span class="pill {{ $order->status->tone() }}">{{ $order->status->label() }}</span>
            </div>

            <div class="row g-3">
                <div class="col-sm-6">
                    <div class="card-ap r-sm p-3 h-100">
                        <div class="tx-mute fs-11">Produk</div>
                        <div class="fw-semibold fs-13">{{ $order->product?->name ?? 'Produk' }}</div>
                        <div class="tx-mute fs-11 mt-2">{{ $order->target }}@if($order->target_zone) · Zone {{ $order->target_zone }}@endif</div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="card-ap r-sm p-3 h-100">
                        <div class="tx-mute fs-11">Toko reseller</div>
                        <div class="fw-semibold fs-13">{{ $order->user?->name ?? ($storefront['store_name'] ?? 'Reseller') }}</div>
                        <div class="tx-mute fs-11 mt-2">{{ $paymentMethod === 'qris' ? 'QRIS melalui Midtrans' : 'Tunai kepada reseller' }}</div>
                    </div>
                </div>
            </div>

            <div class="card-ap r-sm p-3 d-flex flex-column gap-2">
                <div class="d-flex justify-content-between gap-3 fs-13">
                    <span class="tx-mute">Harga produk</span>
                    <span class="num">Rp {{ number_format($sellingPrice, 0, ',', '.') }}</span>
                </div>
                @if ($paymentMethod === 'qris')
                    <div class="d-flex justify-content-between gap-3 fs-13">
                        <span class="tx-mute">Biaya QRIS</span>
                        <span class="num">Rp {{ number_format($order->fee, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between gap-3 pt-2 border-top">
                        <span class="fw-bold">Total dibayar</span>
                        <span class="num fw-bold">Rp {{ number_format($order->totalDue(), 0, ',', '.') }}</span>
                    </div>
                @else
                    <div class="d-flex justify-content-between gap-3 pt-2 border-top">
                        <span class="fw-bold">Pembayaran</span>
                        <span class="fw-semibold">Tunai langsung ke reseller</span>
                    </div>
                @endif
            </div>

            @if ($isPending && $paymentMethod === 'qris')
                <div class="card-ap r-sm p-3 d-flex flex-column gap-2">
                    <p class="fs-12 mb-0">Selesaikan pembayaran di halaman aman Midtrans. Status pesanan akan diperbarui setelah pembayaran terkonfirmasi.</p>
                    @if ($paymentUrl)
                        <a href="{{ $paymentUrl }}" class="btn-ap btn-mint justify-content-center" rel="noopener">
                            Bayar dengan Midtrans <span class="ms ms-sm">open_in_new</span>
                        </a>
                    @endif
                    <form method="POST" action="{{ $syncUrl }}">
                        @csrf
                        <button type="submit" class="btn-soft w-100 justify-content-center">Perbarui Status</button>
                    </form>
                </div>
            @elseif ($isPending && $paymentMethod === 'cash')
                <div class="card-ap r-sm p-3 bg-gold">
                    <div class="fw-semibold fs-12">Menunggu konfirmasi reseller</div>
                    <p class="fs-11 mb-0 mt-1">Serahkan Rp {{ number_format($sellingPrice, 0, ',', '.') }} secara tunai kepada reseller. Pesanan diproses setelah reseller mengonfirmasi penerimaan.</p>
                </div>
            @elseif ($paymentMethod === 'qris'
                && in_array($order->status, [\App\Enums\OrderStatus::Failed, \App\Enums\OrderStatus::Expired], true)
                && $order->refund_status !== null)
                <div class="card-ap r-sm p-3 {{ $order->refund_status === 'completed' ? 'bg-mint-l' : 'bg-gold' }}">
                    @if ($order->refund_status === 'completed')
                        <div class="fw-semibold fs-12">Refund QRIS telah dikonfirmasi</div>
                        <p class="fs-11 mb-0 mt-1">Dana dikembalikan melalui Midtrans ke metode pembayaran asal.</p>
                    @elseif ($order->refund_status === 'manual_required')
                        <div class="fw-semibold fs-12">Refund perlu ditangani admin</div>
                        <p class="fs-11 mb-0 mt-1">Tim Atlantic Pedia akan memeriksa pengembalian dana. Simpan nomor pesanan ini untuk referensi.</p>
                    @else
                        <div class="fw-semibold fs-12">Refund sedang diproses</div>
                        <p class="fs-11 mb-0 mt-1">Permintaan pengembalian dana sedang dikirim ke Midtrans.</p>
                    @endif
                </div>
            @elseif ($order->status === \App\Enums\OrderStatus::Success)
                <div class="card-ap r-sm p-3 bg-mint-l">
                    <div class="fw-semibold fs-12">Pesanan berhasil</div>
                    <p class="fs-11 mb-0 mt-1">Produk telah diproses. Simpan tautan ini untuk melihat status pesanan.</p>
                </div>
            @elseif ($order->status === \App\Enums\OrderStatus::Expired)
                <div class="card-ap r-sm p-3 bg-gold">
                    <div class="fw-semibold fs-12">Pesanan kedaluwarsa</div>
                    <p class="fs-11 mb-0 mt-1">Batas pembayaran atau konfirmasi tunai telah lewat. Buat pesanan baru dari toko reseller.@if ($paymentMethod === 'cash') Jika kamu sudah menyerahkan uang tunai, hubungi reseller untuk pengembaliannya.@endif</p>
                </div>
            @elseif ($order->status === \App\Enums\OrderStatus::Failed && $paymentMethod === 'cash')
                <div class="card-ap r-sm p-3 bg-errc-l">
                    <div class="fw-semibold fs-12">Pesanan gagal diproses</div>
                    <p class="fs-11 mb-0 mt-1">Hubungi reseller untuk pengembalian uang tunai yang telah diserahkan.</p>
                </div>
            @elseif ($order->status === \App\Enums\OrderStatus::Processing)
                <div class="card-ap r-sm p-3 bg-sky-l">
                    <div class="fw-semibold fs-12">Pesanan sedang diproses</div>
                    <p class="fs-11 mb-0 mt-1">Pembayaran diterima. Tunggu sampai produk selesai diproses.</p>
                </div>
            @endif
        </div>
    </x-ui.card>
</div>
</div>
</div>
@endsection
