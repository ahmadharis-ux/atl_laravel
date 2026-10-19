@forelse ($items as $item)
    @php
        $rowActions = [];
        if (auth()->user()->can('merchandise.update')) {
            $rowActions[] = ['label' => 'Ubah', 'icon' => 'edit', 'modal' => 'modalMerchandiseEdit'.$item->getKey()];
            $rowActions[] = [
                'label' => 'Hapus',
                'icon' => 'delete',
                'tone' => 'tx-err',
                'confirm' => 'Hapus merchandise "'.$item->name.'"?',
                'confirm_danger' => true,
                'form' => 'del-merchandise-'.$item->getKey(),
            ];
        }
    @endphp
    <tr>
        <td>
            @if ($url = $item->imageUrl())
                <img src="{{ $url }}" alt="{{ $item->name }}" style="width:3rem;height:3rem;object-fit:cover;border-radius:.5rem">
            @else
                <span class="ibox ibox-48 bg-white r-sm"><span class="ms tx-mute">image</span></span>
            @endif
        </td>
        <td>
            <div class="fw-semibold">{{ $item->name }}</div>
            @if ($item->description)
                <div class="tx-mute fs-12 text-truncate" style="max-width:18rem">{{ $item->description }}</div>
            @endif
        </td>
        <td class="fw-semibold num">{{ number_format($item->points_cost, 0, ',', '.') }} poin</td>
        <td class="num {{ $item->stock < 1 ? 'tx-err fw-semibold' : '' }}">{{ $item->stock }}</td>
        <td>
            @if ($item->is_active)
                <span class="pill bg-mint-l tx-mintt">Aktif</span>
            @else
                <span class="pill bg-errc-l tx-err">Nonaktif</span>
            @endif
        </td>
        <td class="text-end">
            @if (count($rowActions))
                <x-ui.actions :actions="$rowActions" />
            @endif
            @if (auth()->user()->can('merchandise.update'))
                <form id="del-merchandise-{{ $item->getKey() }}" method="POST" action="{{ route('admin.merchandise.destroy', $item) }}" class="d-none">
                    @csrf
                    @method('DELETE')
                </form>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6">
            <x-ui.empty-state icon="redeem" title="Belum ada merchandise"
                description="Tambahkan merchandise yang dapat ditukar pengguna dengan poin reward." />
        </td>
    </tr>
@endforelse
