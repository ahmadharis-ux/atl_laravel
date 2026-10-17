@forelse ($banners as $banner)
    @php
        $rowActions = [];
        if (auth()->user()->can('dashboard-banners.update')) {
            $rowActions[] = ['label' => 'Ubah', 'icon' => 'edit', 'modal' => 'modalBannerDashEdit'.$banner->getKey()];
            $rowActions[] = [
                'label' => 'Hapus',
                'icon' => 'delete',
                'tone' => 'tx-err',
                'confirm' => 'Hapus banner "'.$banner->title.'"?',
                'confirm_danger' => true,
                'form' => 'del-dashboard-banner-'.$banner->getKey(),
            ];
        }
    @endphp
    <tr>
        <td>
            @if ($url = $banner->imageUrl())
                <img src="{{ $url }}" alt="{{ $banner->title }}" style="width:4.5rem;height:2.5rem;object-fit:cover;border-radius:.5rem">
            @else
                <span class="ibox ibox-48 bg-white r-sm"><span class="ms tx-mute">image</span></span>
            @endif
        </td>
        <td>
            @if ($banner->badge)
                <span class="pill bg-mint-l tx-mintt fs-11 mb-1">{{ $banner->badge }}</span>
            @endif
            <div class="fw-semibold">{{ $banner->title }}</div>
            @if ($banner->subtitle)
                <div class="tx-mute fs-12 text-truncate" style="max-width:20rem">{{ $banner->subtitle }}</div>
            @endif
        </td>
        <td class="tx-mute fs-12">
            @if ($banner->starts_at || $banner->ends_at)
                {{ $banner->starts_at?->format('d/m/Y') ?? '—' }} &ndash; {{ $banner->ends_at?->format('d/m/Y') ?? '—' }}
            @else
                Selalu tampil
            @endif
        </td>
        <td>
            @if ($banner->is_active)
                <span class="pill bg-mint-l tx-mintt">Aktif</span>
            @else
                <span class="pill bg-errc-l tx-err">Nonaktif</span>
            @endif
        </td>
        <td class="tx-mute fs-12 num">{{ $banner->sort_order }}</td>
        <td class="text-end">
            @if (count($rowActions))
                <x-ui.actions :actions="$rowActions" />
            @endif
            @if (auth()->user()->can('dashboard-banners.update'))
                <form id="del-dashboard-banner-{{ $banner->getKey() }}" method="POST" action="{{ route('admin.dashboard-banners.destroy', $banner) }}" class="d-none">
                    @csrf
                    @method('DELETE')
                </form>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6">
            <x-ui.empty-state icon="view_carousel" title="Belum ada banner dashboard"
                description="Tambahkan banner promosi yang akan tampil di beranda pengguna." />
        </td>
    </tr>
@endforelse
