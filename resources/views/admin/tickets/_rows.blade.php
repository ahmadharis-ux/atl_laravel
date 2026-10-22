@forelse ($tickets as $ticket)
    <tr>
        <td class="tx-mute fs-12 text-nowrap">{{ $ticket->created_at->locale('id')->translatedFormat('d M Y H:i') }}</td>
        <td class="fw-semibold fs-12">{{ $ticket->code }}</td>
        <td>
            <div class="fw-semibold fs-12">{{ $ticket->user?->name ?? 'Pengguna dihapus' }}</div>
            <div class="tx-mute fs-11">{{ $ticket->user?->email ?? '' }}</div>
        </td>
        <td><span class="pill bg-tealc-m tx-tealt">{{ $ticket->category->label() }}</span></td>
        <td class="fw-semibold fs-12" style="max-width:22rem">{{ $ticket->subject }}</td>
        <td class="text-center tx-mute fs-12 num">{{ $ticket->replies_count }}</td>
        <td><span class="pill {{ $ticket->status->tone() }}">{{ $ticket->status->label() }}</span></td>
        <td class="text-end">
            <x-ui.actions :actions="[
                ['label' => 'Detail', 'icon' => 'visibility', 'href' => route('admin.tickets.show', $ticket)],
            ]" />
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8" class="text-center tx-mute py-4">Belum ada tiket yang cocok.</td>
    </tr>
@endforelse
