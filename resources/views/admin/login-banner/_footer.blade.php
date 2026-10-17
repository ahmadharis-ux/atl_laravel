<span class="fs-12 tx-mute">Menampilkan {{ $banners->firstItem() ?? 0 }}–{{ $banners->lastItem() ?? 0 }} dari {{ $banners->total() }} banner</span>
{{ $banners->links() }}
