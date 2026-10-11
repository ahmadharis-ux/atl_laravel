@forelse ($deposits as $deposit)
    @php
        $actions = [
            ['label' => 'Detail', 'icon' => 'visibility', 'href' => route('admin.topup.show', $deposit)],
        ];
        if ($deposit->isPending() && auth()->user()->can('topups.update')) {
            $actions[] = [
                'label' => 'Tandai Lunas',
                'icon' => 'check_circle',
                'tone' => 'tx-mintt',
                'form' => 'topup-settle-'.$deposit->id,
                'confirm' => 'Tandai top up '.$deposit->reference.' sebagai lunas dan tambahkan saldo pengguna?',
            ];
            $actions[] = [
                'label' => 'Batalkan',
                'icon' => 'cancel',
                'tone' => 'tx-err',
                'form' => 'topup-expire-'.$deposit->id,
                'confirm' => 'Batalkan top up '.$deposit->reference.'?',
                'confirm_danger' => true,
            ];
        }
    @endphp
    <tr>
        <td class="tx-mute fs-12 text-nowrap">{{ $deposit->created_at->locale('id')->translatedFormat('d M Y H:i') }}</td>
        <td><x-ui.tooltip :text="$deposit->reference" class="fw-semibold fs-12">{{ $deposit->reference }}</x-ui.tooltip></td>
        <td>
            <div class="fw-semibold fs-12">{{ $deposit->user?->name ?? '-' }}</div>
            <div class="tx-mute fs-11">{{ $deposit->user?->email ?? '-' }}</div>
        </td>
        <td>
            <span class="pill bg-tealc-m tx-tealt">{{ $deposit->payment_method->label() }}</span>
            @if ($deposit->payment_channel)
                <div class="tx-mute fs-11">{{ $deposit->payment_channel }}</div>
            @endif
        </td>
        <td class="text-end fw-semibold">Rp {{ number_format($deposit->amount, 0, ',', '.') }}</td>
        <td><span class="pill {{ $deposit->status->tone() }}">{{ $deposit->status->label() }}</span></td>
        <td class="tx-mute fs-12 text-nowrap">{{ $deposit->paid_at?->locale('id')->translatedFormat('d M Y H:i') ?? '—' }}</td>
        <td class="text-end">
            <x-ui.actions :actions="$actions" />
            @if ($deposit->isPending() && auth()->user()->can('topups.update'))
                <form id="topup-settle-{{ $deposit->id }}" method="POST" action="{{ route('admin.topup.settle', $deposit) }}" class="d-none">@csrf</form>
                <form id="topup-expire-{{ $deposit->id }}" method="POST" action="{{ route('admin.topup.expire', $deposit) }}" class="d-none">@csrf</form>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8" class="text-center tx-mute py-4">Belum ada data top up yang cocok.</td>
    </tr>
@endforelse
