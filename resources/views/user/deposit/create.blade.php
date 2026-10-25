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
                                    class="amt-chip"
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
                                :label="$method->value === \App\Enums\PaymentMethod::Qris->value ? $method->label().' — '.$method->description().' (Biaya admin 0,7%)' : $method->label().' — '.$method->description()"
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

                        @error('payment_method')<div class="err show" id="payment_method-error">{{ $message }}</div>@enderror
                        @error('master_bank_id')<div class="err show" id="master_bank_id-error">{{ $message }}</div>@enderror
                    </div>
                </x-ui.card>

                <div class="d-flex gap-2">
                    <a href="{{ route('saldo.index') }}" class="btn-ap btn-err flex-fill justify-content-center">Batal</a>
                    <button type="submit" class="btn-ap btn-mint flex-fill justify-content-center"><span class="ms align-middle me-1">add</span>Top Up</button>
                </div>
            </form>
        </div>

        <div class="col-lg-5">
            <div class="d-flex flex-column gap-3">
                <x-ui.card title="Pembayaran Manual">
                    <p class="fs-13 tx-mute m-0">
                        Untuk transfer bank &amp; e-wallet, bayar sesuai nominal ke rekening tujuan lalu tunggu
                        konfirmasi admin. Saldo bertambah setelah pembayaran diverifikasi.
                    </p>
                </x-ui.card>

                <x-ui.card title="Cara Kerja">
                    <ul class="list-unstyled m-0 d-flex flex-column gap-2 fs-13">
                        <li class="d-flex align-items-start gap-2"><span class="ms ms-sm tx-tealc">looks_one</span><span>Pilih nominal dan metode pembayaran.</span></li>
                        <li class="d-flex align-items-start gap-2"><span class="ms ms-sm tx-tealc">looks_two</span><span>Bayar sesuai kode/instruksi yang muncul.</span></li>
                        <li class="d-flex align-items-start gap-2"><span class="ms ms-sm tx-tealc">looks_3</span><span>Saldo bertambah &amp; siap dipakai transaksi.</span></li>
                    </ul>
                </x-ui.card>

                <div class="alert-ap r-sm bg-tealc-m tx-tealt d-flex align-items-start gap-2 px-3 py-2">
                    <span class="ms ms-sm">info</span>
                    <span class="fs-12 fw-semibold">Saldo hanya bisa dipakai untuk transaksi di Atlantic Pedia Tab (tidak dapat ditarik tunai).</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
