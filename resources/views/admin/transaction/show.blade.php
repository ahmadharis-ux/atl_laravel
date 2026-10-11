@extends('layouts.admin')

@section('title', 'Transaksi '.$order->order_number.' - Admin Atlantic Pedia')
@section('page-title', 'Detail Transaksi')

@section('content')
<div class="d-flex flex-column gap-4">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.transactions.index') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
            <div>
                <h1 class="fw-bold fs-4 m-0">{{ $order->order_number }}</h1>
                <p class="tx-mute fs-12 m-0">Dibuat {{ $order->created_at->locale('id')->translatedFormat('d F Y, H:i') }}</p>
            </div>
        </div>
        <span class="pill {{ $order->status->tone() }}">{{ $order->status->label() }}</span>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <x-ui.card title="Detail Pesanan" class="h-100">
                <dl class="row mb-0 fs-12 g-2">
                    <dt class="col-sm-4 tx-mute fw-normal">Pengguna</dt>
                    <dd class="col-sm-8 m-0 fw-semibold">{{ $order->user?->name ?? '-' }}
                        <div class="tx-mute fs-11 fw-normal">{{ $order->user?->email ?? '' }}</div>
                    </dd>

                    <dt class="col-sm-4 tx-mute fw-normal">Produk</dt>
                    <dd class="col-sm-8 m-0 fw-semibold">{{ $order->product?->name ?? 'Produk dihapus' }}
                        <div class="tx-mute fs-11 fw-normal">{{ $order->product?->brand?->name ?? '' }}</div>
                    </dd>

                    <dt class="col-sm-4 tx-mute fw-normal">Tujuan</dt>
                    <dd class="col-sm-8 m-0 fw-semibold">{{ $order->target }}
                        @if ($order->target_zone)
                            <span class="tx-mute">({{ $order->target_zone }})</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4 tx-mute fw-normal">Jumlah</dt>
                    <dd class="col-sm-8 m-0 fw-semibold">Rp {{ number_format($order->amount, 0, ',', '.') }}</dd>

                    <dt class="col-sm-4 tx-mute fw-normal">Dibuat</dt>
                    <dd class="col-sm-8 m-0">{{ $order->created_at->locale('id')->translatedFormat('d F Y, H:i') }}</dd>

                    <dt class="col-sm-4 tx-mute fw-normal">Dibayar</dt>
                    <dd class="col-sm-8 m-0">{{ $order->paid_at?->locale('id')->translatedFormat('d F Y, H:i') ?? '—' }}</dd>

                    <dt class="col-sm-4 tx-mute fw-normal">Catatan</dt>
                    <dd class="col-sm-8 m-0">{{ $order->notes ?: '—' }}</dd>
                </dl>
            </x-ui.card>
        </div>

        <div class="col-lg-5">
            <x-ui.card title="Pembayaran" class="h-100">
                <dl class="row mb-0 fs-12 g-2">
                    <dt class="col-sm-5 tx-mute fw-normal">Metode</dt>
                    <dd class="col-sm-7 m-0 fw-semibold">{{ $order->payment_method->label() }}</dd>

                    <dt class="col-sm-5 tx-mute fw-normal">Kanal</dt>
                    <dd class="col-sm-7 m-0 fw-semibold text-uppercase">{{ $order->payment_channel ?: '—' }}</dd>

                    <dt class="col-sm-5 tx-mute fw-normal">Kode Bayar</dt>
                    <dd class="col-sm-7 m-0 fw-semibold">{{ $order->payment_code ?: '—' }}</dd>

                    <dt class="col-sm-5 tx-mute fw-normal">Waktu Bayar</dt>
                    <dd class="col-sm-7 m-0 fw-semibold">{{ $order->paid_at?->locale('id')->translatedFormat('d M Y, H:i') ?? 'Belum dibayar' }}</dd>
                </dl>
            </x-ui.card>
        </div>
    </div>

    <x-ui.card title="Riwayat Log">
        @if ($logs->isEmpty())
            <x-ui.empty-state icon="history" title="Belum ada log" description="Belum ada catatan proses untuk transaksi ini." />
        @else
            <div class="table-responsive">
                <table class="table table-ui align-middle">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Level</th>
                            <th>Event</th>
                            <th>Pesan</th>
                            <th class="text-end">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td class="tx-mute fs-12 text-nowrap">{{ $log->created_at->locale('id')->translatedFormat('d M H:i:s') }}</td>
                                <td>
                                    <span class="pill {{ $log->level->tone() }}">
                                        <span class="ms ms-sm align-middle">{{ $log->level->icon() }}</span> {{ $log->level->label() }}
                                    </span>
                                </td>
                                <td class="fw-semibold fs-12">{{ $log->event }}</td>
                                <td class="fs-12">{{ $log->message }}</td>
                                <td class="text-end">
                                    @if ($log->context || $log->stack_trace)
                                        <button type="button" class="ap-icon-btn" data-modal-open="log-{{ $log->id }}" data-tip="Lihat detail log"><span class="ms ms-md">visibility</span></button>
                                    @else
                                        <span class="tx-mute">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    @foreach ($logs as $log)
        @if ($log->context || $log->stack_trace)
            <x-ui.modal id="log-{{ $log->id }}" :title="'Log · '.$log->event" size="lg">
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="pill {{ $log->level->tone() }}">{{ $log->level->label() }}</span>
                        <span class="tx-mute fs-12">{{ $log->created_at->locale('id')->translatedFormat('d F Y, H:i:s') }}</span>
                    </div>
                    <p class="m-0 fw-semibold">{{ $log->message }}</p>

                    @if ($log->context)
                        <div>
                            <div class="fw-bold fs-12 mb-1">Context</div>
                            <pre class="ap-log-pre">{{ json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    @endif

                    @if ($log->stack_trace)
                        <div>
                            <div class="fw-bold fs-12 mb-1">Stack Trace</div>
                            <pre class="ap-log-pre">{{ $log->stack_trace }}</pre>
                        </div>
                    @endif
                </div>
                <x-slot:footer>
                    <button type="button" class="btn-soft" data-modal-close>Tutup</button>
                </x-slot:footer>
            </x-ui.modal>
        @endif
    @endforeach
</div>
@endsection
