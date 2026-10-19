@can('merchandise.update')
    @foreach ($items as $item)
        <x-ui.modal id="modalMerchandiseEdit{{ $item->getKey() }}" title="Ubah Merchandise" size="lg">
            <form action="{{ route('admin.merchandise.update', $item) }}" method="POST" id="formMerchandiseEdit{{ $item->getKey() }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <x-ui.input name="name" :id="'name_merch_'.$item->getKey()" label="Nama" :value="$item->name" />
                <x-ui.textarea name="description" :id="'desc_merch_'.$item->getKey()" label="Deskripsi" :rows="3" :value="$item->description" />
                <div class="row g-2">
                    <div class="col-md-6"><x-ui.input type="number" name="points_cost" :id="'cost_merch_'.$item->getKey()" label="Biaya Poin" :value="$item->points_cost" min="1" /></div>
                    <div class="col-md-6"><x-ui.input type="number" name="stock" :id="'stock_merch_'.$item->getKey()" label="Stok" :value="$item->stock" min="0" /></div>
                </div>
                @if ($item->imageUrl())
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <img src="{{ $item->imageUrl() }}" alt="{{ $item->name }}" style="width:3.5rem;height:3.5rem;object-fit:cover;border-radius:.5rem">
                        <span class="tx-mute fs-12">Gambar saat ini</span>
                    </div>
                @endif
                <x-ui.input type="file" name="image" :id="'image_merch_'.$item->getKey()" label="Ganti Gambar" accept="image/png,image/jpeg,image/webp" hint="Kosongkan bila tidak ingin mengubah gambar." />
                <x-ui.switch name="is_active" label="Aktif" :checked="$item->is_active" class="mt-2" />
            </form>
            <x-slot:footer>
                <button type="button" class="btn-soft" data-modal-close>Batal</button>
                <button type="submit" form="formMerchandiseEdit{{ $item->getKey() }}" class="btn-navy">Simpan</button>
            </x-slot:footer>
        </x-ui.modal>
    @endforeach
@endcan
