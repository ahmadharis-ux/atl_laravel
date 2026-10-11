@extends('layouts.app')

@section('title', $product->name.' - Atlantic Pedia Tab')

@section('content')
@php
    $rupiah = fn (int|float $n): string => 'Rp '.number_format((float) $n, 0, ',', '.');
    $typePill = $product->payment_type === \App\Enums\ProductPaymentType::Prabayar
        ? 'bg-tealc-m tx-tealt'
        : 'bg-sky-l tx-skyd';
    $stockLabel = $product->unlimited_stock
        ? 'Unlimited'
        : number_format($product->stock, 0, ',', '.');
@endphp

<div class="d-flex flex-column gap-3">

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('products.index', ['payment_type' => $product->payment_type->value]) }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-5 m-0">{{ $product->name }}</h1>
            <div class="d-flex align-items-center gap-2 mt-1">
                <span class="pill bg-tealc-m tx-tealt">{{ $product->code }}</span>
                <span class="pill {{ $product->payment_type === \App\Enums\ProductPaymentType::Prabayar ? 'bg-tealc-m tx-tealt' : 'bg-sky-l tx-navy' }}">{{ $product->payment_type->label() }}</span>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7 order-2 order-lg-1">
            <x-ui.card title="Detail Produk">
                <div class="row">
                    <div class="col-sm-6">
                        @include('user.product._field', ['label' => 'Brand', 'value' => $product->brand?->name ?? '-'])
                        @include('user.product._field', ['label' => 'Kategori', 'value' => $product->category?->name ?? '-'])
                        @include('user.product._field', ['label' => 'Tipe', 'value' => $product->productType?->name ?? '-'])
                    </div>
                    <div class="col-sm-6">
                        @include('user.product._field', ['label' => 'Stok', 'value' => $product->unlimited_stock ? 'Unlimited' : number_format($product->stock, 0, ',', '.')])
                        @include('user.product._field', ['label' => 'Kode Produk', 'value' => $product->code])
                    </div>
                </div>
                @if ($product->description)
                    <div class="pt-2 mt-2 border-top">
                        <div class="ui-label">Deskripsi</div>
                        <p class="fs-13 tx-mute m-0">{{ $product->description }}</p>
                    </div>
                @endif
            </x-ui.card>
        </div>

        <div class="col-lg-5 order-1 order-lg-2">
            <div class="balance r-card d-flex flex-column gap-3">
                <span class="fs-11 fw-bold text-uppercase tx-tealc" style="letter-spacing:.08em">Harga</span>
                <div class="d-flex align-items-baseline gap-2">
                    <span class="tx-tealc fw-bold fs-4">Rp</span>
                    <span class="num fw-bold fs-3">{{ number_format($product->selling_price, 0, ',', '.') }}</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="pill bg-glass tx-mint">Stok {{ $product->unlimited_stock ? '∞' : number_format($product->stock, 0, ',', '.') }}</span>
                    @if ($product->max_price)
                        <span class="pill bg-glass tx-skyd">Maks Rp {{ number_format($product->max_price, 0, ',', '.') }}</span>
                    @endif
                </div>
                @if ($canCheckout && $canValidateNickname)
                    <form method="GET" action="{{ route('checkout.show', $product) }}" class="d-flex flex-column gap-3 pt-3 mt-1 border-top">
                        @include('user.product._nickname', ['brand' => $product->brand->slug, 'requiresZone' => $requiresZone, 'target' => '', 'zone' => ''])
                        <button type="submit" class="btn-ap btn-mint justify-content-center"><span class="ms align-middle me-1">shopping_cart</span>Beli Sekarang</button>
                    </form>
                @else
                    @if ($canValidateNickname)
                        <div class="pt-3 mt-1 border-top">
                            @include('user.product._nickname', ['brand' => $product->brand->slug, 'requiresZone' => $requiresZone, 'target' => '', 'zone' => ''])
                        </div>
                    @endif
                    @if ($canCheckout)
                        <a href="{{ route('checkout.show', $product) }}" class="btn-ap btn-mint justify-content-center"><span class="ms align-middle me-1">shopping_cart</span>Beli Sekarang</a>
                    @else
                        <a href="{{ route('pulsa.index', ['tab' => $product->category?->slug ?? 'pulsa']) }}" class="btn-ap btn-mint justify-content-center"><span class="ms align-middle me-1">shopping_cart</span>Beli Sekarang</a>
                    @endif
                @endif
                <span class="fs-12 tx-skyd" style="opacity:.85">Pastikan tujuan &amp; nominal sudah benar sebelum bertransaksi.</span>
            </div>
        </div>
    </div>
</div>
@endsection