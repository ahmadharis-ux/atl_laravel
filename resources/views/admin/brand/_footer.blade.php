<div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <span class="fs-12 tx-mute">Menampilkan {{ $brands->firstItem() ?? 0 }}–{{ $brands->lastItem() ?? 0 }} dari {{ $brands->total() }} brand</span>
@include('admin.partials.table-per-page')
    </div>
    {{ $brands->links() }}
</div>
