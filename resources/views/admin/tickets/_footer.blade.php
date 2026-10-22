<span class="fs-12 tx-mute">Menampilkan {{ $tickets->firstItem() ?? 0 }}–{{ $tickets->lastItem() ?? 0 }} dari {{ $tickets->total() }} tiket</span>
{{ $tickets->links() }}
