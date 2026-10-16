<div class="d-flex flex-column gap-3">
    <x-ui.searchbox
        :action="route('products.index', ['tab' => 'brand'])"
        :suggest-url="route('products.lookup', ['type' => 'brands'])"
        :value="request('q')"
        placeholder="Cari brand..."
        suggest-label="Saran Brand"
        :show-history="false"
    />

    @if ($brands->isNotEmpty())
        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-2 g-sm-3">
            @foreach ($brands as $b)
                <div class="col">
                    <a href="{{ route('products.brand', $b) }}" class="bcard r-card h-100 d-flex flex-column align-items-center justify-content-center text-center text-decoration-none py-3 px-2">
                        @if ($b->logoUrl())
                            <img src="{{ $b->logoUrl() }}" alt="{{ $b->name }}" class="pcard-logo mb-2" loading="lazy">
                        @else
                            <span class="ms ms-lg tx-navy mb-2">storefront</span>
                        @endif
                        <span class="pcard-name clamp-1 fw-semibold">{{ $b->name }}</span>
                        <span class="pcard-sold">{{ number_format((int) $b->products_count, 0, ',', '.') }} produk</span>
                    </a>
                </div>
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
