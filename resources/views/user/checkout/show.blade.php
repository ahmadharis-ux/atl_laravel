@extends('layouts.app')

@section('title', 'Checkout '.$product->name.' - Atlantic Pedia Tab')

@section('content')
@php
    $total = $product->selling_price + $adminFee;
    $sisaSaldo = $balance - $total;
@endphp

<div class="d-flex flex-column gap-3">

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('products.show', $product) }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-5 m-0">Checkout</h1>
            <p class="tx-mute fs-12 m-0">Periksa tujuan &amp; nominal sebelum melanjutkan.</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7 order-2 order-lg-1">
            <form method="POST" action="{{ route('checkout.store', $product) }}" class="d-flex flex-column gap-3" data-min-loader="2000" id="checkoutForm" data-checkout-form data-requires-zone="{{ $requiresZone ? '1' : '0' }}">
                @csrf
                <x-ui.card title="Data Tujuan">
                    <div class="d-flex flex-column gap-3">
                        @if (in_array($product->brand?->slug, ['mobile-legends', 'free-fire', 'pubg-mobile', 'aov', 'genshin-impact', 'honkai-star-rail'], true))
                            @include('user.product._nickname', [
                                'brand' => $product->brand->slug,
                                'requiresZone' => $requiresZone,
                                'target' => old('target', $target),
                                'zone' => old('zone', $zone),
                            ])
                            <script>
                                document.addEventListener('DOMContentLoaded', function(){
                                    document.querySelectorAll('input[name="target"], input[name="zone"]').forEach(function(i){ i.readOnly = true; i.setAttribute('readonly','readonly'); });
                                });
                            </script>
                            @error('target')<div class="err show">{{ $message }}</div>@enderror
                            @error('zone')<div class="err show">{{ $message }}</div>@enderror
                        @else
                            <x-ui.input
                                name="target"
                                :label="$targetLabel"
                                :placeholder="$targetPlaceholder"
                                :value="old('target', $target)"
                                autocomplete="off"
                                autofocus
                            />
                            @if ($requiresZone)
                                <x-ui.input
                                    name="zone"
                                    label="Zone ID"
                                    placeholder="Masukkan Zone ID"
                                    inputmode="numeric"
                                    :value="old('zone', $zone)"
                                    autocomplete="off"
                                />
                            @endif
                        @endif

                        @error('product')<div class="err show">{{ $message }}</div>@enderror
                        @error('balance')<div class="err show">{{ $message }}</div>@enderror
                    </div>
                </x-ui.card>

                <x-ui.card title="Metode Pembayaran">
                    <div class="d-flex flex-column gap-2" data-payment-methods>
                        @php($allowed = ['saldo', 'qris'])
                        @foreach ($paymentMethods as $method)
                            @php($val = $method->value)
                            @if (! in_array($val, $allowed, true))
                                @continue
                            @endif
                            <x-ui.radio-card
                                name="payment_method"
                                :value="$method->value"
                                :label="$method->label().' — '.$method->description()"
                                :checked="$selectedMethod === $method->value"
                            />
                        @endforeach

                        <div data-payment-channel="va" class="mt-1 d-none">
                        </div>

                        <div data-payment-channel="ewallet" class="mt-1 d-none">
                        </div>

                        @error('payment_method')<div class="err show">{{ $message }}</div>@enderror
                        @error('master_bank_id')<div class="err show">{{ $message }}</div>@enderror
                    </div>
                </x-ui.card>

                <div class="d-flex gap-2">
                    <a href="{{ route('products.show', $product) }}" class="btn-ap btn-err flex-fill justify-content-center">Batal</a>
                    <button type="submit" class="btn-ap btn-mint flex-fill justify-content-center"><span class="ms align-middle me-1">shopping_cart</span>Buat Pesanan</button>
                </div>
            </form>
        </div>

        <div class="col-lg-5 order-1 order-lg-2">
            <div class="d-flex flex-column gap-3">
                <div class="balance r-card d-flex flex-column gap-3">
                    <span class="fs-11 fw-bold text-uppercase tx-tealc" style="letter-spacing:.08em">Ringkasan</span>
                    <div>
                        <div class="fw-bold">{{ $product->name }}</div>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <span class="pill bg-tealc-m tx-white">{{ $product->code }}</span>
                            @if ($product->brand)
                                <span class="pill bg-glass tx-white">{{ $product->brand->name }}</span>
                            @endif
                        </div>
                    </div>

                    <ul class="list-unstyled m-0 d-flex flex-column gap-2 fs-13">
                        <li class="d-flex align-items-center justify-content-between">
                            <span class="text-white">Harga produk</span>
                            <span class="num fw-semibold">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</span>
                        </li>
                        <li class="d-flex align-items-center justify-content-between" data-fee-row data-method="qris">
                            <span class="text-white">Biaya admin QRIS (0,7%)</span>
                            <span class="num fw-semibold">{{ $adminFee > 0 ? 'Rp '.number_format($adminFee, 0, ',', '.') : 'Rp '.number_format($product->selling_price > 0 ? app(\App\Services\MidtransService::class)->qrisFee($product->selling_price) : 0, 0, ',', '.') }}</span>
                        </li>
                        <li class="d-flex align-items-center justify-content-between pt-2 border-top">
                            <span class="fw-bold">Total</span>
                            <span class="num fw-bold tx-tealc">Rp {{ number_format($total, 0, ',', '.') }}</span>
                        </li>
                    </ul>

                    <div data-checkout-saldo class="d-none flex-column gap-2">
                        <div class="d-flex align-items-center justify-content-between fs-12 tx-skyd border-top pt-2">
                            <span>Saldo kamu</span>
                            <span class="num">Rp {{ number_format($balance, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between fs-12 {{ $sisaSaldo < 0 ? 'tx-err' : 'tx-skyd' }}">
                            <span>Sisa setelah transaksi</span>
                            <span class="num" data-sisa-saldo>Rp {{ number_format($sisaSaldo, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <span class="fs-12 tx-mute">Saldo hanya terpotong bila kamu memilih metode Saldo.</span>
            </div>
        </div>
    </div>
</div>
@endsection
