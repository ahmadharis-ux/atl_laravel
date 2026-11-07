@forelse ($orders as $order)
    @php
        $source = match ($order->source) {
            'api' => ['API reseller', 'bg-gold tx-gold'],
            'store_cash' => ['Toko · Tunai customer', 'bg-sky-l tx-navy'],
            'store_qris' => ['Toko · QRIS customer', 'bg-mint-l tx-mintt'],
            default => ['Lainnya', 'bg-glass tx-mute'],
        };
    @endphp
    <tr>
        <td>
            <div class="fw-semibold">{{ $order->order_number }}</div>
            <div class="tx-mute fs-11 text-truncate" style="max-width:14rem">{{ $order->product?->name ?? 'Produk tidak tersedia' }}</div>
        </td>
        <td><span class="pill fs-11 {{ $source[1] }}">{{ $source[0] }}</span></td>
        <td class="fs-12">{{ $order->target }}@if ($order->target_zone) · {{ $order->target_zone }}@endif</td>
        <td class="num text-end fw-semibold">Rp {{ number_format((int) $order->amount, 0, ',', '.') }}</td>
        <td><span class="pill fs-11 {{ $order->status->tone() }}">{{ $order->status->label() }}</span></td>
        <td class="tx-mute fs-12 text-nowrap">{{ $order->created_at->locale('id')->translatedFormat('d M Y, H:i') }}</td>
        <td class="text-end">
            <a href="{{ route('settings.reseller.store.transactions.show', $order) }}" class="ap-icon-btn" data-tip="Lihat detail"><span class="ms ms-md">chevron_right</span></a>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7">
            <x-ui.empty-state icon="receipt_long" title="Belum ada transaksi" description="Pesanan API dan transaksi dari toko hosted akan tampil di sini." />
        </td>
    </tr>
@endforelse
