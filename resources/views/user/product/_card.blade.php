@php
    $thumb = match ($p->category?->slug) {
        'pulsa'   => ['icon' => 'smartphone',    'bg' => 'bg-tealc-l', 'tx' => 'tx-tealt'],
        'games'   => ['icon' => 'sports_esports','bg' => 'bg-mint-l',  'tx' => 'tx-mintt'],
        'tagihan' => ['icon' => 'receipt_long',  'bg' => 'bg-gold',    'tx' => 'tx-gold'],
        'voucher' => ['icon' => 'star',          'bg' => 'bg-sky-l',   'tx' => 'tx-navy'],
        'hiburan' => ['icon' => 'movie',         'bg' => 'bg-errc-l',  'tx' => 'tx-err'],
        default   => ['icon' => 'storefront',    'bg' => 'bg-sky-l',   'tx' => 'tx-navy'],
    };
    $isOutOfStock = ! $p->unlimited_stock && $p->stock < 1;
    $isCheckout = $checkout ?? false;
    $href = $href ?? route('products.show', $p);
    $brandLogo = $p->brand?->logoUrl();
    $region = $p->region ?? null;
@endphp
<div class="col">
    <a href="{{ $href }}" class="pcard r-card h-100 d-flex flex-column"@if ($isCheckout) data-checkout-link data-checkout-base="{{ route('checkout.show', $p) }}" data-tip="Beli"@endif>
        <div class="pcard-thumb {{ $thumb['bg'] }}">
            @if ($brandLogo)
                <img src="{{ $brandLogo }}" alt="{{ $p->brand->name }}" class="pcard-logo" loading="lazy">
            @else
                <span class="ms {{ $thumb['tx'] }}">{{ $thumb['icon'] }}</span>
            @endif
            @if ($region)
                <span class="pill bg-glass tx-mute pcard-badge">{{ $region }}</span>
            @endif
            @if ($isOutOfStock)
                <span class="pill bg-errc-l tx-err pcard-badge">Habis</span>
            @endif
        </div>
        <div class="pcard-body">
            <div class="pcard-name clamp-2">{{ $p->name }}</div>
            <div class="pcard-price num">Rp {{ number_format($p->selling_price, 0, ',', '.') }}</div>
            <div class="pcard-sold">{{ number_format($p->sold_count, 0, ',', '.') }} terjual</div>
        </div>
    </a>
</div>