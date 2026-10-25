@extends('layouts.admin')

@section('title', 'Topup '.$deposit->reference.' - Admin Atlantic Pedia')
@section('page-title', 'Customer Topup')

@section('content')
@php
    $isManual = in_array($deposit->payment_method, [\App\Enums\PaymentMethod::VirtualAccount, \App\Enums\PaymentMethod::EWallet], true);
    $canSettle = (
        $deposit->isPending() && $deposit->payment_method === \App\Enums\PaymentMethod::Qris
    ) || (
        $isManual && $deposit->status === \App\Enums\DepositStatus::UnderReview
        && $deposit->proofs->first()?->review_action === null
    );
@endphp
<div class="d-flex flex-column gap-4">

    @if (session('status'))
        <div class="alert-ap r-sm bg-glass fs-13">{{ session('status') }}</div>
    @endif

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
            @if ($canSettle && auth()->user()->can('topups.update'))
                <button type="button" class="btn-navy" data-modal-open="modalSettleTopup">
                    <span class="ms ms-sm align-middle me-1">check_circle</span>Tandai Lunas
                </button>
            @endif
            @if ($deposit->isPending() && auth()->user()->can('topups.update'))
                <form id="topup-expire-show" method="POST" action="{{ route('admin.topup.expire', $deposit) }}" class="d-none">@csrf</form>
                <button type="button" class="btn-soft tx-err" data-confirm="Batalkan top up {{ $deposit->reference }}?" data-confirm-danger data-submit-form="topup-expire-show">
                    <span class="ms ms-sm align-middle me-1">cancel</span>Batalkan
                </button>
            @endif
        </div>
    </div>

    @if ($isManual)
        <x-ui.card title="Bukti Transfer">
            @forelse ($deposit->proofs as $proof)
                @php
                    $proofStatus = match ($proof->review_action) {
                        \App\Models\DepositProof::ACTION_APPROVED => 'Disetujui',
                        \App\Models\DepositProof::ACTION_NEEDS_REVISION => 'Perlu perbaikan',
                        \App\Models\DepositProof::ACTION_REJECTED => 'Ditolak',
                        default => 'Menunggu verifikasi',
                    };
                @endphp
                <div class="d-flex flex-column gap-2 {{ $loop->first ? '' : 'border-top pt-3 mt-3' }}">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <div class="fw-semibold fs-13">Dikirim {{ $proof->submitted_at->locale('id')->translatedFormat('d M Y, H:i') }}</div>
                            <div class="tx-mute fs-12">{{ $proofStatus }}</div>
                        </div>
                        @if (in_array(strtolower(pathinfo($proof->file_path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true))
                            <button type="button" class="btn-ap btn-ap-outline" data-modal-open="modalDepositProof-{{ $proof->getKey() }}">
                                <span class="ms ms-sm align-middle me-1">visibility</span>Lihat bukti
                            </button>
                        @else
                            <a href="{{ route('admin.topup.proof.download', [$deposit, $proof]) }}" class="btn-ap btn-ap-outline">
                                <span class="ms ms-sm align-middle me-1">download</span>Unduh PDF
                            </a>
                        @endif
                    </div>
                    @if ($proof->review_reason)
                        <div class="alert-ap r-sm bg-glass fs-12">
                            <strong>Catatan:</strong> {{ $proof->review_reason }}
                            @if ($proof->reviewer)
                                <span class="tx-mute">— {{ $proof->reviewer->name }}</span>
                            @endif
                        </div>
                    @endif
                </div>
                @if (in_array(strtolower(pathinfo($proof->file_path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true))
                    <x-ui.modal id="modalDepositProof-{{ $proof->getKey() }}" :title="'Bukti Transfer · '.$proof->submitted_at->locale('id')->translatedFormat('d M Y, H:i')" size="lg">
                        <div class="deposit-proof-viewer" data-proof-zoom>
                            <div class="deposit-proof-controls" role="group" aria-label="Kontrol zoom bukti transfer">
                                <button type="button" class="btn-soft" data-proof-zoom-out aria-label="Perkecil gambar" data-tip="Perkecil">
                                    <span class="ms">remove</span>
                                </button>
                                <button type="button" class="btn-soft" data-proof-zoom-reset>Reset zoom</button>
                                <button type="button" class="btn-soft" data-proof-zoom-in aria-label="Perbesar gambar" data-tip="Perbesar">
                                    <span class="ms">add</span>
                                </button>
                                <a href="{{ route('admin.topup.proof.download', [$deposit, $proof]) }}" class="btn-ap btn-ap-outline ms-auto">
                                    <span class="ms ms-sm align-middle me-1">download</span>Unduh
                                </a>
                            </div>
                            <div class="deposit-proof-frame">
                                <img
                                    src="{{ route('admin.topup.proof.preview', [$deposit, $proof]) }}"
                                    alt="Bukti transfer {{ $deposit->reference }}"
                                    class="deposit-proof-image"
                                    data-proof-image
                                >
                            </div>
                        </div>
                    </x-ui.modal>
                @endif
            @empty
                <x-ui.empty-state icon="upload_file" title="Belum ada bukti transfer" description="Pengguna perlu mengunggah bukti sebelum top up dapat disetujui." />
            @endforelse
        </x-ui.card>
    @endif

    @if ($isManual && $deposit->status === \App\Enums\DepositStatus::UnderReview && auth()->user()->can('topups.update'))
        <x-ui.card title="Tinjau Bukti Pembayaran">
            <form method="POST" action="{{ route('admin.topup.review', $deposit) }}" class="d-flex flex-column gap-3">
                @csrf
                <x-ui.select
                    name="decision"
                    label="Tindakan"
                    :options="['needs_revision' => 'Minta pengguna memperbaiki bukti', 'failed' => 'Tolak top up secara final']"
                    placeholder="Pilih tindakan"
                    required
                />
                <x-ui.textarea
                    name="reason"
                    label="Alasan / instruksi untuk pengguna"
                    :rows="3"
                    required
                    hint="Wajib diisi. Jelaskan bukti yang perlu diperbaiki atau alasan dana tidak dapat diverifikasi."
                />
                <div>
                    <button type="submit" class="btn-navy">
                        <span class="ms ms-sm align-middle me-1">send</span>Kirim hasil pemeriksaan
                    </button>
                </div>
            </form>
        </x-ui.card>
    @endif

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

                    <dt class="col-sm-5 tx-mute fw-normal">Biaya admin</dt>
                    <dd class="col-sm-7 m-0 fw-semibold">Rp {{ number_format($deposit->fee, 0, ',', '.') }}</dd>

                    <dt class="col-sm-5 tx-mute fw-normal">Total tagihan</dt>
                    <dd class="col-sm-7 m-0 fw-semibold">Rp {{ number_format($deposit->totalDue(), 0, ',', '.') }}</dd>

                    @if ($deposit->received_amount !== null)
                        <dt class="col-sm-5 tx-mute fw-normal">Nominal diterima</dt>
                        <dd class="col-sm-7 m-0 fw-semibold">Rp {{ number_format($deposit->received_amount, 0, ',', '.') }}</dd>
                    @endif

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

@if ($canSettle && auth()->user()->can('topups.update'))
    <x-ui.modal id="modalSettleTopup" title="Konfirmasi Top Up" size="sm">
        <form id="topup-settle-form" method="POST" action="{{ route('admin.topup.settle', $deposit) }}">
            @csrf
            <p class="fs-13 tx-mute mb-3">
                Total tagihan <span class="fw-semibold">Rp {{ number_format($deposit->totalDue(), 0, ',', '.') }}</span>
                (nominal Rp {{ number_format($deposit->amount, 0, ',', '.') }} + biaya admin Rp {{ number_format($deposit->fee, 0, ',', '.') }}).
            </p>
            <x-ui.currency-input name="received_amount" label="Nominal diterima" :value="$deposit->totalDue()" hint="Saldo yang masuk = nominal diterima − biaya admin." />
        </form>
        <x-slot:footer>
            <button type="button" class="btn-soft" data-modal-close>Batal</button>
            <button type="submit" form="topup-settle-form" class="btn-navy">
                <span class="ms ms-sm align-middle me-1">check_circle</span>Tandai Lunas
            </button>
        </x-slot:footer>
    </x-ui.modal>
@endif
@endsection
