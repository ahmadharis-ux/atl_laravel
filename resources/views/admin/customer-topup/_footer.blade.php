<div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <span class="fs-12 tx-mute">Menampilkan {{ $deposits->firstItem() ?? 0 }}–{{ $deposits->lastItem() ?? 0 }} dari {{ $deposits->total() }} top up</span>
@include('admin.partials.table-per-page')
    </div>
    {{ $deposits->links() }}
</div>
