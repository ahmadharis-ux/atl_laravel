@extends('layouts.app')

@section('title', 'Top Up Saldo - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-3">

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('saldo.index') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-4 m-0">Top Up Saldo</h1>
            <p class="tx-mute fs-12 m-0">Isi saldo lewat transfer bank, e-wallet, atau QRIS.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert-ap r-sm bg-mint-l tx-mintt d-flex align-items-center gap-2">
            <span class="ms">info</span>
            <span class="fs-13 fw-semibold">{{ session('status') }}</span>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-7">
            <form method="POST" action="{{ route('deposits.store') }}" data-min-loader="2000" class="d-flex flex-column gap-3">
                @csrf

                <x-ui.card title="Nominal Top Up">
                    <div class="d-flex flex-column gap-2">
                        <x-ui.currency-input
                            name="amount"
                            label="Jumlah"
                            hint="Minimal Rp {{ number_format($minAmount, 0, ',', '.') }} &middot; Maksimal Rp {{ number_format($maxAmount, 0, ',', '.') }}"
                            autofocus
                        />

                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($presets as $preset)
                                <button
                                    type="button"
                                    class="pill bg-tealc-m tx-tealt fw-semibold"
                                    data-amount-set="{{ $preset }}"
                                    data-amount-target="#amount"
                                >Rp {{ number_format($preset, 0, ',', '.') }}</button>
                            @endforeach
                        </div>
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

                        <div data-payment-channel="va ewallet" class="mt-1">
                            <x-ui.select
                                name="payment_channel"
                                label="Pilih Bank / E-Wallet"
                                :options="$channels"
                                placeholder="Pilih salah satu"
                            />
                        </div>

                        @error('payment_method')<div class="err show">{{ $message }}</div>@enderror
                    </div>
                </x-ui.card>

                <div class="d-flex gap-2">
                    <a href="{{ route('saldo.index') }}" class="btn-ap btn-glass flex-fill justify-content-center">Batal</a>
                    <button type="submit" class="btn-ap btn-mint flex-fill justify-content-center"><span class="ms align-middle me-1">add</span>Top Up</button>
                </div>
            </form>
        </div>

        <div class="col-lg-5">
            <div class="d-flex flex-column gap-3">
                <x-ui.card title="Mode Development">
                    <p class="fs-13 tx-mute m-0">
                        Pembayaran belum terhubung ke payment gateway sungguhan. Setelah membuat permintaan top up,
                        kamu bisa menekan tombol <b>Simulasikan Pembayaran</b> untuk menambah saldo secara otomatis.
                    </p>
                </x-ui.card>

                <x-ui.card title="Cara Kerja">
                    <ul class="list-unstyled m-0 d-flex flex-column gap-2 fs-13">
                        <li class="d-flex align-items-start gap-2"><span class="ms ms-sm tx-tealc">looks_one</span><span>Pilih nominal dan metode pembayaran.</span></li>
                        <li class="d-flex align-items-start gap-2"><span class="ms ms-sm tx-tealc">looks_two</span><span>Bayar sesuai kode/instruksi yang muncul.</span></li>
                        <li class="d-flex align-items-start gap-2"><span class="ms ms-sm tx-tealc">looks_3</span><span>Saldo bertambah &amp; siap dipakai transaksi.</span></li>
                    </ul>
                </x-ui.card>

                <span class="fs-12 tx-skyd" style="opacity:.85">Saldo hanya bisa dipakai untuk transaksi di Atlantic Pedia Tab (tidak dapat ditarik tunai).</span>
            </div>
        </div>
    </div>
</div>
@endsection
