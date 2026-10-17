@forelse ($products as $p)
    @include('user.product._card', [
        'p' => $p,
        'href' => route('checkout.show', $p),
        'checkout' => $canValidateNickname,
    ])
@empty
    @php
        $filtered = request()->filled('region')
            || request()->filled('category')
            || request()->filled('min_price')
            || request()->filled('max_price');
    @endphp
    <x-ui.empty-state
        class="empty-state-fill"
        icon="search_off"
        title="Produk tidak ditemukan"
        :description="($searching ?? false)
            ? 'Tidak ada produk “'.($term ?? '').'” yang cocok di brand ini. Coba kata kunci lain.'
            : ($filtered
                ? 'Tidak ada produk dari brand ini yang cocok dengan filter. Coba ubah wilayah, kategori, atau harga.'
                : 'Belum ada produk dari brand ini.')"
    >
        <a href="{{ route('products.brand', $brand) }}" class="btn-ap btn-navy"><span class="ms ms-sm">restart_alt</span>Lihat Semua Produk</a>
    </x-ui.empty-state>
@endforelse
