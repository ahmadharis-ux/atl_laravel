@php($logo = $b->logoUrl())
<div class="col">
    <a href="{{ route('products.brand', $b) }}" class="pcard bcard r-card h-100 d-flex flex-column">
        <div class="pcard-thumb bg-sky-l">
            @if ($logo)
                <img src="{{ $logo }}" alt="{{ $b->name }}" class="pcard-logo" loading="lazy">
            @else
                <span class="ms tx-navy">storefront</span>
            @endif
        </div>
        <div class="pcard-body">
            <div class="pcard-name clamp-2">{{ $b->name }}</div>
            <div class="pcard-sold">{{ number_format((int) ($b->products_count ?? 0), 0, ',', '.') }} produk</div>
        </div>
    </a>
</div>
