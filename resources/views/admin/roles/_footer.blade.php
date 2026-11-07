<div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <span class="fs-12 tx-mute">Menampilkan {{ $roles->firstItem() ?? 0 }}–{{ $roles->lastItem() ?? 0 }} dari {{ $roles->total() }} peran</span>
@include('admin.partials.table-per-page')
    </div>
    {{ $roles->links() }}
</div>
