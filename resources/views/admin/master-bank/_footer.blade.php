<div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <span class="fs-12 tx-mute">Menampilkan {{ $banks->firstItem() ?? 0 }}–{{ $banks->lastItem() ?? 0 }} dari {{ $banks->total() }} bank</span>
@include('admin.partials.table-per-page')
    </div>
    {{ $banks->links() }}
</div>
