<div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <span class="fs-12 tx-mute">Menampilkan {{ $tickets->firstItem() ?? 0 }}–{{ $tickets->lastItem() ?? 0 }} dari {{ $tickets->total() }} tiket</span>
        @include('admin.partials.table-per-page')
    </div>
    {{ $tickets->links() }}
</div>
