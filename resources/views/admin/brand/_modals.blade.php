@can('master-brands.update')
    @foreach ($brands as $brand)
        <x-ui.modal id="modalBrandEdit{{ $brand->getKey() }}" title="Ubah Logo Brand" size="sm">
            <form action="{{ route('admin.master-brands.update', $brand) }}" method="POST"
                id="formBrandEdit{{ $brand->getKey() }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="ibox ibox-48 bg-white r-sm" style="padding:6px">
                        @if ($brand->logoUrl())
                            <img src="{{ $brand->logoUrl() }}" alt="{{ $brand->name }}" style="width:100%;height:100%;object-fit:contain">
                        @else
                            <span class="ms tx-mute">image</span>
                        @endif
                    </div>
                    <div>
                        <div class="fw-semibold fs-13">{{ $brand->name }}</div>
                        <div class="tx-mute fs-12">{{ $brand->logo ?: 'Belum ada logo' }}</div>
                    </div>
                </div>
                <x-ui.input :id="'logo_'.$brand->getKey()" type="file" name="logo" label="Logo baru"
                    accept="image/png,image/jpeg,image/webp" hint="PNG, JPG, atau WEBP. Maksimal 2MB." />
            </form>
            <x-slot:footer>
                <button type="button" class="btn-soft" data-modal-close>Batal</button>
                <button type="submit" form="formBrandEdit{{ $brand->getKey() }}" class="btn-navy">Simpan</button>
            </x-slot:footer>
        </x-ui.modal>
    @endforeach
@endcan
