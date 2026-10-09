@forelse ($products as $i => $p)
    @php
        $actions = [];
        $actions[] = ['label' => 'Detail', 'icon' => 'visibility', 'href' => route('admin.products.show', $p)];
        if (auth()->user()->can('products.update')) {
            $actions[] = ['label' => 'Ubah', 'icon' => 'edit', 'href' => route('admin.products.edit', $p)];
        }
    @endphp
    <tr>
        <td class="tx-mute fs-12">{{ $products->firstItem() + $i }}</td>
        <td class="fw-semibold fs-12 text-nowrap">{{ $p->code }}</td>
        <td><span class="pill bg-tealc-m tx-tealt">{{ $p->brand?->name ?? '-' }}</span></td>
        <td class="fw-semibold">
            {{ $p->name }}
            @if ($p->description)
                <div class="tx-mute fs-11" style="max-width:16rem"><x-ui.tooltip :text="$p->description" truncate="16rem">{{ $p->description }}</x-ui.tooltip></div>
            @endif
        </td>
        <td class="tx-mute fs-12">{{ $p->category?->name ?? '-' }}</td>
        <td class="text-end tx-mute">Rp {{ number_format($p->cost_price, 0, ',', '.') }}</td>
        <td class="text-end fs-12">
            @if ($p->markup_type === \App\Enums\MarkupType::Percent)
                {{ rtrim(rtrim(number_format((float) $p->markup_value, 2, ',', '.'), '0'), ',') }}%
            @else
                Rp {{ number_format((float) $p->markup_value, 0, ',', '.') }}
            @endif
        </td>
        <td class="text-end fw-semibold">Rp {{ number_format($p->selling_price, 0, ',', '.') }}</td>
        <td class="text-end fs-12">{{ $p->unlimited_stock ? '∞' : number_format($p->stock, 0, ',', '.') }}</td>
        <td><span class="pill {{ $statusTones[$p->status ? '1' : '0'] ?? 'bg-tealc-m tx-tealt' }}">{{ $p->status ? 'Aktif' : 'Nonaktif' }}</span></td>
        <td class="tx-mute fs-12">{{ $p->seller?->name ?? '-' }}</td>
        <td class="text-end">
            <x-ui.actions :actions="$actions" />
        </td>
    </tr>
@empty
    <tr>
        <td colspan="12" class="text-center tx-mute py-4">Belum ada produk {{ strtolower($paymentType->label()) }} yang cocok.</td>
    </tr>
@endforelse
