@extends('layouts.app')

@section('title', 'Checkout '.$product->name.' - Atlantic Pedia Tab')

@section('content')
@php
    $qrisSelected = $selectedMethod === \App\Enums\PaymentMethod::Qris->value;
    $total = $product->selling_price + ($qrisSelected ? $qrisFee : 0);
    $sisaSaldo = $balance - $product->selling_price;
    $saldoCukup = $sisaSaldo >= 0;
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
                            @error('target')<div class="err show" id="target-error">{{ $message }}</div>@enderror
                            @error('zone')<div class="err show" id="zone-error">{{ $message }}</div>@enderror
                        @else
                            <x-ui.input
                                name="target"
                                :label="$targetLabel.' *'"
                                :placeholder="$targetPlaceholder"
                                :value="old('target', $target)"
                                hint="Wajib diisi. Pastikan data tujuan sudah benar sebelum melanjutkan."
                                required
                                aria-required="true"
                                autocomplete="off"
                                autofocus
                            />
                            @if ($requiresZone)
                                <x-ui.input
                                    name="zone"
                                    label="Zone ID *"
                                    placeholder="Masukkan Zone ID"
                                    hint="Wajib diisi untuk produk ini."
                                    required
                                    aria-required="true"
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
                                :label="$method->label().' — '.($val === 'saldo' ? 'Dipotong dari saldo saat pesanan dibuat.' : 'Scan QRIS dan selesaikan pembayaran dalam 3 jam.')"
                                :checked="$selectedMethod === $method->value"
                            />
                        @endforeach

                        <div data-payment-channel="va" class="mt-1 d-none">
                        </div>

                        <div data-payment-channel="ewallet" class="mt-1 d-none">
                        </div>

                        @error('payment_method')<div class="err show" id="payment_method-error">{{ $message }}</div>@enderror
                        @error('master_bank_id')<div class="err show" id="master_bank_id-error">{{ $message }}</div>@enderror
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
                        <li @class(['d-flex align-items-center justify-content-between' => true, 'd-none' => ! $qrisSelected]) data-fee-row data-method="qris">
                            <span class="text-white">Biaya admin QRIS (0,7%)</span>
                            <span class="num fw-semibold">Rp {{ number_format($qrisFee, 0, ',', '.') }}</span>
                        </li>
                        <li class="d-flex align-items-center justify-content-between pt-2 border-top">
                            <span class="fw-bold">Total</span>
                            <span
                                class="num fw-bold tx-tealc"
                                data-checkout-total
                                data-base-amount="{{ $product->selling_price }}"
                                data-qris-fee="{{ $qrisFee }}"
                            >Rp {{ number_format($total, 0, ',', '.') }}</span>
                        </li>
                    </ul>

                    <div data-checkout-saldo class="d-none flex-column gap-2">
                        <div class="d-flex align-items-center justify-content-between fs-12 tx-skyd border-top pt-2">
                            <span>Saldo kamu</span>
                            <span class="num">Rp {{ number_format($balance, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between fs-12 {{ $saldoCukup ? 'tx-skyd' : 'tx-err' }}">
                            <span>Sisa setelah transaksi</span>
                            <span class="num" data-sisa-saldo>Rp {{ number_format($sisaSaldo, 0, ',', '.') }}</span>
                        </div>
                        <div
                            class="fs-12 {{ $saldoCukup ? 'tx-mintt' : 'tx-err' }}"
                            role="status"
                            aria-live="polite"
                            data-checkout-saldo-status
                            data-sufficient="{{ $saldoCukup ? '1' : '0' }}"
                        >
                            <span data-saldo-sufficient-message @class(['d-none' => ! $saldoCukup])>
                                Saldo cukup untuk membayar pesanan ini.
                            </span>
                            <span data-saldo-insufficient-message @class(['d-none' => $saldoCukup])>
                                Saldo kurang untuk membayar pesanan ini.
                            </span>
                        </div>
                        <a
                            href="{{ route('deposits.create') }}"
                            class="btn-ap btn-mint justify-content-center{{ $saldoCukup ? ' d-none' : '' }}"
                            data-checkout-topup
                        >
                            <span class="ms">add</span>Isi Saldo
                        </a>
                    </div>
                </div>

                <span class="fs-12 tx-mute">Pembayaran Saldo langsung memotong saldo akun saat pesanan dibuat. Pembayaran QRIS menunggu pembayaran dan tidak memotong saldo akun.</span>
            </div>
        </div>
    </div>
</div>
@endsection
