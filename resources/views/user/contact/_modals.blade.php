@foreach ($contacts as $contact)
    <x-ui.modal id="modalContactEdit{{ $contact->getKey() }}" title="Ubah Kontak" size="sm">
        <form action="{{ route('contacts.update', $contact) }}" method="POST" id="formContactEdit{{ $contact->getKey() }}">
            @csrf
            @method('PUT')
            <x-ui.input name="name" :id="'name_contact_'.$contact->getKey()" label="Nama" :value="$contact->name" />
            <x-ui.input name="phone" :id="'phone_contact_'.$contact->getKey()" label="Nomor HP" :value="$contact->phone" />
            <x-ui.input type="email" name="email" :id="'email_contact_'.$contact->getKey()" label="Email (opsional)" :value="$contact->email" />
            <x-ui.textarea name="notes" :id="'notes_contact_'.$contact->getKey()" label="Catatan (opsional)" :rows="2" :value="$contact->notes" />
        </form>
        <x-slot:footer>
            <button type="button" class="btn-soft" data-modal-close>Batal</button>
            <button type="submit" form="formContactEdit{{ $contact->getKey() }}" class="btn-navy">Simpan</button>
        </x-slot:footer>
    </x-ui.modal>
@endforeach
