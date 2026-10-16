<div class="d-flex flex-column gap-3">
    <x-ui.searchbox
        :action="route('products.index', ['tab' => 'brand'])"
        :suggest-url="route('products.lookup', ['type' => 'brands'])"
        :params="['tab' => 'brand']"
        :value="request('q')"
        placeholder="Cari brand..."
        suggest-label="Saran Brand"
        :show-history="false"
    />

    @if ($brands->isNotEmpty())
        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-8 g-2 g-sm-3">
            @foreach ($brands as $b)
                @include('user.product._brand_card')
            @endforeach
        </div>

        @if (! $allBrands && $brandTotal > $brands->count())
            <div class="d-flex justify-content-center">
                <a href="{{ route('products.index', array_filter(['tab' => 'brand', 'q' => $term, 'all' => 1])) }}" class="btn-ap btn-navy">
                    <span class="ms ms-sm">apps</span>Lihat semua {{ number_format($brandTotal, 0, ',', '.') }} brand
                </a>
            </div>
        @endif
    @else
        <x-ui.empty-state
            icon="search_off"
            title="Brand tidak ditemukan"
            :description="$term !== ''
                ? 'Tidak ada brand yang cocok dengan “'.$term.'”. Coba kata kunci lain.'
                : 'Belum ada brand tersedia.'"
        />
    @endif
</div>
