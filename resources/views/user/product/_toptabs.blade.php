<nav class="ptabs ptabs-full" role="tablist" aria-label="Mode katalog">
    <a href="{{ route('products.index') }}" role="tab"
       class="{{ $tab === 'product' ? 'active' : '' }}"
       @if ($tab === 'product') aria-current="page" @endif>
        <span class="ms ms-sm" aria-hidden="true">storefront</span>Produk
    </a>
    <a href="{{ route('products.index', ['tab' => 'brand']) }}" role="tab"
       class="{{ $tab === 'brand' ? 'active' : '' }}"
       @if ($tab === 'brand') aria-current="page" @endif>
        <span class="ms ms-sm" aria-hidden="true">apps</span>Brand
    </a>
</nav>
