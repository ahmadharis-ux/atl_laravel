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
            <form method="POST" action="{{ route('checkout.store', $product) }}" class="d-flex flex-column gap-3" data-min-loader="2000">
                @csrf
                <x-ui.card title="Data Tujuan">
                    <div class="d-flex flex-column gap-3">
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

                        @error('product')<div class="err show">{{ $message }}</div>@enderror
                        @error('balance')<div class="err show">{{ $message }}</div>@enderror
                    </div>
                </x-ui.card>

                <x-ui.card title="Metode Pembayaran">
                    <div class="d-flex flex-column gap-2" data-payment-methods>
                        @foreach ($paymentMethods as $method)
                            <x-ui.radio-card
                                name="payment_method"
                                :value="$method->value"
                                :label="$method->label().' — '.$method->description()"
                                :checked="$selectedMethod === $method->value"
                            />
                        @endforeach

                        <div data-payment-channel="va" class="mt-1">
                            @if ($bankTargets->isEmpty())
                                <div class="alert-ap r-sm bg-errc-l tx-err fs-12 px-3 py-2">Belum ada rekening bank tujuan. Silakan hubungi admin.</div>
                            @else
                                <x-ui.select
                                    name="master_bank_id"
                                    id="master_bank_id_bank"
                                    label="Pilih Bank Tujuan"
                                    :options="$bankTargets->mapWithKeys(fn ($bank) => [$bank->getKey() => $bank->bank_name.' — '.$bank->account_number.($bank->admin_fee > 0 ? ' (biaya admin Rp '.number_format($bank->admin_fee, 0, ',', '.').')' : '')])->all()"
                                    placeholder="Pilih bank tujuan"
                                />
                            @endif
                        </div>

                        <div data-payment-channel="ewallet" class="mt-1">
                            @if ($ewalletTargets->isEmpty())
                                <div class="alert-ap r-sm bg-errc-l tx-err fs-12 px-3 py-2">Belum ada e-wallet tujuan. Silakan hubungi admin.</div>
                            @else
                                <x-ui.select
                                    name="master_bank_id"
                                    id="master_bank_id_ewallet"
                                    label="Pilih E-Wallet Tujuan"
                                    :options="$ewalletTargets->mapWithKeys(fn ($wallet) => [$wallet->getKey() => $wallet->bank_name.' — '.$wallet->account_number.($wallet->admin_fee > 0 ? ' (biaya admin Rp '.number_format($wallet->admin_fee, 0, ',', '.').')' : '')])->all()"
                                    placeholder="Pilih e-wallet tujuan"
                                />
                            @endif
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
                            <span class="pill bg-tealc-m tx-tealt">{{ $product->code }}</span>
                            @if ($product->brand)
                                <span class="pill bg-glass tx-mute">{{ $product->brand->name }}</span>
                            @endif
                        </div>
                    </div>

                    <ul class="list-unstyled m-0 d-flex flex-column gap-2 fs-13">
                        <li class="d-flex align-items-center justify-content-between">
                            <span class="text-white">Harga produk</span>
                            <span class="num fw-semibold">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</span>
                        </li>
                        <li class="d-flex align-items-center justify-content-between">
                            <span class="text-white">Biaya admin</span>
                            <span class="num fw-semibold">{{ $adminFee > 0 ? 'Rp '.number_format($adminFee, 0, ',', '.') : 'Gratis' }}</span>
                        </li>
                        <li class="d-flex align-items-center justify-content-between pt-2 border-top">
                            <span class="fw-bold">Total</span>
                            <span class="num fw-bold tx-tealc">Rp {{ number_format($total, 0, ',', '.') }}</span>
                        </li>
                    </ul>

                    <div class="d-flex align-items-center justify-content-between fs-12 tx-skyd border-top pt-2">
                        <span>Saldo kamu</span>
                        <span class="num">Rp {{ number_format($balance, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between fs-12 {{ $sisaSaldo < 0 ? 'tx-err' : 'tx-skyd' }}">
                        <span>Sisa setelah transaksi</span>
                        <span class="num">Rp {{ number_format($sisaSaldo, 0, ',', '.') }}</span>
                    </div>
                </div>

                <span class="fs-12 tx-mute">Saldo hanya terpotong bila kamu memilih metode Saldo.</span>
            </div>
        </div>
    </div>
</div>
@endsection
