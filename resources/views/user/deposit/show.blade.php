@extends('layouts.app')

@section('title', 'Top Up '.$deposit->reference.' - Atlantic Pedia Tab')

@section('content')
@php
    $method = $deposit->payment_method;
    $masterBank = $deposit->masterBank;
    $channelLabel = $masterBank?->bank_name ?? $method?->channelLabel($deposit->payment_channel);
    $pending = $deposit->status === \App\Enums\DepositStatus::Pending;
    $paid = $deposit->status === \App\Enums\DepositStatus::Paid;
    $midtrans = $deposit->metadata['midtrans'] ?? [];
    $qrUrl = $midtrans['qr_url'] ?? null;
    $qrString = $midtrans['qr_string'] ?? null;
    $isQris = $method === \App\Enums\PaymentMethod::Qris;
@endphp

<div class="d-flex flex-column gap-3">

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('saldo.index') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-5 m-0">Top Up Saldo</h1>
            <p class="tx-mute fs-12 m-0">{{ $deposit->reference }}</p>
        </div>
        <span class="pill ms-auto {{ $deposit->status->tone() }}">{{ $deposit->status->label() }}</span>
    </div>

    @if (session('status'))
        <div class="alert-ap r-sm bg-mint-l tx-mintt d-flex align-items-center gap-2">
            <span class="ms">check_circle</span>
            <span class="fs-13 fw-semibold">{{ session('status') }}</span>
        </div>
    @endif

    @if ($pending)
        <x-ui.card title="Instruksi Pembayaran">
            @if ($isQris && $qrUrl)
                <div class="d-flex flex-column align-items-center gap-2 text-center">
                    <img src="{{ $qrUrl }}" alt="QRIS" class="r-sm bg-white p-2" style="width:220px;height:220px;object-fit:contain">
                    <div class="num fw-bold fs-4 tx-tealc">Rp {{ number_format($deposit->amount, 0, ',', '.') }}</div>
                    <p class="fs-12 tx-mute m-0">Pindai kode QR di atas dengan aplikasi pembayaran apa pun.</p>
                    @if ($deposit->expires_at)
                        <p class="fs-12 tx-skyd m-0">Berlaku sampai {{ $deposit->expires_at->locale('id')->translatedFormat('d M Y, H:i') }}.</p>
                    @endif
                </div>
            @elseif ($masterBank)
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="ibox ibox-40 bg-mint-l tx-mintt"><span class="ms">{{ $method?->icon() }}</span></span>
                        <div>
                            <div class="fw-semibold fs-13">{{ $masterBank->bank_name }}</div>
                            <div class="tx-mute fs-12">{{ $masterBank->type === \App\Models\MasterBank::TYPE_EWALLET ? 'E-Wallet tujuan' : 'Bank tujuan' }}</div>
                        </div>
                    </div>
                    <ul class="list-unstyled m-0 d-flex flex-column gap-2 fs-13">
                        <li class="d-flex align-items-center justify-content-between">
                            <span class="tx-mute">Nomor tujuan</span>
                            <span class="num fw-semibold">{{ $masterBank->account_number }}</span>
                        </li>
                        <li class="d-flex align-items-center justify-content-between">
                            <span class="tx-mute">Atas nama</span>
                            <span class="fw-semibold">{{ $masterBank->account_holder }}</span>
                        </li>
                    <li class="d-flex align-items-center justify-content-between pt-2 border-top">
                        <span class="fw-bold">Jumlah transfer</span>
                        <span class="num fw-bold tx-tealc">Rp {{ number_format($deposit->amount, 0, ',', '.') }}</span>
                    </li>
                </ul>
                    @if ($deposit->expires_at)
                        <p class="fs-12 tx-skyd m-0">Transfer sebelum {{ $deposit->expires_at->locale('id')->translatedFormat('d M Y, H:i') }}.</p>
                    @endif
                </div>
            @else
                <div class="d-flex flex-column align-items-center gap-2 text-center">
                    <span class="ibox ibox-48 bg-mint-l tx-mintt"><span class="ms">{{ $method?->icon() }}</span></span>
                    @if ($isQris)
                        <div class="r-sm bg-glass d-flex align-items-center justify-content-center" style="width:140px;height:140px">
                            <span class="ms" style="font-size:64px">qr_code_2</span>
                        </div>
                    @endif
                    <div class="num fw-bold fs-4 tx-tealc">{{ $deposit->payment_code }}</div>
                    <p class="fs-12 tx-mute m-0">{{ $method?->label() }}@if ($channelLabel) &middot; {{ $channelLabel }}@endif</p>
                    <p class="fs-12 tx-skyd m-0">Bayar tepat <b>Rp {{ number_format($deposit->amount, 0, ',', '.') }}</b>@if ($deposit->expires_at) sebelum {{ $deposit->expires_at->locale('id')->translatedFormat('d M Y, H:i') }}@endif.</p>
                </div>
            @endif
        </x-ui.card>

        @if ($isQris && ! empty($midtrans) && ! config('midtrans.is_production'))
            <x-ui.card title="Data Testing (Sandbox Midtrans)">
                <div class="d-flex flex-column gap-3">
                    <p class="fs-12 tx-mute m-0">
                        Salin nilai di bawah untuk uji di
                        <a href="https://simulator.sandbox.midtrans.com/v2/qris/index" target="_blank" rel="noopener" class="tx-skyd fw-semibold">QRIS Simulator</a>.
                    </p>
                    <x-ui.input name="test_order_id" label="Order ID" :value="$deposit->reference" readonly />
                    @if ($qrUrl)
                        <x-ui.input name="test_qr_url" label="QR Image URL" :value="$qrUrl" readonly />
                    @endif
                    @if ($qrString)
                        <x-ui.textarea name="test_qr_string" label="QR String" :value="$qrString" readonly rows="3" />
                    @endif
                </div>
            </x-ui.card>
        @endif

        @if (! $midtransEnabled || $method !== \App\Enums\PaymentMethod::Qris)
            <x-ui.card title="Mode Development">
                <div class="d-flex flex-column gap-2">
                    <p class="fs-12 tx-mute m-0">Belum ada payment gateway. Tekan tombol di bawah untuk menyimulasikan pembayaran dan menambah saldo.</p>
                    <form method="POST" action="{{ route('deposits.pay', $deposit) }}" id="deposit-pay-form">
                        @csrf
                        <button
                            type="submit"
                            class="btn-ap btn-mint justify-content-center w-100"
                            data-confirm="Saldo akan ditambah Rp {{ number_format($deposit->amount, 0, ',', '.') }}. Lanjutkan?"
                            data-confirm-title="Simulasi Pembayaran"
                            data-confirm-ok="Ya, Bayar"
                            data-submit-form="deposit-pay-form"
                        >Simulasikan Pembayaran</button>
                    </form>
                </div>
            </x-ui.card>
        @endif
    @elseif ($paid)
        <x-ui.card>
            <div class="d-flex flex-column align-items-center gap-2 text-center">
                <span class="ibox ibox-48 bg-mint-l tx-mintt"><span class="ms">check_circle</span></span>
                <div class="num fw-bold fs-4 tx-tealc">+ Rp {{ number_format($deposit->amount, 0, ',', '.') }}</div>
                <p class="fs-13 tx-mute m-0">Top up berhasil. Saldo kamu sudah bertambah.</p>
                <a href="{{ route('saldo.index') }}" class="btn-ap btn-mint justify-content-center">Lihat Saldo</a>
            </div>
        </x-ui.card>
    @else
        <x-ui.card title="Top Up Tidak Selesai">
            <div class="d-flex flex-column align-items-center gap-2 text-center">
                <span class="ibox ibox-48 bg-glass tx-mute"><span class="ms">{{ $deposit->status->icon() }}</span></span>
                <p class="fs-13 tx-mute m-0">{{ $deposit->status->label() }}. Silakan buat permintaan top up baru.</p>
                <a href="{{ route('deposits.create') }}" class="btn-ap btn-mint justify-content-center">Buat Top Up Baru</a>
            </div>
        </x-ui.card>
    @endif

    <x-ui.card title="Rincian Top Up">
        <ul class="list-unstyled m-0 d-flex flex-column gap-2 fs-13">
            <li class="d-flex align-items-center justify-content-between">
                <span class="tx-mute">Referensi</span>
                <span class="fw-semibold">{{ $deposit->reference }}</span>
            </li>
            <li class="d-flex align-items-center justify-content-between">
                <span class="tx-mute">Metode</span>
                <span class="fw-semibold">{{ $method?->label() }}@if ($channelLabel) &middot; {{ $channelLabel }}@endif</span>
            </li>
            <li class="d-flex align-items-center justify-content-between">
                <span class="tx-mute">Dibuat</span>
                <span class="fw-semibold">{{ $deposit->created_at->format('d M Y, H:i') }}</span>
            </li>
            @if ($deposit->expires_at)
                <li class="d-flex align-items-center justify-content-between">
                    <span class="tx-mute">Kedaluwarsa</span>
                    <span class="fw-semibold">{{ $deposit->expires_at->format('d M Y, H:i') }}</span>
                </li>
            @endif
            @if ($deposit->paid_at)
                <li class="d-flex align-items-center justify-content-between">
                    <span class="tx-mute">Dibayar</span>
                    <span class="fw-semibold">{{ $deposit->paid_at->format('d M Y, H:i') }}</span>
                </li>
            @endif
            <li class="d-flex align-items-center justify-content-between pt-2 border-top">
                <span class="fw-bold">Total</span>
                <span class="num fw-bold tx-tealc">Rp {{ number_format($deposit->amount, 0, ',', '.') }}</span>
            </li>
        </ul>
    </x-ui.card>
</div>
@endsection
