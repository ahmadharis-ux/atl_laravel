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
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.transactions.invoice', $order) }}" target="_blank" rel="noopener" class="ap-icon-btn" data-tip="Cetak Invoice"><span class="ms ms-md">receipt_long</span></a>
            <span class="pill {{ $order->status->tone() }}">{{ $order->status->label() }}</span>
        </div>
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

                    <dt class="col-sm-4 tx-mute fw-normal">
                        {{ ($order->metadata['storefront']['payment_method'] ?? null) === 'cash' ? 'Potongan saldo reseller' : 'Jumlah' }}
                    </dt>
                    <dd class="col-sm-8 m-0 fw-semibold">Rp {{ number_format($order->amount, 0, ',', '.') }}</dd>

                    @if (($order->metadata['storefront']['payment_method'] ?? null) === 'cash')
                        <dt class="col-sm-4 tx-mute fw-normal">Harga pelanggan</dt>
                        <dd class="col-sm-8 m-0 fw-semibold">Rp {{ number_format((int) ($order->metadata['storefront']['selling_price'] ?? 0), 0, ',', '.') }}</dd>
                    @endif

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

                    @if (($order->metadata['storefront']['payment_method'] ?? null) === 'cash')
                        <dt class="col-sm-5 tx-mute fw-normal">Pembayaran pelanggan</dt>
                        <dd class="col-sm-7 m-0 fw-semibold">Tunai kepada reseller</dd>
                    @endif

                    <dt class="col-sm-5 tx-mute fw-normal">Kanal</dt>
                    <dd class="col-sm-7 m-0 fw-semibold text-uppercase">{{ $order->payment_channel ?: '—' }}</dd>

                    <dt class="col-sm-5 tx-mute fw-normal">Kode Bayar</dt>
                    <dd class="col-sm-7 m-0 fw-semibold">{{ $order->payment_code ?: '—' }}</dd>

                    <dt class="col-sm-5 tx-mute fw-normal">Biaya Admin</dt>
                    <dd class="col-sm-7 m-0 fw-semibold">Rp {{ number_format($order->fee, 0, ',', '.') }}</dd>

                    <dt class="col-sm-5 tx-mute fw-normal">Total Tagihan</dt>
                    <dd class="col-sm-7 m-0 fw-semibold">Rp {{ number_format($order->totalDue(), 0, ',', '.') }}</dd>

                    <dt class="col-sm-5 tx-mute fw-normal">Waktu Bayar</dt>
                    <dd class="col-sm-7 m-0 fw-semibold">{{ $order->paid_at?->locale('id')->translatedFormat('d M Y, H:i') ?? 'Belum dibayar' }}</dd>
                </dl>

                @if ($order->isPending())
                    <form id="formSettle{{ $order->getKey() }}" method="POST" action="{{ route('admin.transactions.settle', $order) }}" class="d-none">@csrf</form>
                    <form id="formExpire{{ $order->getKey() }}" method="POST" action="{{ route('admin.transactions.expire', $order) }}" class="d-none">@csrf</form>
                    <div class="d-flex flex-wrap gap-2 pt-3 mt-3 border-top justify-content-end">
                        <button type="button" class="btn-soft tx-err" data-confirm="Batalkan pesanan {{ $order->order_number }} dan kembalikan stok?" data-confirm-danger data-submit-form="formExpire{{ $order->getKey() }}">
                            <span class="ms ms-sm align-middle me-1">cancel</span>Batalkan
                        </button>
                        <button type="button" class="btn-navy" data-confirm="Tandai pesanan {{ $order->order_number }} (Rp {{ number_format($order->totalDue(), 0, ',', '.') }}) sebagai lunas dan proses pesanan?" data-submit-form="formSettle{{ $order->getKey() }}">
                            <span class="ms ms-sm align-middle me-1">check_circle</span>Tandai Lunas
                        </button>
                    </div>
                @endif
            </x-ui.card>
        </div>
    </div>

    @if ($order->provider)
        <x-ui.card title="Provider">
            @if ($order->provider_payload)
                <x-slot:header>
                    <button type="button" class="btn-soft" data-modal-open="modalProviderPayload">
                        <span class="ms ms-sm align-middle me-1">data_object</span>Payload
                    </button>
                </x-slot:header>
            @endif

            <dl class="row mb-0 fs-12 g-2">
                <dt class="col-sm-4 tx-mute fw-normal">Provider</dt>
                <dd class="col-sm-8 m-0 fw-semibold text-uppercase">{{ $order->provider }}</dd>

                <dt class="col-sm-4 tx-mute fw-normal">Ref ID</dt>
                <dd class="col-sm-8 m-0 fw-semibold">{{ $order->provider_ref ?: '—' }}</dd>

                <dt class="col-sm-4 tx-mute fw-normal">Status Provider</dt>
                <dd class="col-sm-8 m-0 fw-semibold">{{ $order->provider_status ?: '—' }}</dd>

                <dt class="col-sm-4 tx-mute fw-normal">Serial Number</dt>
                <dd class="col-sm-8 m-0 fw-semibold">{{ $order->provider_sn ?: '—' }}</dd>

                <dt class="col-sm-4 tx-mute fw-normal">Dikirim ke Provider</dt>
                <dd class="col-sm-8 m-0">{{ $order->processed_at?->locale('id')->translatedFormat('d F Y, H:i') ?? '—' }}</dd>
            </dl>
        </x-ui.card>

        @if ($order->provider_payload)
            <x-ui.modal id="modalProviderPayload" title="Payload Provider" size="lg">
                <pre class="ap-log-pre">{{ json_encode($order->provider_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                <x-slot:footer>
                    <button type="button" class="btn-soft" data-modal-close>Tutup</button>
                </x-slot:footer>
            </x-ui.modal>
        @endif
    @endif

    @if ($order->status === \App\Enums\OrderStatus::Failed
        || ($order->source === 'store_qris' && $order->status === \App\Enums\OrderStatus::Expired))
        <x-ui.card title="Pengembalian Dana">
            <dl class="row mb-0 fs-12 g-2">
                <dt class="col-sm-4 tx-mute fw-normal">Refund saldo</dt>
                <dd class="col-sm-8 m-0">{{ $order->refunded_at?->locale('id')->translatedFormat('d F Y, H:i') ?? '—' }}</dd>

                @if ($order->source === 'store_qris')
                    <dt class="col-sm-4 tx-mute fw-normal">Status Refund QRIS</dt>
                    <dd class="col-sm-8 m-0">{{ match ($order->refund_status) { 'pending' => 'Sedang diajukan ke Midtrans', 'completed' => 'Selesai', 'manual_required' => 'Perlu tindakan manual', default => 'Belum diajukan' } }}</dd>
                @endif
            </dl>

            <div class="pt-3 mt-3 border-top d-flex justify-content-end">
                @if ($order->refunded_at !== null)
                    <span class="pill bg-mint-l tx-mintt">Sudah direfund</span>
                @elseif ($order->refund_status === 'pending')
                    <span class="pill bg-gold tx-gold">Refund otomatis berjalan</span>
                @elseif (
                    ($order->source === 'store_qris'
                        && $order->refund_status === 'manual_required')
                    || ($order->source !== 'store_qris' && $order->status === \App\Enums\OrderStatus::Failed)
                )
                    <form id="formRefund{{ $order->getKey() }}" method="POST" action="{{ route('admin.transactions.refund', $order) }}"
                          data-confirm="Pastikan refund tidak sedang diproses Midtrans dan dana sudah dikembalikan secara manual. Proses refund ini?">
                        @csrf
                        <button type="submit" class="btn-soft">
                            <span class="ms ms-sm align-middle me-1">currency_exchange</span>Proses Refund
                        </button>
                    </form>
                @endif
            </div>
        </x-ui.card>
    @endif

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
