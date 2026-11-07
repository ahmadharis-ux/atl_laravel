@extends('layouts.app')

@section('title', 'Mutasi Saldo - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-3">

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('saldo.index') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-4 m-0">Mutasi Saldo</h1>
            <p class="tx-mute fs-12 m-0">Semua uang masuk &amp; keluar dari saldo kamu.</p>
        </div>
    </div>

    <section class="card-ap r-card p-3 d-flex align-items-center justify-content-between gap-3">
        <div>
            <div class="fs-12 tx-mute">Saldo Saat Ini</div>
            <div class="num fw-bold fs-3">Rp {{ number_format($balance, 0, ',', '.') }}</div>
        </div>
        <a href="{{ route('deposits.create') }}" class="btn-ap btn-mint"><span class="ms">add</span>Isi Saldo</a>
    </section>

    <div class="card-ap r-card p-3">
        <h3 class="fw-bold fs-5 m-0 pb-2">Riwayat Mutasi</h3>
        <ul class="trx list-unstyled m-0">
            @forelse ($entries as $entry)
                @php($incoming = $entry->direction->sign() > 0)
                @php($rowUrl = match (true) {
                    $entry->reference instanceof \App\Models\Order => route('orders.show', $entry->reference),
                    $entry->reference instanceof \App\Models\Deposit => route('deposits.show', $entry->reference),
                    default => null,
                })
                <li @if ($rowUrl) data-row-href="{{ $rowUrl }}" @endif>
                    <span class="ibox ibox-44 {{ $entry->type->tone() }}"><span class="ms" style="font-size:20px">{{ $entry->type->icon() }}</span></span>
                    <div class="t">
                        <div class="fw-bold text-truncate">{{ $entry->type->label() }}</div>
                        <div class="fs-12 tx-mute text-truncate">{{ $entry->description ?? $entry->direction->label() }}</div>
                        <div class="fs-11 tx-mute">{{ $entry->created_at->locale('id')->translatedFormat('d M Y H:i') }}</div>
                    </div>
                    <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
                        <span class="num fw-bold {{ $incoming ? 'tx-mintt' : 'tx-err' }}">
                            {{ $incoming ? '+' : '-' }}Rp {{ number_format($entry->amount, 0, ',', '.') }}
                        </span>
                        <span class="fs-11 tx-mute">Saldo Rp {{ number_format($entry->balance_after, 0, ',', '.') }}</span>
                    </div>
                </li>
            @empty
                <li class="d-block border-0 p-0">
                    <x-ui.empty-state icon="history" title="Belum ada mutasi" description="Riwayat isi saldo dan pembelian kamu akan tampil di sini." />
                </li>
            @endforelse
        </ul>

        @if ($entries->hasPages())
            <div class="d-flex justify-content-center pt-3">{{ $entries->links() }}</div>
        @endif
    </div>

</div>
@endsection
