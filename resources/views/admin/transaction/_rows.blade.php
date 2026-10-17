@forelse ($orders as $order)
    <tr>
        <td class="tx-mute fs-12 text-nowrap">{{ $order->created_at->locale('id')->translatedFormat('d M Y H:i') }}</td>
        <td><x-ui.tooltip :text="$order->order_number" class="fw-semibold fs-12">{{ $order->order_number }}</x-ui.tooltip></td>
        <td>
            <div class="fw-semibold fs-12">{{ $order->user?->name ?? '-' }}</div>
            <div class="tx-mute fs-11">{{ $order->user?->email ?? '' }}</div>
        </td>
        <td>
            <div class="fw-semibold">{{ $order->product?->name ?? 'Produk dihapus' }}</div>
            <div class="tx-mute fs-11">{{ $order->product?->brand?->name ?? '' }}</div>
        </td>
        <td class="fw-semibold fs-12">
            {{ $order->target }}
            @if ($order->target_zone)
                <span class="tx-mute">({{ $order->target_zone }})</span>
            @endif
        </td>
        <td>
            <span class="pill bg-tealc-m tx-tealt">{{ $order->payment_method->label() }}</span>
            @if ($order->payment_channel)
                <div class="tx-mute fs-11 text-uppercase">{{ $order->payment_channel }}</div>
            @endif
        </td>
        <td class="text-end fw-semibold">Rp {{ number_format($order->amount, 0, ',', '.') }}</td>
        <td><span class="pill {{ $order->status->tone() }}">{{ $order->status->label() }}</span></td>
        <td class="text-end">
            <x-ui.actions :actions="[
                ['label' => 'Detail', 'icon' => 'visibility', 'href' => route('admin.transactions.show', $order)],
                ['label' => 'Invoice', 'icon' => 'receipt_long', 'href' => route('admin.transactions.invoice', $order), 'target' => '_blank'],
            ]" />
        </td>
    </tr>
@empty
    <tr>
        <td colspan="9" class="text-center tx-mute py-4">Belum ada transaksi yang cocok.</td>
    </tr>
@endforelse
