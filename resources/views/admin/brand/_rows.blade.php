@forelse ($brands as $brand)
    @php
        $rowActions = [];
        if (auth()->user()->can('master-brands.update')) {
            $rowActions[] = ['label' => 'Ubah Logo', 'icon' => 'image', 'modal' => 'modalBrandEdit'.$brand->getKey()];
        }
    @endphp
    <tr>
        <td>
            @if ($brand->logoUrl())
                <img src="{{ $brand->logoUrl() }}" alt="{{ $brand->name }}" class="r-sm bg-white" style="width:40px;height:40px;object-fit:contain;padding:4px">
            @else
                <span class="ibox ibox-40 bg-glass tx-mute"><span class="ms">image</span></span>
            @endif
        </td>
        <td class="fw-semibold">{{ $brand->name }}</td>
        <td class="tx-mute fs-12">{{ $brand->slug }}</td>
        <td class="num tx-mute fs-12">{{ $brand->products_count }}</td>
        <td class="text-end">
            @if (count($rowActions))
                <x-ui.actions :actions="$rowActions" />
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="5">
            <x-ui.empty-state icon="branding_watermark" title="Belum ada brand"
                description="Brand akan muncul di sini setelah produk tersinkron." />
        </td>
    </tr>
@endforelse
