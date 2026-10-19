@forelse ($redemptions as $redemption)
    @php
        $rowActions = [];
        if ($redemption->status->value === 'pending' && auth()->user()->can('redemptions.update')) {
            $rowActions[] = [
                'label' => 'Tandai Selesai',
                'icon' => 'check_circle',
                'tone' => 'tx-mintt',
                'form' => 'redemption-complete-'.$redemption->getKey(),
                'confirm' => 'Tandai penukaran "'.$redemption->merchandise?->name.'" oleh '.$redemption->user?->name.' sebagai selesai?',
            ];
            $rowActions[] = [
                'label' => 'Batalkan',
                'icon' => 'cancel',
                'tone' => 'tx-err',
                'form' => 'redemption-cancel-'.$redemption->getKey(),
                'confirm' => 'Batalkan penukaran "'.$redemption->merchandise?->name.'" oleh '.$redemption->user?->name.'? Poin akan dikembalikan dan stok ditambah.',
                'confirm_danger' => true,
            ];
        }
    @endphp
    <tr>
        <td>
            <div class="fw-semibold fs-12">{{ $redemption->user?->name ?? '-' }}</div>
            <div class="tx-mute fs-11">{{ $redemption->user?->email ?? '' }}</div>
        </td>
        <td class="fs-12 fw-semibold">{{ $redemption->merchandise?->name ?? 'Merchandise dihapus' }}</td>
        <td class="fw-semibold num">{{ number_format($redemption->points_cost, 0, ',', '.') }}</td>
        <td class="tx-mute fs-12">{{ $redemption->created_at->locale('id')->translatedFormat('d M Y H:i') }}</td>
        <td>
            <span class="pill {{ $redemption->status->tone() }}">{{ $redemption->status->label() }}</span>
            @if ($redemption->notes)
                <div class="tx-mute fs-11 text-truncate" style="max-width:12rem">{{ $redemption->notes }}</div>
            @endif
        </td>
        <td class="text-end">
            @if (count($rowActions))
                <x-ui.actions :actions="$rowActions" />
            @endif
            @if ($redemption->status->value === 'pending' && auth()->user()->can('redemptions.update'))
                <form id="redemption-complete-{{ $redemption->getKey() }}" method="POST" action="{{ route('admin.redemptions.complete', $redemption) }}" class="d-none">@csrf</form>
                <form id="redemption-cancel-{{ $redemption->getKey() }}" method="POST" action="{{ route('admin.redemptions.cancel', $redemption) }}" class="d-none">@csrf</form>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6">
            <x-ui.empty-state icon="redeem" title="Belum ada penukaran"
                description="Penukaran poin pengguna ke merchandise akan tampil di sini." />
        </td>
    </tr>
@endforelse
