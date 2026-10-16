@if ($brands->isNotEmpty())
    <section id="brand-section" class="sec">
        <div class="sec-head">
            <span class="ic"><span class="ms">storefront</span></span>
            <div><h2>Pilih Brand</h2><p>Pilih brand untuk melihat semua produknya.</p></div>
        </div>
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
                        <span class="pcard-sold">{{ number_format($b->products()->where('status', true)->forCatalog()->count(), 0, ',', '.') }} produk</span>
                    </a>
                </div>
            @endforeach
        </div>
    </section>
@endif
