@forelse ($banners as $banner)
    @php
        $rowActions = [];
        if (auth()->user()->can('login-banners.update')) {
            $rowActions[] = ['label' => 'Ubah', 'icon' => 'edit', 'modal' => 'modalBannerLoginEdit'.$banner->getKey()];
            $rowActions[] = [
                'label' => 'Hapus',
                'icon' => 'delete',
                'tone' => 'tx-err',
                'confirm' => 'Hapus banner "'.$banner->title.'"?',
                'confirm_danger' => true,
                'form' => 'del-login-banner-'.$banner->getKey(),
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
            <div class="fw-semibold">{{ $banner->title }}</div>
            @if ($banner->body)
                <div class="tx-mute fs-12 text-truncate" style="max-width:20rem">{{ $banner->body }}</div>
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
            @if (auth()->user()->can('login-banners.update'))
                <form id="del-login-banner-{{ $banner->getKey() }}" method="POST" action="{{ route('admin.login-banners.destroy', $banner) }}" class="d-none">
                    @csrf
                    @method('DELETE')
                </form>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6">
            <x-ui.empty-state icon="campaign" title="Belum ada banner login"
                description="Tambahkan banner promosi yang akan tampil sebagai popup setelah pengguna login." />
        </td>
    </tr>
@endforelse
