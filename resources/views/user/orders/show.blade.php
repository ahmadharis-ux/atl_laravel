@extends('layouts.app')

@section('title', 'Detail Pesanan '.$order->order_number.' - Atlantic Pedia Tab')

@section('content')
@php
    $product = $order->product;
    $targetLabel = \App\Support\OrderTarget::label($product?->category?->slug);
    $method = $order->payment_method;
    $masterBank = $order->masterBank;
    $channelLabel = $masterBank?->bank_name ?? $method?->channelLabel($order->payment_channel);
    $pending = $order->status === \App\Enums\OrderStatus::Pending;
    $processing = $order->status === \App\Enums\OrderStatus::Processing;
    $midtrans = $order->metadata['midtrans'] ?? [];
    $qrUrl = $midtrans['qr_url'] ?? null;
    $fee = (int) $order->fee;
    $totalDue = $order->totalDue();
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
            @if ($masterBank)
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
                            <span class="d-flex align-items-center gap-2">
                                <span class="num fw-semibold">{{ $masterBank->account_number }}</span>
                                <button type="button" class="ap-icon-btn" data-copy="{{ $masterBank->account_number }}" data-copy-label="Nomor tujuan" data-tip="Salin nomor"><span class="ms ms-sm">content_copy</span></button>
                            </span>
                        </li>
                        <li class="d-flex align-items-center justify-content-between">
                            <span class="tx-mute">Atas nama</span>
                            <span class="fw-semibold">{{ $masterBank->account_holder }}</span>
                        </li>
                        <li class="d-flex align-items-center justify-content-between">
                            <span class="tx-mute">Harga produk</span>
                            <span class="num fw-semibold">Rp {{ number_format($order->amount, 0, ',', '.') }}</span>
                        </li>
                        @if ($fee > 0)
                            <li class="d-flex align-items-center justify-content-between">
                                <span class="tx-mute">Biaya admin</span>
                                <span class="num fw-semibold">Rp {{ number_format($fee, 0, ',', '.') }}</span>
                            </li>
                        @endif
                        <li class="d-flex align-items-center justify-content-between pt-2 border-top">
                            <span class="fw-bold">Jumlah transfer</span>
                            <span class="d-flex align-items-center gap-2">
                                <span class="num fw-bold tx-tealt">Rp {{ number_format($totalDue, 0, ',', '.') }}</span>
                                <button type="button" class="ap-icon-btn" data-copy="{{ $totalDue }}" data-copy-label="Total transfer" data-tip="Salin nominal"><span class="ms ms-sm">content_copy</span></button>
                            </span>
                        </li>
                    </ul>
                    @if ($order->expires_at)
                        <p class="fs-12 tx-mute m-0">Transfer sebelum {{ $order->expires_at->locale('id')->translatedFormat('d M Y, H:i') }} <span class="tx-err fw-semibold" data-expiry-timer="{{ $order->expires_at->toIso8601String() }}">(00:00)</span>.</p>
                    @endif
                    <p class="fs-12 tx-mute m-0">Pembayaran akan dikonfirmasi admin setelah dana diterima.</p>
                </div>
            @else
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
                    @if ($order->expires_at)
                        <p class="fs-12 tx-mute m-0">Selesaikan pembayaran sebelum <span class="tx-err fw-semibold" data-expiry-timer="{{ $order->expires_at->toIso8601String() }}">(00:00)</span></p>
                    @endif</p>

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
                    @endif
                </div>
            @endif
        </x-ui.card>
    @endif

    @if ($processing)
        <div class="alert-ap r-sm bg-glass d-flex align-items-center gap-2">
            <span class="ms tx-teal">autorenew</span>
            <span class="fs-13">Pesanan kamu sedang diproses. Halaman ini menyegarkan otomatis saat pesanan selesai.</span>
        </div>
    @endif

    @php
        $helpUrl = in_array($order->status, [\App\Enums\OrderStatus::Failed, \App\Enums\OrderStatus::Expired], true)
            ? \App\Support\SupportWhatsapp::orderIssueUrl($order)
            : null;
    @endphp
    @if ($order->status === \App\Enums\OrderStatus::Failed)
        @php
            $waFailedUrl = \App\Support\SupportWhatsapp::url(
                "Halo Admin/CS Atlantic Pedia, saya ingin melaporkan transaksi yang GAGAL.\n\n".
                "Link Transaksi: ".route('admin.transactions.show', $order)."\n\n".
                "No. Pesanan: ".$order->order_number."\n".
                "Keterangan Aduan: "
            );
        @endphp
        @if ($waFailedUrl)
            <x-ui.card title="Bantuan CS">
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-start gap-2">
                        <span class="ms tx-err">support_agent</span>
                        <div>
                            <div class="fw-semibold">Transaksi gagal</div>
                            <div class="fs-13 tx-mute">Klik tombol di bawah untuk menghubungi CS via WhatsApp. Link transaksi sudah otomatis terlampir, kamu bisa menambahkan keterangan aduan bebas.</div>
                        </div>
                    </div>
                    <a href="{{ $waFailedUrl }}" target="_blank" rel="noopener" class="btn-ap btn-mint flex-shrink-0">
                        <span class="ms ms-sm align-middle me-1">chat</span>Hubungi CS via WhatsApp
                    </a>
                </div>
            </x-ui.card>
        @endif
    @elseif ($helpUrl)
        <div class="alert-ap r-sm {{ $order->status->tone() }} d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="ms">help</span>
                <span class="fs-13">Ada kendala dengan pesanan ini? Hubungi CS dengan detail pesanan yang sudah terisi otomatis.</span>
            </div>
            <x-ui.whatsapp-help :url="$helpUrl" class="btn-sm" />
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-7">
            <x-ui.card title="Produk">
                <div class="fw-bold">{{ $product?->name ?? 'Produk tidak tersedia' }}</div>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-1 mb-2">
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

                <li class="d-flex align-items-center justify-content-between pt-2 border-top mb-2">
                    <span class="fw-bold fs-10">Data Tujuan</span>
                </li>
                @include('user.product._field', ['label' => $targetLabel, 'value' => $order->target])
                @if ($order->target_zone)
                    @include('user.product._field', ['label' => 'Zone ID', 'value' => $order->target_zone])
                @endif
                
            </x-ui.card>
        </div>
        <div class="col-lg-5">
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
                    @if ($fee > 0)
                        <li class="d-flex align-items-center justify-content-between">
                            <span class="tx-mute">Biaya admin</span>
                            <span class="num fw-semibold">Rp {{ number_format($fee, 0, ',', '.') }}</span>
                        </li>
                    @endif
                    <li class="d-flex align-items-center justify-content-between pt-2 border-top">
                        <span class="fw-bold">Total</span>
                        <span class="num fw-bold tx-tealt">Rp {{ number_format($totalDue, 0, ',', '.') }}</span>
                    </li>
                </ul>
            </x-ui.card>
        </div>
    </div>

    
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
@endsection
