<span class="fs-12 tx-mute">Menampilkan {{ $brands->firstItem() ?? 0 }}–{{ $brands->lastItem() ?? 0 }} dari {{ $brands->total() }} brand</span>
{{ $brands->links() }}
