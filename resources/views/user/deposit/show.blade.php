@extends('layouts.app')

@section('title', 'Top Up '.$deposit->reference.' - Atlantic Pedia Tab')

@section('content')
@php
    $method = $deposit->payment_method;
    $channelLabel = $method?->channelLabel($deposit->payment_channel);
    $pending = $deposit->status === \App\Enums\DepositStatus::Pending;
    $paid = $deposit->status === \App\Enums\DepositStatus::Paid;
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
        <x-ui.card title="Selesaikan Pembayaran">
            <div class="d-flex flex-column align-items-center gap-2 text-center">
                <span class="ibox ibox-48 bg-mint-l tx-mintt"><span class="ms">{{ $method?->icon() }}</span></span>
                @if ($method === \App\Enums\PaymentMethod::Qris)
                    <div class="r-sm bg-glass d-flex align-items-center justify-content-center" style="width:140px;height:140px">
                        <span class="ms" style="font-size:64px">qr_code_2</span>
                    </div>
                @endif
                <div class="num fw-bold fs-4 tx-tealc">{{ $deposit->payment_code }}</div>
                <p class="fs-12 tx-mute m-0">{{ $method?->label() }}@if ($channelLabel) &middot; {{ $channelLabel }}@endif</p>
                <p class="fs-12 tx-skyd m-0">Bayar tepat <b>Rp {{ number_format($deposit->amount, 0, ',', '.') }}</b>@if ($deposit->expires_at) sebelum {{ $deposit->expires_at->locale('id')->translatedFormat('d M Y, H:i') }}@endif.</p>
            </div>
        </x-ui.card>

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
