<span class="fs-12 tx-mute">Menampilkan {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }} dari {{ $items->total() }} merchandise</span>
{{ $items->links() }}
