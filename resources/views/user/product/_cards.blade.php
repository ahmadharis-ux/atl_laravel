@forelse ($products as $p)
    @include('user.product._card', ['p' => $p])
@empty
    <x-ui.empty-state
        class="empty-state-fill"
        icon="search_off"
        title="Produk tidak ditemukan"
        :description="$searching
            ? 'Tidak ada hasil untuk “'.$term.'”. Coba kata kunci lain atau ubah filter.'
            : 'Belum ada produk '.strtolower($paymentType->label()).' yang cocok dengan filter ini.'"
    >
        <a href="{{ route('products.index') }}" class="btn-ap btn-navy"><span class="ms ms-sm">storefront</span>Lihat semua produk</a>
    </x-ui.empty-state>
@endforelse