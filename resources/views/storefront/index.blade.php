@extends('layouts.app')

@section('title', 'Toko '.$reseller->name.' - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-4">
    <div class="card-ap r-card p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <span class="pill bg-mint-l tx-mintt mb-2">Toko Reseller</span>
            <h1 class="fw-bold fs-4 m-0">{{ $reseller->name }}</h1>
            <p class="tx-mute fs-12 m-0">Pilih produk untuk melanjutkan ke checkout aman Atlantic Pedia.</p>
        </div>
        <span class="ibox ibox-48 bg-sky-l tx-navy"><span class="ms">storefront</span></span>
    </div>

    @if ($products->isEmpty())
        <x-ui.card title="Produk belum tersedia">
            <p class="tx-mute fs-13 mb-0">Toko ini belum memiliki produk yang dapat dipesan saat ini.</p>
        </x-ui.card>
    @else
        <div class="row g-3">
            @foreach ($products as $product)
                <div class="col-12 col-sm-6 col-xl-4">
                    <x-ui.card class="h-100 d-flex flex-column gap-3">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <div class="min-w-0">
                                <h2 class="fw-bold fs-6 m-0">{{ $product->name }}</h2>
                                <p class="tx-mute fs-12 mb-0 mt-1">{{ $product->brand?->name ?? $product->category?->name }}</p>
                            </div>
                            <span class="pill bg-tealc-m tx-tealt">{{ $product->code }}</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between gap-2 mt-auto">
                            <span class="fs-12 tx-mute">Harga pelanggan</span>
                            <span class="fw-bold num">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</span>
                        </div>
                        <a href="{{ route('storefront.product', [$reseller, $product]) }}" class="btn-ap btn-mint justify-content-center">
                            Lihat &amp; pesan <span class="ms ms-sm">arrow_forward</span>
                        </a>
                    </x-ui.card>
                </div>
            @endforeach
        </div>

        {{ $products->links() }}
    @endif

    <p class="tx-mute fs-11 text-center mb-0">Pembayaran dan pemrosesan pesanan ditangani langsung oleh Atlantic Pedia.</p>
</div>
@endsection
