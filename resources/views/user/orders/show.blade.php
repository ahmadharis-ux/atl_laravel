@extends('layouts.app')

@section('title', 'Detail Pesanan '.$order->order_number.' - Atlantic Pedia Tab')

@section('content')
@php
    $product = $order->product;
    $targetLabel = \App\Support\OrderTarget::label($product?->category?->slug);
    $method = $order->payment_method;
    $channelLabel = $method?->channelLabel($order->payment_channel);
    $pending = $order->status === \App\Enums\OrderStatus::Pending;
    $processing = $order->status === \App\Enums\OrderStatus::Processing;
    $midtrans = $order->metadata['midtrans'] ?? [];
    $qrUrl = $midtrans['qr_url'] ?? null;
@endphp

<div class="d-flex flex-column gap-3"
     @if ($pending || $processing) data-poll-url="{{ route('orders.show', $order) }}" data-poll-status="{{ $order->status->value }}" data-poll-interval="4000" @endif>

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('orders.index') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-5 m-0">Detail Pesanan</h1>
            <p class="tx-mute fs-12 m-0">{{ $order->order_number }}</p>
        </div>
        <a href="{{ route('orders.invoice', $order) }}" target="_blank" rel="noopener" class="ap-icon-btn ms-auto" data-tip="Cetak Invoice"><span class="ms ms-md">receipt_long</span></a>
        <span class="pill {{ $order->status->tone() }}">{{ $order->status->label() }}</span>
    </div>

    @if (session('status'))
        <div class="alert-ap r-sm bg-mint-l tx-mintt d-flex align-items-center gap-2">
            <span class="ms">check_circle</span>
            <span class="fs-13 fw-semibold">{{ session('status') }}</span>
        </div>
    @endif

    @if ($pending)
        <div class="alert-ap r-sm bg-glass d-flex align-items-center gap-2">
            <span class="ms tx-teal">autorenew</span>
            <span class="fs-13">Halaman ini menyegarkan otomatis setelah pembayaran diterima.</span>
        </div>

        <x-ui.card title="Selesaikan Pembayaran">
            <div class="d-flex flex-column align-items-center gap-2 text-center">
                <span class="ibox ibox-48 bg-mint-l tx-mintt"><span class="ms">{{ $method?->icon() }}</span></span>
                @if ($method === \App\Enums\PaymentMethod::Qris)
                    @if ($qrUrl)
                        <img src="{{ $qrUrl }}" alt="QRIS" class="r-sm bg-white p-2" style="width:220px;height:220px;object-fit:contain">
                    @else
                        <div class="r-sm bg-glass d-flex align-items-center justify-content-center" style="width:140px;height:140px">
                            <span class="ms" style="font-size:64px">qr_code_2</span>
                        </div>
                    @endif
                @endif
                <div class="num fw-bold fs-4 tx-tealc">{{ $order->payment_code }}</div>
                <p class="fs-12 tx-mute m-0">{{ $method?->label() }}@if ($channelLabel) &middot; {{ $channelLabel }}@endif</p>
                <p class="fs-12 tx-mute m-0">Bayar tepat <b class="tx-tealt">Rp {{ number_format($order->amount, 0, ',', '.') }}</b>. Status pesanan akan otomatis diperbarui setelah pembayaran diterima.</p>

                @if ($method === \App\Enums\PaymentMethod::Qris)
                    <div class="d-flex flex-wrap gap-2 justify-content-center w-100 mt-1">
                        @if ($qrUrl)
                            <a href="{{ route('orders.qris', $order) }}" class="btn-ap btn-ap-outline flex-fill" style="min-width:9rem">
                                <span class="ms ms-sm align-middle me-1">download</span>Unduh QRIS
                            </a>
                        @endif
                        <form method="POST" action="{{ route('orders.sync', $order) }}" class="d-flex flex-fill m-0" style="min-width:9rem">
                            @csrf
                            <button type="submit" class="btn-ap btn-mint flex-fill justify-content-center w-100">
                                <span class="ms ms-sm align-middle me-1">refresh</span>Cek Status
                            </button>
                        </form>
                    </div>
                    <p class="fs-11 tx-mute m-0 mt-1">Sudah membayar? Tekan "Cek Status" untuk memperbarui pembayaran secara manual.</p>
                @endif
            </div>
        </x-ui.card>
    @endif

    @if ($processing)
        <div class="alert-ap r-sm bg-glass d-flex align-items-center gap-2">
            <span class="ms tx-teal">autorenew</span>
            <span class="fs-13">Pesanan kamu sedang diproses. Halaman ini menyegarkan otomatis saat pesanan selesai.</span>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-7">
            <x-ui.card title="Produk">
                <div class="fw-bold">{{ $product?->name ?? 'Produk tidak tersedia' }}</div>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-1">
                    @if ($product)
                        <span class="pill bg-tealc-m tx-tealt">{{ $product->code }}</span>
                    @endif
                    @if ($product?->brand)
                        <span class="pill bg-glass tx-mute">{{ $product->brand->name }}</span>
                    @endif
                    @if ($product?->category)
                        <span class="pill bg-glass tx-mute">{{ $product->category->name }}</span>
                    @endif
                </div>
                @if ($product && $product->status)
                    @php
                        $reorderQuery = array_filter([
                            'target' => $order->target,
                            'zone' => $order->target_zone,
                        ], fn ($value) => $value !== null && $value !== '');
                        $reorderUrl = route('checkout.show', $product).($reorderQuery !== [] ? '?'.http_build_query($reorderQuery) : '');
                    @endphp
                    <a href="{{ $reorderUrl }}" class="btn-ap btn-mint justify-content-center w-100 mt-3">
                        <span class="ms ms-sm align-middle me-1" aria-hidden="true">refresh</span>Beli Lagi
                    </a>
                @endif
            </x-ui.card>
        </div>

        <div class="col-lg-5">
            <x-ui.card title="Data Tujuan">
                @include('user.product._field', ['label' => $targetLabel, 'value' => $order->target])
                @if ($order->target_zone)
                    @include('user.product._field', ['label' => 'Zone ID', 'value' => $order->target_zone])
                @endif
            </x-ui.card>
        </div>
    </div>

    <x-ui.card title="Pembayaran">
        <ul class="list-unstyled m-0 d-flex flex-column gap-2 fs-13">
            <li class="d-flex align-items-center justify-content-between">
                <span class="tx-mute">Metode</span>
                <span class="fw-semibold">{{ $method?->label() }}@if ($channelLabel) &middot; {{ $channelLabel }}@endif</span>
            </li>
            @if ($order->payment_code)
                <li class="d-flex align-items-center justify-content-between">
                    <span class="tx-mute">Kode Pembayaran</span>
                    <span class="num fw-semibold">{{ $order->payment_code }}</span>
                </li>
            @endif
            <li class="d-flex align-items-center justify-content-between">
                <span class="tx-mute">Waktu pesan</span>
                <span class="fw-semibold">{{ $order->created_at->format('d M Y, H:i') }}</span>
            </li>
            @if ($order->paid_at)
                <li class="d-flex align-items-center justify-content-between">
                    <span class="tx-mute">Dibayar</span>
                    <span class="fw-semibold">{{ $order->paid_at->format('d M Y, H:i') }}</span>
                </li>
            @endif
            @if ($order->provider_sn)
                <li class="d-flex align-items-center justify-content-between">
                    <span class="tx-mute">Serial Number</span>
                    <span class="num fw-semibold">{{ $order->provider_sn }}</span>
                </li>
            @endif
            <li class="d-flex align-items-center justify-content-between pt-2 border-top">
                <span class="fw-bold">Total</span>
                <span class="num fw-bold tx-tealt">Rp {{ number_format($order->amount, 0, ',', '.') }}</span>
            </li>
        </ul>
    </x-ui.card>
</div>
@endsection
