@forelse ($contacts as $contact)
    @php
        $rowActions = [
            ['label' => 'Ubah', 'icon' => 'edit', 'modal' => 'modalContactEdit'.$contact->getKey()],
            [
                'label' => 'Hapus',
                'icon' => 'delete',
                'tone' => 'tx-err',
                'confirm' => 'Hapus kontak "'.$contact->name.'"?',
                'confirm_danger' => true,
                'form' => 'del-contact-'.$contact->getKey(),
            ],
        ];
    @endphp
    <tr>
        <td class="fw-semibold">{{ $contact->name }}</td>
        <td class="num">{{ $contact->phone }}</td>
        <td class="tx-mute fs-12">{{ $contact->email ?? '—' }}</td>
        <td class="tx-mute fs-12">
            @if ($contact->notes)
                <x-ui.tooltip :text="$contact->notes" truncate="16rem">{{ $contact->notes }}</x-ui.tooltip>
            @else
                —
            @endif
        </td>
        <td class="text-end">
            <x-ui.actions :actions="$rowActions" />
            <form id="del-contact-{{ $contact->getKey() }}" method="POST" action="{{ route('contacts.destroy', $contact) }}" class="d-none">
                @csrf
                @method('DELETE')
            </form>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="5">
            <x-ui.empty-state icon="contact_phone" title="Belum ada kontak"
                description="Tambahkan kontak satu per satu atau import sekaligus dari berkas CSV/Excel." />
        </td>
    </tr>
@endforelse
