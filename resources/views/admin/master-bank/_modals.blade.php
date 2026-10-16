@can('master-banks.update')
    @foreach ($banks as $bank)
        <x-ui.modal id="modalBankEdit{{ $bank->getKey() }}" title="Ubah Master Bank" size="sm">
            <form action="{{ route('admin.master-banks.update', $bank) }}" method="POST" id="formBankEdit{{ $bank->getKey() }}">
                @csrf
                @method('PUT')
                <x-ui.select name="type" :id="'type_edit_'.$bank->getKey()" label="Jenis" :options="\App\Models\MasterBank::typeOptions()" :value="$bank->type" />
                @php($bankOptions = collect(\App\Models\MasterBank::nameOptions())->put($bank->bank_name, $bank->bank_name)->all())
                <x-ui.search-select name="bank_name" :id="'bank_name_edit_'.$bank->getKey()" label="Nama Bank atau E-Wallet" placeholder="Pilih bank atau e-wallet..."
                    :options="$bankOptions" :value="$bank->bank_name" />
                <x-ui.input name="account_number" label="Nomor rekening" :value="$bank->account_number" />
                <x-ui.input name="account_holder" label="Atas nama" :value="$bank->account_holder" />
                <x-ui.input name="sort_order" type="number" label="Urutan tampil" :value="$bank->sort_order" />
                <x-ui.switch name="is_active" label="Aktif" :checked="$bank->is_active" class="mt-2" />
            </form>
            <x-slot:footer>
                <button type="button" class="btn-soft" data-modal-close>Batal</button>
                <button type="submit" form="formBankEdit{{ $bank->getKey() }}" class="btn-navy">Simpan</button>
            </x-slot:footer>
        </x-ui.modal>
    @endforeach
@endcan
