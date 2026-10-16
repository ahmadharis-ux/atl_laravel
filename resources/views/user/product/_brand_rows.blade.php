@forelse ($products as $p)
    @include('user.product._card', [
        'p' => $p,
        'href' => route('checkout.show', $p),
        'checkout' => $canValidateNickname,
    ])
@empty
    <x-ui.empty-state
        class="empty-state-fill"
        icon="search_off"
        title="Produk tidak ditemukan"
        :description="$selectedRegion !== '' || request()->filled('min_price') || request()->filled('max_price')
            ? 'Tidak ada produk dari brand ini yang cocok dengan filter. Coba ubah wilayah atau harga.'
            : 'Belum ada produk dari brand ini.'"
    >
        <a href="{{ route('products.brand', $brand) }}" class="btn-ap btn-navy"><span class="ms ms-sm">restart_alt</span>Lihat Semua Produk</a>
    </x-ui.empty-state>
@endforelse
