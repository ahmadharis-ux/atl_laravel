@extends('layouts.admin')

@section('title', 'Topup '.$deposit->reference.' - Admin Atlantic Pedia')
@section('page-title', 'Customer Topup')

@section('content')
<div class="d-flex flex-column gap-4">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.topup.index') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms">arrow_back</span></a>
            <div>
                <h1 class="fw-bold fs-4 m-0">{{ $deposit->reference }}</h1>
                <p class="tx-mute fs-12 m-0">Permintaan top up saldo pelanggan.</p>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="pill {{ $deposit->status->tone() }}">{{ $deposit->status->label() }}</span>
            @if ($deposit->isPending() && auth()->user()->can('topups.update'))
                <form id="topup-settle-show" method="POST" action="{{ route('admin.topup.settle', $deposit) }}" class="d-none">@csrf</form>
                <form id="topup-expire-show" method="POST" action="{{ route('admin.topup.expire', $deposit) }}" class="d-none">@csrf</form>
                <button type="button" class="btn-navy" data-confirm="Tandai top up {{ $deposit->reference }} sebagai lunas dan tambahkan saldo pengguna?" data-submit-form="topup-settle-show">
                    <span class="ms ms-sm align-middle me-1">check_circle</span>Tandai Lunas
                </button>
                <button type="button" class="btn-soft tx-err" data-confirm="Batalkan top up {{ $deposit->reference }}?" data-confirm-danger data-submit-form="topup-expire-show">
                    <span class="ms ms-sm align-middle me-1">cancel</span>Batalkan
                </button>
            @endif
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <x-ui.card title="Detail Top Up" class="h-100">
                <dl class="row mb-0 fs-12 g-2">
                    <dt class="col-sm-5 tx-mute fw-normal">Pengguna</dt>
                    <dd class="col-sm-7 m-0 fw-semibold">{{ $deposit->user?->name ?? '-' }}
                        <div class="tx-mute fs-11 fw-normal">{{ $deposit->user?->email ?? '-' }}</div>
                    </dd>

                    <dt class="col-sm-5 tx-mute fw-normal">Nominal</dt>
                    <dd class="col-sm-7 m-0 fw-semibold">Rp {{ number_format($deposit->amount, 0, ',', '.') }}</dd>

                    <dt class="col-sm-5 tx-mute fw-normal">Dibuat</dt>
                    <dd class="col-sm-7 m-0">{{ $deposit->created_at->locale('id')->translatedFormat('d F Y, H:i') }}</dd>

                    <dt class="col-sm-5 tx-mute fw-normal">Kedaluwarsa</dt>
                    <dd class="col-sm-7 m-0">{{ $deposit->expires_at?->locale('id')->translatedFormat('d F Y, H:i') ?? '—' }}</dd>

                    <dt class="col-sm-5 tx-mute fw-normal">Dibayar</dt>
                    <dd class="col-sm-7 m-0">{{ $deposit->paid_at?->locale('id')->translatedFormat('d F Y, H:i') ?? '—' }}</dd>
                </dl>
            </x-ui.card>
        </div>

        <div class="col-lg-5">
            <x-ui.card title="Pembayaran" class="h-100">
                <dl class="row mb-0 fs-12 g-2">
                    <dt class="col-sm-5 tx-mute fw-normal">Metode</dt>
                    <dd class="col-sm-7 m-0 fw-semibold">{{ $deposit->payment_method->label() }}</dd>

                    <dt class="col-sm-5 tx-mute fw-normal">Kanal</dt>
                    <dd class="col-sm-7 m-0 fw-semibold">{{ $deposit->payment_channel ?: '—' }}</dd>

                    <dt class="col-sm-5 tx-mute fw-normal">Kode Bayar</dt>
                    <dd class="col-sm-7 m-0 fw-semibold">{{ $deposit->payment_code ?: '—' }}</dd>

                    <dt class="col-sm-5 tx-mute fw-normal">Status</dt>
                    <dd class="col-sm-7 m-0"><span class="pill {{ $deposit->status->tone() }}">{{ $deposit->status->label() }}</span></dd>
                </dl>
            </x-ui.card>
        </div>
    </div>

    <x-ui.card title="Mutasi Saldo">
        @if ($ledger === null)
            <x-ui.empty-state icon="account_balance_wallet" title="Belum ada mutasi saldo" description="Saldo pengguna belum bertambah karena top up belum lunas." />
        @else
            <div class="table-responsive">
                <table class="table table-ui align-middle">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Jenis</th>
                            <th>Deskripsi</th>
                            <th class="text-end">Nominal</th>
                            <th class="text-end">Saldo Setelah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="tx-mute fs-12 text-nowrap">{{ $ledger->created_at->locale('id')->translatedFormat('d M Y, H:i') }}</td>
                            <td><span class="pill {{ $ledger->type->tone() }}">{{ $ledger->type->label() }}</span></td>
                            <td class="fs-12">{{ $ledger->description ?: '—' }}</td>
                            <td class="text-end fw-semibold">Rp {{ number_format($ledger->amount, 0, ',', '.') }}</td>
                            <td class="text-end fw-semibold">Rp {{ number_format($ledger->balance_after, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
</div>
@endsection
