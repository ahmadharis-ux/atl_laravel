<div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <span class="fs-12 tx-mute">Menampilkan {{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }} dari {{ $orders->total() }} transaksi</span>
@include('admin.partials.table-per-page')
    </div>
    {{ $orders->links() }}
</div>
