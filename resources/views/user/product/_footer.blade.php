@if ($products->total() > 0)
    <div class="d-flex flex-column align-items-center gap-2 w-100">
        <span class="tx-mute fs-12">Menampilkan {{ $products->firstItem() }}–{{ $products->lastItem() }} dari {{ $products->total() }} produk</span>
        @if ($products->hasMorePages())
            <button type="button" class="btn-ap btn-navy" data-ajax-more data-page="{{ $products->currentPage() + 1 }}">
                <span class="ms ms-sm">expand_more</span>Muat lebih banyak
            </button>
        @endif
    </div>
@endif
