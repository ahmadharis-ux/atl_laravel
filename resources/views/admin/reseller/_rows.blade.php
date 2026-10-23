@forelse ($items as $item)
    <tr>
        <td>
            <div class="fw-semibold">{{ $item->name }}</div>
            <div class="tx-mute fs-12">{{ $item->email }}</div>
        </td>
        <td class="tx-mute fs-12 text-nowrap">
            {{ $item->reseller_since?->locale('id')->translatedFormat('d M Y') }}
        </td>
        <td>
            @if ($item->is_reseller)
                <span class="pill bg-mint-l tx-mintt">Aktif</span>
            @else
                <span class="pill bg-errc-l tx-err">Tidak Aktif</span>
            @endif
        </td>
        <td class="num">{{ number_format($item->orders_count, 0, ',', '.') }}</td>
        <td class="num">{{ number_format($item->api_orders_count, 0, ',', '.') }}</td>
        <td class="num text-end fw-semibold">Rp {{ number_format((int) ($item->api_amount_sum ?? 0), 0, ',', '.') }}</td>
        <td class="num fs-12 tx-mute">{{ $item->reseller_api_key_hint ?? '-' }}</td>
    </tr>
@empty
    <tr>
        <td colspan="7">
            <x-ui.empty-state icon="storefront" title="Belum ada reseller"
                description="Pengguna yang mengaktifkan keanggotaan reseller akan tampil di sini." />
        </td>
    </tr>
@endforelse
