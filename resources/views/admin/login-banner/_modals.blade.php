@can('login-banners.update')
    @foreach ($banners as $banner)
        <x-ui.modal id="modalBannerLoginEdit{{ $banner->getKey() }}" title="Ubah Banner Login" size="lg">
            <form action="{{ route('admin.login-banners.update', $banner) }}" method="POST" id="formBannerLoginEdit{{ $banner->getKey() }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <x-ui.input name="title" :id="'title_login_'.$banner->getKey()" label="Judul" :value="$banner->title" />
                <x-ui.textarea name="body" :id="'body_login_'.$banner->getKey()" label="Isi / Keterangan" :rows="3" :value="$banner->body" />
                @if ($banner->imageUrl())
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <img src="{{ $banner->imageUrl() }}" alt="{{ $banner->title }}" style="width:5rem;height:3rem;object-fit:cover;border-radius:.5rem">
                        <span class="tx-mute fs-12">Gambar saat ini</span>
                    </div>
                @endif
                <x-ui.input type="file" name="image" :id="'image_login_'.$banner->getKey()" label="Ganti Gambar" accept="image/png,image/jpeg,image/webp" hint="Kosongkan bila tidak ingin mengubah gambar." />
                <x-ui.input type="url" name="link" :id="'link_login_'.$banner->getKey()" label="Tautan (opsional)" :value="$banner->link" />
                <x-ui.input name="cta_label" :id="'cta_login_'.$banner->getKey()" label="Label Tombol (opsional)" :value="$banner->cta_label" />
                <div class="row g-2">
                    <div class="col-md-6"><x-ui.input type="date" name="starts_at" :id="'start_login_'.$banner->getKey()" label="Mulai (opsional)" :value="$banner->starts_at?->format('Y-m-d')" /></div>
                    <div class="col-md-6"><x-ui.input type="date" name="ends_at" :id="'end_login_'.$banner->getKey()" label="Berakhir (opsional)" :value="$banner->ends_at?->format('Y-m-d')" /></div>
                </div>
                <x-ui.input type="number" name="sort_order" :id="'sort_login_'.$banner->getKey()" label="Urutan tampil" :value="$banner->sort_order" />
                <x-ui.switch name="is_active" label="Aktif" :checked="$banner->is_active" class="mt-2" />
            </form>
            <x-slot:footer>
                <button type="button" class="btn-soft" data-modal-close>Batal</button>
                <button type="submit" form="formBannerLoginEdit{{ $banner->getKey() }}" class="btn-navy">Simpan</button>
            </x-slot:footer>
        </x-ui.modal>
    @endforeach
@endcan
