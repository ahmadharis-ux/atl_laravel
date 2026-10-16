<span class="fs-12 tx-mute">Menampilkan {{ $banks->firstItem() ?? 0 }}–{{ $banks->lastItem() ?? 0 }} dari {{ $banks->total() }} bank</span>
{{ $banks->links() }}
