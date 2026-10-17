@if ($notifications->total() > 0)
    <div class="d-flex flex-column align-items-center gap-2 w-100">
        <span class="tx-mute fs-12">Menampilkan {{ $notifications->firstItem() }}–{{ $notifications->lastItem() }} dari {{ $notifications->total() }} notifikasi</span>
        @if ($notifications->hasMorePages())
            <button type="button" class="btn-ap btn-navy" data-ajax-more data-page="{{ $notifications->currentPage() + 1 }}">
                <span class="ms ms-sm">expand_more</span>Muat lebih banyak
            </button>
        @endif
    </div>
@endif
