@extends('layouts.app')

@section('title', 'Riwayat Transaksi - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-3">

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('dashboard') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-4 m-0">Riwayat Transaksi</h1>
            <p class="tx-mute fs-12 m-0">Semua pesanan yang pernah kamu buat.</p>
        </div>
    </div>

    <div class="card-ap r-card p-3">
        <ul class="trx list-unstyled m-0">
            @forelse ($orders as $order)
                <li data-row-href="{{ route('orders.show', $order) }}">
                    <span class="ibox ibox-44 bg-mint-l tx-mintt"><span class="ms" style="font-size:20px">shopping_bag</span></span>
                    <div class="t">
                        <div class="fw-bold text-truncate">{{ $order->product?->name ?? 'Produk tidak tersedia' }}</div>
                        <div class="fs-12 tx-mute text-truncate">
                            {{ $order->order_number }} &middot; {{ $order->target }}@if ($order->target_zone) ({{ $order->target_zone }})@endif
                        </div>
                        <div class="fs-11 tx-skyd">{{ $order->created_at->format('d M Y, H:i') }}</div>
                    </div>
                    <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
                        <span class="num fw-bold">Rp {{ number_format($order->amount, 0, ',', '.') }}</span>
                        <span class="pill fs-11 {{ $order->status->tone() }}">{{ $order->status->label() }}</span>
                    </div>
                    <a href="{{ route('orders.show', $order) }}" class="ap-icon-btn" data-tip="Detail"><span class="ms ms-md">chevron_right</span></a>
                </li>
            @empty
                <li class="d-block border-0 p-0">
                    <x-ui.empty-state icon="receipt_long" title="Belum ada transaksi" description="Pesanan yang kamu buat akan tampil di sini.">
                        <a href="{{ route('products.index') }}" class="btn-ap btn-mint justify-content-center">Mulai Belanja</a>
                    </x-ui.empty-state>
                </li>
            @endforelse
        </ul>
    </div>

    @if ($orders->hasPages())
        <div class="d-flex justify-content-center">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
