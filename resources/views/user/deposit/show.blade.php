@extends('layouts.app')

@section('title', 'Top Up '.$deposit->reference.' - Atlantic Pedia Tab')

@section('content')
@php
    $method = $deposit->payment_method;
    $masterBank = $deposit->masterBank;
    $channelLabel = $masterBank?->bank_name ?? $method?->channelLabel($deposit->payment_channel);
    $pending = $deposit->status === \App\Enums\DepositStatus::Pending;
    $paid = $deposit->status === \App\Enums\DepositStatus::Paid;
    $midtrans = $deposit->metadata['midtrans'] ?? [];
    $qrUrl = $midtrans['qr_url'] ?? null;
    $isQris = $method === \App\Enums\PaymentMethod::Qris;
    $fee = (int) $deposit->fee;
    $totalDue = $deposit->totalDue();
@endphp

<div class="d-flex flex-column gap-3"
     @if ($pending) data-poll-url="{{ route('deposits.show', $deposit) }}" data-poll-status="{{ $deposit->status->value }}" data-poll-interval="4000" @endif>

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('saldo.index') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-5 m-0">Top Up Saldo</h1>
            <p class="tx-mute fs-12 m-0">{{ $deposit->reference }}</p>
        </div>
        <span class="pill ms-auto {{ $deposit->status->tone() }}">{{ $deposit->status->label() }}</span>
    </div>

    @if (session('status'))
        <div class="alert-ap r-sm bg-mint-l tx-mintt d-flex align-items-center gap-2">
            <span class="ms">check_circle</span>
            <span class="fs-13 fw-semibold">{{ session('status') }}</span>
        </div>
    @endif

    @if ($pending)
        <div class="alert-ap r-sm bg-glass d-flex align-items-center gap-2">
            <span class="ms tx-teal">autorenew</span>
            <span class="fs-13">Halaman ini menyegarkan otomatis setelah pembayaran diterima.</span>
        </div>

        <x-ui.card title="Instruksi Pembayaran">
            @if ($isQris && $qrUrl)
                <div class="d-flex flex-column align-items-center gap-2 text-center">
                    <img src="{{ $qrUrl }}" alt="QRIS" class="r-sm bg-white p-2" style="width:220px;height:220px;object-fit:contain">
                    <div class="num fw-bold fs-4 tx-tealt">Rp {{ number_format($deposit->amount, 0, ',', '.') }}</div>
                    <p class="fs-12 tx-mute m-0">Pindai kode QR di atas dengan aplikasi pembayaran apa pun.</p>
                    @if ($deposit->expires_at)
                        <p class="fs-12 tx-mute m-0">Berlaku sampai {{ $deposit->expires_at->locale('id')->translatedFormat('d M Y, H:i') }}.</p>
                    @endif
                    <div class="d-flex flex-wrap gap-2 justify-content-center w-100 mt-1">
                        <a href="{{ route('deposits.qris', $deposit) }}" class="btn-ap btn-ap-outline flex-fill" style="min-width:9rem">
                            <span class="ms ms-sm align-middle me-1">download</span>Unduh QRIS
                        </a>
                        <form method="POST" action="{{ route('deposits.sync', $deposit) }}" class="d-flex flex-fill m-0" style="min-width:9rem">
                            @csrf
                            <button type="submit" class="btn-ap btn-mint flex-fill justify-content-center w-100">
                                <span class="ms ms-sm align-middle me-1">refresh</span>Cek Status
                            </button>
                        </form>
                    </div>
                    {{-- <p class="fs-11 tx-mute m-0">Sudah membayar? Tekan "Cek Status" untuk memperbarui pembayaran secara manual.</p> --}}
                </div>
            @elseif ($masterBank)
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="ibox ibox-40 bg-mint-l tx-mintt"><span class="ms">{{ $method?->icon() }}</span></span>
                        <div>
                            <div class="fw-semibold fs-13">{{ $masterBank->bank_name }}</div>
                            <div class="tx-mute fs-12">{{ $masterBank->type === \App\Models\MasterBank::TYPE_EWALLET ? 'E-Wallet tujuan' : 'Bank tujuan' }}</div>
                        </div>
                    </div>
                    <ul class="list-unstyled m-0 d-flex flex-column gap-2 fs-13">
                        <li class="d-flex align-items-center justify-content-between">
                            <span class="tx-mute">Nomor tujuan</span>
                            <span class="d-flex align-items-center gap-2">
                                <span class="num fw-semibold">{{ $masterBank->account_number }}</span>
                                <button type="button" class="ap-icon-btn" data-copy="{{ $masterBank->account_number }}" data-copy-label="Nomor tujuan" data-tip="Salin nomor"><span class="ms ms-sm">content_copy</span></button>
                            </span>
                        </li>
                        <li class="d-flex align-items-center justify-content-between">
                            <span class="tx-mute">Atas nama</span>
                            <span class="fw-semibold">{{ $masterBank->account_holder }}</span>
                        </li>
                        <li class="d-flex align-items-center justify-content-between">
                            <span class="tx-mute">Nominal top up</span>
                            <span class="num fw-semibold">Rp {{ number_format($deposit->amount, 0, ',', '.') }}</span>
                        </li>
                        @if ($fee > 0)
                            <li class="d-flex align-items-center justify-content-between">
                                <span class="tx-mute">Biaya admin</span>
                                <span class="num fw-semibold">Rp {{ number_format($fee, 0, ',', '.') }}</span>
                            </li>
                        @endif
                        <li class="d-flex align-items-center justify-content-between pt-2 border-top">
                            <span class="fw-bold">Jumlah transfer</span>
                            <span class="d-flex align-items-center gap-2">
                                <span class="num fw-bold tx-tealt">Rp {{ number_format($totalDue, 0, ',', '.') }}</span>
                                <button type="button" class="ap-icon-btn" data-copy="{{ $totalDue }}" data-copy-label="Total transfer" data-tip="Salin nominal"><span class="ms ms-sm">content_copy</span></button>
                            </span>
                        </li>
                    </ul>
                    @if ($deposit->expires_at)
                        <p class="fs-12 tx-mute m-0">Transfer sebelum {{ $deposit->expires_at->locale('id')->translatedFormat('d M Y, H:i') }}.</p>
                    @endif
                    <p class="fs-12 tx-mute m-0">Pembayaran akan dikonfirmasi admin setelah dana diterima.</p>
                    <div class="alert-ap r-sm bg-glass d-flex align-items-start gap-2 px-3 py-2">
                        <span class="ms ms-sm tx-teal">info</span>
                        <span class="fs-12"><strong>Bila dana yang kamu transfer kurang dari total tagihan, saldo yang ditambahkan akan menyesuaikan dana yang benar-benar diterima (setelah dikurangi biaya admin).</strong></span>
                    </div>
                </div>
            @else
                <div class="d-flex flex-column align-items-center gap-2 text-center">
                    <span class="ibox ibox-48 bg-mint-l tx-mintt"><span class="ms">{{ $method?->icon() }}</span></span>
                    @if ($isQris)
                        <div class="r-sm bg-glass d-flex align-items-center justify-content-center" style="width:140px;height:140px">
                            <span class="ms" style="font-size:64px">qr_code_2</span>
                        </div>
                    @endif
                    <div class="num fw-bold fs-4 tx-tealt">{{ $deposit->payment_code }}</div>
                    <p class="fs-12 tx-mute m-0">{{ $method?->label() }}@if ($channelLabel) &middot; {{ $channelLabel }}@endif</p>
                    <p class="fs-12 tx-mute m-0">Bayar tepat <b>Rp {{ number_format($deposit->amount, 0, ',', '.') }}</b>@if ($deposit->expires_at) sebelum {{ $deposit->expires_at->locale('id')->translatedFormat('d M Y, H:i') }}@endif.</p>
                    @if ($isQris)
                        <form method="POST" action="{{ route('deposits.sync', $deposit) }}" class="d-flex w-100 mt-1">
                            @csrf
                            <button type="submit" class="btn-ap btn-mint justify-content-center w-100">
                                <span class="ms ms-sm align-middle me-1">refresh</span>Cek Status
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        </x-ui.card>

        @unless ($isQris)
            <x-ui.card title="Konfirmasi Pembayaran">
                <div class="d-flex align-items-start gap-2">
                    <span class="ms tx-gold">hourglass_top</span>
                    <div class="d-flex flex-column gap-1">
                        <span class="fs-13 fw-semibold">Menunggu konfirmasi admin</span>
                        <span class="fs-12 tx-mute">Setelah transfer ke tujuan di atas diterima, admin akan memverifikasi dan menambahkan saldo ke akunmu.</span>
                    </div>
                </div>
            </x-ui.card>
        @endunless
    @elseif ($paid)
        <x-ui.card>
            <div class="d-flex flex-column align-items-center gap-2 text-center">
                <span class="ibox ibox-48 bg-mint-l tx-mintt"><span class="ms">check_circle</span></span>
                <div class="num fw-bold fs-4 tx-tealt">+ Rp {{ number_format($deposit->amount, 0, ',', '.') }}</div>
                <p class="fs-13 tx-mute m-0">Top up berhasil. Saldo kamu sudah bertambah.</p>
                <a href="{{ route('saldo.index') }}" class="btn-ap btn-mint justify-content-center">Lihat Saldo</a>
            </div>
        </x-ui.card>
    @else
        <x-ui.card title="Top Up Tidak Selesai">
            <div class="d-flex flex-column align-items-center gap-2 text-center">
                <span class="ibox ibox-48 bg-glass tx-mute"><span class="ms">{{ $deposit->status->icon() }}</span></span>
                <p class="fs-13 tx-mute m-0">{{ $deposit->status->label() }}. Silakan buat permintaan top up baru.</p>
                <a href="{{ route('deposits.create') }}" class="btn-ap btn-mint justify-content-center">Buat Top Up Baru</a>
                @php
                    $helpUrl = \App\Support\SupportWhatsapp::depositIssueUrl($deposit);
                @endphp
                @if ($helpUrl)
                    <x-ui.whatsapp-help :url="$helpUrl" label="Laporkan ke CS via WhatsApp" class="mt-1" />
                @endif
            </div>
        </x-ui.card>
    @endif

    <x-ui.card title="Rincian Top Up">
        <ul class="list-unstyled m-0 d-flex flex-column gap-2 fs-13">
            <li class="d-flex align-items-center justify-content-between">
                <span class="tx-mute">Referensi</span>
                <span class="fw-semibold">{{ $deposit->reference }}</span>
            </li>
            <li class="d-flex align-items-center justify-content-between">
                <span class="tx-mute">Metode</span>
                <span class="fw-semibold">{{ $method?->label() }}@if ($channelLabel) &middot; {{ $channelLabel }}@endif</span>
            </li>
            <li class="d-flex align-items-center justify-content-between">
                <span class="tx-mute">Dibuat</span>
                <span class="fw-semibold">{{ $deposit->created_at->format('d M Y, H:i') }}</span>
            </li>
            @if ($deposit->expires_at)
                <li class="d-flex align-items-center justify-content-between">
                    <span class="tx-mute">Kedaluwarsa</span>
                    <span class="fw-semibold">{{ $deposit->expires_at->format('d M Y, H:i') }}</span>
                </li>
            @endif
            @if ($deposit->paid_at)
                <li class="d-flex align-items-center justify-content-between">
                    <span class="tx-mute">Dibayar</span>
                    <span class="fw-semibold">{{ $deposit->paid_at->format('d M Y, H:i') }}</span>
                </li>
            @endif
            @if ($fee > 0)
                <li class="d-flex align-items-center justify-content-between">
                    <span class="tx-mute">Biaya admin</span>
                    <span class="fw-semibold">Rp {{ number_format($fee, 0, ',', '.') }}</span>
                </li>
            @endif
            <li class="d-flex align-items-center justify-content-between pt-2 border-top">
                <span class="fw-bold">Total</span>
                <span class="num fw-bold tx-tealt">Rp {{ number_format($totalDue, 0, ',', '.') }}</span>
            </li>
        </ul>
    </x-ui.card>
</div>
@endsection
