@can('dashboard-banners.update')
    @foreach ($banners as $banner)
        <x-ui.modal id="modalBannerDashEdit{{ $banner->getKey() }}" title="Ubah Banner Dashboard" size="lg">
            <form action="{{ route('admin.dashboard-banners.update', $banner) }}" method="POST" id="formBannerDashEdit{{ $banner->getKey() }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <x-ui.input name="badge" :id="'badge_dash_'.$banner->getKey()" label="Label Kecil (opsional)" :value="$banner->badge" />
                <x-ui.input name="title" :id="'title_dash_'.$banner->getKey()" label="Judul" :value="$banner->title" />
                <x-ui.input name="subtitle" :id="'subtitle_dash_'.$banner->getKey()" label="Subjudul (opsional)" :value="$banner->subtitle" />
                <x-ui.textarea name="description" :id="'desc_dash_'.$banner->getKey()" label="Deskripsi" :rows="3" :value="$banner->description" />
                @if ($banner->imageUrl())
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <img src="{{ $banner->imageUrl() }}" alt="{{ $banner->title }}" style="width:5rem;height:3rem;object-fit:cover;border-radius:.5rem">
                        <span class="tx-mute fs-12">Gambar saat ini</span>
                    </div>
                @endif
                <x-ui.input type="file" name="image" :id="'image_dash_'.$banner->getKey()" label="Ganti Gambar" accept="image/png,image/jpeg,image/webp" hint="Kosongkan bila tidak ingin mengubah gambar." />
                <x-ui.input type="url" name="link" :id="'link_dash_'.$banner->getKey()" label="Tautan (opsional)" :value="$banner->link" />
                <x-ui.input name="cta_label" :id="'cta_dash_'.$banner->getKey()" label="Label Tombol" :value="$banner->cta_label" />
                <div class="row g-2">
                    <div class="col-md-6"><x-ui.input type="date" name="starts_at" :id="'start_dash_'.$banner->getKey()" label="Mulai (opsional)" :value="$banner->starts_at?->format('Y-m-d')" /></div>
                    <div class="col-md-6"><x-ui.input type="date" name="ends_at" :id="'end_dash_'.$banner->getKey()" label="Berakhir (opsional)" :value="$banner->ends_at?->format('Y-m-d')" /></div>
                </div>
                <x-ui.input type="number" name="sort_order" :id="'sort_dash_'.$banner->getKey()" label="Urutan tampil" :value="$banner->sort_order" />
                <x-ui.switch name="is_active" label="Aktif" :checked="$banner->is_active" class="mt-2" />
            </form>
            <x-slot:footer>
                <button type="button" class="btn-soft" data-modal-close>Batal</button>
                <button type="submit" form="formBannerDashEdit{{ $banner->getKey() }}" class="btn-navy">Simpan</button>
            </x-slot:footer>
        </x-ui.modal>
    @endforeach
@endcan
