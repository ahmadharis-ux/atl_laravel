@extends('layouts.app')

@section('title', 'Riwayat Top Up - Atlantic Pedia Tab')

@section('content')
<style>
   .min-w-0 { min-width: 0; }
</style>
<div class="d-flex flex-column gap-3">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('saldo.index') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms">arrow_back</span></a>
        <div class="d-flex align-items-center justify-content-between gap-2 flex-grow-1 min-w-0">
            <div class="min-w-0">
                <h1 class="fw-bold fs-4 m-0">Riwayat Top Up</h1>
                <p class="tx-mute fs-12 m-0">Semua permintaan top up saldo kamu.</p>
            </div>
            <div class="flex-shrink-0">
                <a href="{{ route('deposits.create') }}" class="btn-ap btn-mint text-nowrap">
                    <span class="ms">add</span>Top Up
                </a>
            </div>
        </div>
    </div>

    <div class="card-ap r-card p-3">
        <ul class="trx list-unstyled m-0">
            @forelse ($deposits as $deposit)
                @php($method = $deposit->payment_method)
                @php($channelLabel = $deposit->masterBank?->bank_name ?? $method?->channelLabel($deposit->payment_channel))
                @php($resumable = $deposit->status === \App\Enums\DepositStatus::Pending && ! $deposit->isExpired())
                @php($isQris = $method === \App\Enums\PaymentMethod::Qris)
                <li data-row-href="{{ route('deposits.show', $deposit) }}">
                    <span class="ibox ibox-44 {{ $deposit->status->tone() }}"><span class="ms" style="font-size:20px">{{ $deposit->status->icon() }}</span></span>
                    <div class="t">
                        <div class="fw-bold text-truncate">
                            @if ($isQris)
                                {{ $method?->label() }}
                            @else
                                TF
                            @endif
                            @if ($channelLabel) &middot; {{ $channelLabel }}@endif
                        </div>
                        <div class="fs-12 tx-mute text-truncate">{{ $deposit->reference }}</div>
                        <div class="fs-11 tx-mute">{{ $deposit->created_at->locale('id')->translatedFormat('d M Y H:i') }}</div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <div class="d-flex flex-column align-items-end gap-1">
                            <span class="num fw-bold">Rp {{ number_format($deposit->amount, 0, ',', '.') }}</span>
                            <span class="pill {{ $deposit->status->tone() }}">{{ $deposit->status->label() }}</span>
                        </div>
                        {{-- @unless ($isQris)
                            <x-ui.card title="Konfirmasi Pembayaran" class="w-100 mb-2">
                                <div class="d-flex align-items-start gap-2">
                                    <span class="ms tx-gold">hourglass_top</span>
                                    <div class="d-flex flex-column gap-1">
                                        <span class="fs-13 fw-semibold">Menunggu konfirmasi admin</span>
                                        <span class="fs-12 tx-mute">Setelah transfer ke tujuan di atas diterima, admin akan memverifikasi dan menambahkan saldo ke akunmu.</span>
                                    </div>
                                </div>
                            </x-ui.card>
                        @endunless --}}
                        {{-- @if ($resumable)
                            <x-ui.icon-button href="{{ route('deposits.show', $deposit) }}" icon="payments" tip="Lanjutkan bayar" />
                        @endif --}}
                    </div>
                </li>
            @empty
                <li class="d-block border-0 p-0">
                    <x-ui.empty-state icon="receipt_long" title="Belum ada top up" description="Riwayat top up saldo kamu akan tampil di sini.">
                        <a href="{{ route('deposits.create') }}" class="btn-ap btn-mint">Top Up Sekarang</a>
                    </x-ui.empty-state>
                </li>
            @endforelse
        </ul>

        @if ($deposits->hasPages())
            <div class="d-flex justify-content-center pt-3">{{ $deposits->links() }}</div>
        @endif
    </div>

</div>
@endsection
