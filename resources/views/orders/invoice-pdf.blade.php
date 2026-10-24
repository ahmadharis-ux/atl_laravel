@php
    $product = $order->product;
    $targetLabel = \App\Support\OrderTarget::label($product?->category?->slug);
    $method = $order->payment_method;
    $channelLabel = $method?->channelLabel($order->payment_channel);
    $rupiah = fn (int|float $n): string => 'Rp '.number_format((float) $n, 0, ',', '.');
    $meta = collect([$product?->code, $product?->brand?->name, $product?->category?->name])->filter()->implode(' · ');
    $logoPath = public_path('assets/images/logo_with_right_text.png');
    $logo = is_file($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : null;
    $navy = '#001f3f';
    $deep = '#000f1f';
    $tealt = '#0066e6';
    $tealc = '#7dd3fc';
    $line = '#dbeafe';
    $mute = '#5b7085';
    $bgLight = '#f0f8ff';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->order_number }}</title>
    <style>
        @page { size: A4; margin: 14mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'DejaVu Sans', Arial, sans-serif; color: {{ $navy }}; font-size: 11px; }
        .doc { border: 1px solid {{ $line }}; border-radius: 10px; overflow: hidden; }
        .head { background: {{ $navy }}; color: #ffffff; padding: 18px 22px; width: 100%; }
        .head td { vertical-align: top; color: #ffffff; }
        .brand-name { font-size: 20px; font-weight: bold; line-height: 1.1; color: #ffffff; }
        .brand-sub { color: {{ $tealc }}; font-size: 10px; margin-top: 3px; }
        .inv-title { font-size: 22px; font-weight: bold; letter-spacing: .06em; color: #ffffff; }
        .inv-meta { font-size: 9.5px; color: #d7e6f7; margin-top: 4px; }
        .pill { display: inline-block; padding: 3px 9px; border-radius: 20px; font-size: 9px; font-weight: bold; margin-top: 6px; background: {{ $bgLight }}; color: {{ $navy }}; }
        .body { padding: 20px 22px 18px; }
        .label { font-size: 8.5px; text-transform: uppercase; letter-spacing: .08em; color: {{ $mute }}; font-weight: bold; }
        .value { font-size: 11px; font-weight: bold; color: {{ $navy }}; margin-top: 2px; }
        .sub { font-size: 9.5px; color: {{ $mute }}; margin-top: 1px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 16px; }
        table.items thead th { background: {{ $bgLight }}; color: {{ $navy }}; font-size: 9.5px; text-align: left; padding: 9px 10px; border-bottom: 1px solid {{ $line }}; }
        table.items tbody td { padding: 11px 10px; border-bottom: 1px solid {{ $line }}; vertical-align: top; }
        .right { text-align: right; }
        .center { text-align: center; }
        .totals { width: 270px; margin-top: 14px; }
        .totals td { padding: 5px 0; font-size: 11px; }
        .totals .line-top td { border-top: 1px solid {{ $line }}; padding-top: 8px; }
        .total-label { font-weight: bold; }
        .total-value { font-weight: bold; font-size: 15px; color: {{ $tealt }}; }
        .note { margin-top: 18px; padding-top: 10px; border-top: 1px solid {{ $line }}; font-size: 9px; color: {{ $mute }}; }
        .note td { font-size: 9px; color: {{ $mute }}; }
    </style>
</head>
<body>
    <div class="doc">
        <table class="head" cellpadding="0" cellspacing="0">
            <tr>
                <td width="60%">
                    @if ($logo)
                        <img src="{{ $logo }}" alt="Atlantic Pedia" style="height:40px">
                    @else
                        <div class="brand-name">Atlantic Pedia</div>
                    @endif
                    <div class="brand-sub">Atlantic Pedia Tab</div>
                </td>
                <td width="40%" class="right">
                    <div class="inv-title">INVOICE</div>
                    <div class="inv-meta">No. {{ $order->order_number }}</div>
                    <div class="inv-meta">{{ $order->created_at->locale('id')->translatedFormat('d F Y, H:i') }}</div>
                    <span class="pill">{{ $order->status->label() }}</span>
                </td>
            </tr>
        </table>

        <div class="body">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="50%">
                        <div class="label">Ditagihkan kepada</div>
                        <div class="value">{{ $order->user?->name ?? '-' }}</div>
                        <div class="sub">{{ $order->user?->email ?? '-' }}</div>
                    </td>
                    <td width="50%" class="right">
                        <div class="label">Pembayaran</div>
                        <div class="value">{{ $method?->label() }}@if ($channelLabel) &middot; {{ $channelLabel }}@endif</div>
                        <div class="sub">{{ $order->paid_at ? 'Dibayar '.$order->paid_at->locale('id')->translatedFormat('d F Y, H:i') : 'Belum dibayar' }}</div>
                    </td>
                </tr>
            </table>

            <table class="items" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th width="48%">Produk</th>
                        <th width="34%">Tujuan</th>
                        <th width="18%" class="right">Harga</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="value">{{ $product?->name ?? 'Produk dihapus' }}</div>
                            @if ($meta !== '')
                                <div class="sub">{{ $meta }}</div>
                            @endif
                        </td>
                        <td>
                            <div class="value">{{ $order->target }}</div>
                            <div class="sub">{{ $targetLabel }}@if ($order->target_zone) &middot; Zone {{ $order->target_zone }}@endif</div>
                        </td>
                        <td class="right">{{ $rupiah($order->amount) }}</td>
                    </tr>
                </tbody>
            </table>

            <table class="totals" align="right" cellpadding="0" cellspacing="0">
                <tr>
                    <td>Subtotal</td>
                    <td class="right">{{ $rupiah($order->amount) }}</td>
                </tr>
                <tr class="line-top">
                    <td class="total-label">Total</td>
                    <td class="right total-value">{{ $rupiah($order->amount) }}</td>
                </tr>
            </table>

            @if ($order->provider_sn)
                <div class="sub" style="margin-top:12px">Serial Number: <strong>{{ $order->provider_sn }}</strong></div>
            @endif

            <table class="note" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td>Terima kasih telah berbelanja di Atlantic Pedia.</td>
                    <td class="right">Dicetak {{ now()->locale('id')->translatedFormat('d F Y, H:i') }}</td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
