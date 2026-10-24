@php
    $product = $order->product;
    $targetLabel = \App\Support\OrderTarget::label($product?->category?->slug);
    $method = $order->payment_method;
    $channelLabel = $method?->channelLabel($order->payment_channel);
    $rupiah = fn (int|float $n): string => 'Rp '.number_format((float) $n, 0, ',', '.');
    $meta = collect([$product?->code, $product?->brand?->name, $product?->category?->name])->filter()->implode(' · ');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $order->order_number }} - Atlantic Pedia</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('assets/css/atlantic.css') }}?v={{ filemtime(public_path('assets/css/atlantic.css')) }}" rel="stylesheet">
    <link rel="icon" href="{{ asset('assets/images/logo.png') }}">

    <style>
        body { background: var(--bg); }
        .invoice-doc { max-width: 820px; margin: 0 auto; background: #fff; overflow: hidden; }
        .invoice-head { background: linear-gradient(135deg, var(--navy), var(--deep)); }
        .invoice-head .brand-sub { color: var(--tealc); }
        .invoice-title { color: #fff; letter-spacing: .04em; }
        .invoice-thead { background: rgba(159,240,247,.28); color: var(--navy); }
        @media print {
            @page { size: A4; margin: 12mm; }
            body { background: #fff; }
            .no-print { display: none !important; }
            .invoice-doc { max-width: none; box-shadow: none !important; border-radius: 0 !important; }
        }
    </style>
</head>
<body class="py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3 no-print flex-wrap gap-2">
            <a href="{{ $backUrl }}" class="btn-ap btn-glass">
                <span class="ms ms-sm align-middle me-1">arrow_back</span>Kembali
            </a>
            <a href="{{ $pdfUrl }}" class="btn-ap btn-navy">
               Download PDF
            </a>
        </div>

        <div class="invoice-doc r-card shadow-sm">
            <div class="invoice-head d-flex justify-content-between align-items-start gap-3 flex-wrap p-4">
                <div class="d-flex align-items-center gap-3">
                    <img src="{{ asset('assets/images/logo_with_right_text.png') }}" alt="Atlantic Pedia" style="height:42px">
                    <div>
                        <div class="fw-bold fs-4 text-white lh-1">Atlantic Pedia</div>
                        <div class="brand-sub fs-12 mt-1">Atlantic Pedia Tab</div>
                    </div>
                </div>
                <div class="text-end text-white">
                    <div class="invoice-title fw-bold fs-3 lh-1">INVOICE</div>
                    <div class="fs-12 mt-2 opacity-75">No. {{ $order->order_number }}</div>
                    <div class="fs-12 opacity-75">{{ $order->created_at->locale('id')->translatedFormat('d F Y, H:i') }}</div>
                    <span class="pill mt-2 {{ $order->status->tone() }}">{{ $order->status->label() }}</span>
                </div>
            </div>

            <div class="p-4">
                <div class="row g-3 fs-13">
                    <div class="col-sm-6">
                        <div class="tx-mute fs-11 text-uppercase fw-semibold mb-1">Ditagihkan kepada</div>
                        <div class="fw-semibold">{{ $order->user?->name ?? '-' }}</div>
                        <div class="tx-mute fs-12">{{ $order->user?->email ?? '-' }}</div>
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        <div class="tx-mute fs-11 text-uppercase fw-semibold mb-1">Pembayaran</div>
                        <div class="fw-semibold">{{ $method?->label() }}@if ($channelLabel) &middot; {{ $channelLabel }}@endif</div>
                        <div class="tx-mute fs-12">{{ $order->paid_at ? 'Dibayar '.$order->paid_at->locale('id')->translatedFormat('d F Y, H:i') : 'Belum dibayar' }}</div>
                    </div>
                </div>

                <div class="table-responsive mt-4">
                    <table class="table table-ui align-middle m-0 fs-13">
                        <thead class="invoice-thead">
                            <tr>
                                <th>Produk</th>
                                <th>Tujuan</th>
                                <th class="text-end">Harga</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $product?->name ?? 'Produk dihapus' }}</div>
                                    @if ($meta !== '')
                                        <div class="tx-mute fs-11">{{ $meta }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $order->target }}</div>
                                    <div class="tx-mute fs-11">{{ $targetLabel }}@if ($order->target_zone) &middot; Zone {{ $order->target_zone }}@endif</div>
                                </td>
                                <td class="text-end num">{{ $rupiah($order->amount) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <div style="min-width: 260px">
                        <div class="d-flex justify-content-between fs-13 py-1">
                            <span class="tx-mute">Subtotal</span>
                            <span class="num">{{ $rupiah($order->amount) }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 mt-1 border-top">
                            <span class="fw-bold">Total</span>
                            <span class="num fw-bold tx-tealt fs-5">{{ $rupiah($order->amount) }}</span>
                        </div>
                    </div>
                </div>

                @if ($order->provider_sn)
                    <div class="tx-mute fs-12 mt-3">Serial Number: <span class="num fw-semibold">{{ $order->provider_sn }}</span></div>
                @endif

                <div class="border-top mt-4 pt-3 tx-mute fs-11 d-flex justify-content-between flex-wrap gap-2">
                    <span>Terima kasih telah berbelanja di Atlantic Pedia.</span>
                    <span>Dicetak {{ now()->locale('id')->translatedFormat('d F Y, H:i') }}</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
