<span class="fs-12 tx-mute">Menampilkan {{ $contacts->firstItem() ?? 0 }}–{{ $contacts->lastItem() ?? 0 }} dari {{ $contacts->total() }} kontak</span>
{{ $contacts->links() }}
