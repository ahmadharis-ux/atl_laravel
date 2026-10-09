@if ($products->total() > 0)
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 w-100">
        <span class="tx-mute fs-12">Menampilkan {{ $products->firstItem() }}–{{ $products->lastItem() }} dari {{ $products->total() }} produk</span>
        {{ $products->links() }}
    </div>
@endif