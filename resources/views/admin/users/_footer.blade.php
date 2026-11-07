<div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <span class="fs-12 tx-mute">Menampilkan {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} dari {{ $users->total() }} pengguna</span>
@include('admin.partials.table-per-page')
    </div>
    {{ $users->links() }}
</div>
