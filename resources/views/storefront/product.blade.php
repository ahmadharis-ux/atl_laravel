@extends('layouts.app')

@section('title', 'Checkout '.$product->name.' - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-3">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('storefront.index', $reseller) }}" class="ap-icon-btn" data-tip="Kembali ke toko">
            <span class="ms ms-md">arrow_back</span>
        </a>
        <div>
            <h1 class="fw-bold fs-5 m-0">Checkout Toko {{ $reseller->name }}</h1>
            <p class="tx-mute fs-12 m-0">Periksa data tujuan dan total pembayaran sebelum membuat pesanan.</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <form method="POST" action="{{ route('storefront.checkout', [$reseller, $product]) }}" class="d-flex flex-column gap-3">
                @csrf
                <input type="hidden" name="storefront_key" value="{{ $storefrontKey }}">

                <x-ui.card title="Data Tujuan">
                    <div class="d-flex flex-column gap-3">
                        <x-ui.input
                            name="target"
                            :label="$targetLabel.' *'"
                            :placeholder="$targetPlaceholder"
                            :value="old('target')"
                            :hint="$targetHint"
                            required
                            aria-required="true"
                            autocomplete="off"
                        />
                        @if ($requiresZone)
                            <x-ui.input
                                name="zone"
                                label="Zone ID *"
                                placeholder="Masukkan Zone ID"
                                hint="Masukkan Zone ID persis seperti yang tampil pada profil game."
                                required
                                aria-required="true"
                                inputmode="numeric"
                                autocomplete="off"
                            />
                        @endif
                        @error('product')<div class="err show">{{ $message }}</div>@enderror
                    </div>
                </x-ui.card>

                <x-ui.card title="Pembayaran">
                    <div class="d-flex flex-column gap-2">
                        @if ($qrisAvailable)
                            <x-ui.radio-card
                                name="payment_method"
                                value="qris"
                                label="QRIS — Bayar melalui halaman aman Midtrans. Biaya QRIS ditambahkan ke total."
                                :checked="old('payment_method', 'qris') === 'qris'"
                            />
                        @else
                            <div class="card-ap r-sm p-3">
                                <div class="fw-semibold fs-12">QRIS sementara tidak tersedia</div>
                                <div class="tx-mute fs-11">Pilih pembayaran tunai kepada reseller.</div>
                            </div>
                        @endif
                        <x-ui.radio-card
                            name="payment_method"
                            value="cash"
                            label="Tunai ke reseller — Serahkan uang langsung kepada reseller. Reseller akan mengonfirmasi penerimaan."
                            :checked="old('payment_method', $qrisAvailable ? 'qris' : 'cash') === 'cash'"
                        />
                        @error('payment_method')<div class="err show">{{ $message }}</div>@enderror
                        @error('storefront_key')<div class="err show">{{ $message }}</div>@enderror
                    </div>
                </x-ui.card>

                <button type="submit" class="btn-ap btn-mint justify-content-center">
                    <span class="ms ms-sm">shopping_cart</span> Buat Pesanan
                </button>
            </form>
        </div>

        <div class="col-lg-5">
            <x-ui.card title="Ringkasan Pesanan">
                <div class="d-flex flex-column gap-3">
                    <div>
                        <div class="fw-bold">{{ $product->name }}</div>
                        <div class="tx-mute fs-12">{{ $product->brand?->name ?? $product->category?->name }}</div>
                    </div>
                    <div class="d-flex justify-content-between gap-3 fs-13">
                        <span class="tx-mute">Harga produk</span>
                        <span class="num fw-semibold">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between gap-3 fs-13">
                        <span class="tx-mute">Biaya QRIS</span>
                        <span class="num fw-semibold">Rp {{ number_format($qrisFee, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between gap-3 pt-2 border-top">
                        <span class="fw-bold">Total QRIS</span>
                        <span class="num fw-bold tx-tealt">Rp {{ number_format($product->selling_price + $qrisFee, 0, ',', '.') }}</span>
                    </div>
                    <div class="card-ap r-sm p-3 fs-11 tx-mute">
                        Untuk pembayaran tunai, harga yang dibayarkan tetap harga produk dan diserahkan langsung kepada reseller.
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>
</div>
@endsection
