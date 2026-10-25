@extends('layouts.app')

@section('title', 'Toko & Komisi Reseller - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-4">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('settings.reseller') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-4 m-0">Toko &amp; Komisi Reseller</h1>
            <p class="tx-mute fs-12 m-0">Bagikan toko Atlantic, konfirmasi pembayaran tunai, dan ajukan pencairan komisi QRIS.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="card-ap r-card p-3 d-flex align-items-center gap-2 bg-mint-l">
            <span class="ms tx-mintt">check_circle</span><span class="fw-semibold fs-12">{{ session('status') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="card-ap r-card p-3 d-flex align-items-center gap-2 bg-errc-l">
            <span class="ms tx-err">error</span><span class="fw-semibold fs-12 tx-err">{{ session('error') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="card-ap r-card p-3 d-flex align-items-center gap-2 bg-errc-l">
            <span class="ms tx-err">error</span><span class="fw-semibold fs-12 tx-err">{{ $errors->all()[0] }}</span>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-7">
            <x-ui.card title="Tautan Toko">
                @if ($storeUrl)
                    <p class="fs-12 tx-mute">Pelanggan dapat memilih produk dan membayar tanpa perlu membuat akun atau mengintegrasikan API.</p>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <code class="ap-log-pre flex-grow-1">{{ $storeUrl }}</code>
                        <button type="button" class="btn-soft" data-copy="{{ $storeUrl }}" data-copy-label="Tautan toko">
                            <span class="ms ms-sm">content_copy</span> Salin
                        </button>
                        <a href="{{ $storeUrl }}" class="btn-navy" target="_blank" rel="noopener">Lihat toko</a>
                    </div>
                    <div class="fs-11 tx-mute mt-3">Harga pelanggan mengikuti harga jual publik Atlantic. QRIS dibayar ke Midtrans; biaya QRIS ditanggung pelanggan. Pembayaran tunai diserahkan langsung kepadamu.</div>
                @else
                    <p class="fs-12 tx-mute mb-0">Toko sedang tidak aktif karena keanggotaan reseller dinonaktifkan. Riwayat komisi dan permintaan pencairan tetap dapat dikelola.</p>
                @endif
            </x-ui.card>
        </div>
        <div class="col-lg-5">
            <x-ui.stat-card
                label="Komisi QRIS tersedia"
                value="Rp {{ number_format($availableCommission, 0, ',', '.') }}"
                icon="account_balance_wallet"
                tone="bg-mint-l tx-mintt"
            />
        </div>
    </div>

    <x-ui.card title="Riwayat Komisi QRIS">
        @if ($commissionHistory->isEmpty())
            <p class="fs-12 tx-mute mb-0">Komisi akan tercatat setelah pesanan QRIS berhasil diproses.</p>
        @else
            <div class="d-flex flex-column gap-2">
                @foreach ($commissionHistory as $commission)
                    <div class="card-ap r-sm p-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <div class="fw-semibold fs-12">{{ $commission->order?->product?->name ?? 'Produk' }}</div>
                            <div class="tx-mute fs-11">
                                {{ $commission->order?->order_number ?? 'Pesanan' }}
                                · {{ $commission->created_at?->format('d/m/Y H:i') }}
                            </div>
                        </div>
                        <span class="num fw-semibold tx-mintt">+Rp {{ number_format($commission->amount, 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.card>

    <x-ui.card title="Konfirmasi Pembayaran Tunai">
        <p class="tx-mute fs-12">Konfirmasi hanya setelah uang tunai benar-benar kamu terima. Saldo operasional reseller dipotong sebesar harga reseller saat dikonfirmasi.</p>
        @if ($pendingCashOrders->isEmpty())
            <div class="card-ap r-sm p-3 fs-12 tx-mute">Belum ada pesanan tunai yang menunggu konfirmasi.</div>
        @else
            <div class="d-flex flex-column gap-2">
                @foreach ($pendingCashOrders as $order)
                    @php($salePrice = (int) ($order->metadata['storefront']['selling_price'] ?? $order->amount))
                    <div class="card-ap r-sm p-3 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                        <div>
                            <div class="fw-semibold fs-13">{{ $order->product?->name ?? 'Produk' }} · {{ $order->order_number }}</div>
                            <div class="tx-mute fs-11">{{ $order->target }}@if($order->target_zone) · Zone {{ $order->target_zone }}@endif</div>
                            <div class="fs-11 mt-1">Harga pelanggan: <span class="num fw-semibold">Rp {{ number_format($salePrice, 0, ',', '.') }}</span> · Potong saldo: <span class="num fw-semibold">Rp {{ number_format($order->amount, 0, ',', '.') }}</span></div>
                        </div>
                        <form method="POST" action="{{ route('settings.reseller.store.cash.confirm', $order) }}"
                              data-confirm="Pastikan kamu sudah menerima tunai dari pelanggan. Saldo operasional akan dipotong Rp {{ number_format($order->amount, 0, ',', '.') }}. Lanjutkan?"
                              data-confirm-title="Konfirmasi tunai" data-confirm-ok="Tunai diterima">
                            @csrf
                            <button type="submit" class="btn-ap btn-mint justify-content-center">
                                <span class="ms ms-sm">payments</span> Tunai diterima
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.card>

    <div class="row g-3">
        <div class="col-lg-6">
            <x-ui.card title="Ajukan Pencairan Komisi">
                @if ($bankAccounts->isEmpty())
                    <p class="fs-12 tx-mute">Tambahkan rekening tujuan sebelum meminta pencairan.</p>
                    <a href="{{ route('settings.banks.index') }}" class="btn-soft">Kelola rekening bank</a>
                @else
                    <form method="POST" action="{{ route('settings.reseller.store.payouts.store') }}" class="d-flex flex-column gap-3">
                        @csrf
                        <x-ui.select
                            name="bank_account_id"
                            label="Rekening tujuan *"
                            placeholder="Pilih rekening"
                            :options="$bankAccounts->mapWithKeys(fn ($account) => [
                                $account->id => $account->bank_name.' · '.$account->account_number.' · '.$account->account_holder,
                            ])->all()"
                            required
                        />
                        <x-ui.input
                            name="amount"
                            type="number"
                            label="Nominal pencairan *"
                            min="1"
                            :max="$availableCommission"
                            step="1"
                            placeholder="Masukkan nominal rupiah"
                            :hint="'Maksimal Rp '.number_format($availableCommission, 0, ',', '.')"
                            required
                        />
                        @error('amount')<div class="err show">{{ $message }}</div>@enderror
                        <button type="submit" class="btn-ap btn-mint justify-content-center" @disabled($availableCommission < 1)>
                            Ajukan pencairan
                        </button>
                    </form>
                @endif
            </x-ui.card>
        </div>
        <div class="col-lg-6">
            <x-ui.card title="Riwayat Pencairan">
                @if ($payouts->isEmpty())
                    <p class="fs-12 tx-mute mb-0">Belum ada permintaan pencairan.</p>
                @else
                    <div class="d-flex flex-column gap-2">
                        @foreach ($payouts as $payout)
                            <div class="card-ap r-sm p-3 d-flex align-items-center justify-content-between gap-2">
                                <div>
                                    <div class="fw-semibold fs-12">Rp {{ number_format($payout->amount, 0, ',', '.') }}</div>
                                    <div class="tx-mute fs-11">{{ $payout->bank_name }} · {{ $payout->account_number }} · {{ $payout->created_at?->format('d/m/Y H:i') }}</div>
                                </div>
                                <span class="pill {{ $payout->status === 'paid' ? 'bg-mint-l tx-mintt' : ($payout->status === 'rejected' ? 'bg-errc-l tx-err' : 'bg-gold tx-gold') }}">
                                    {{ match ($payout->status) { 'paid' => 'Dibayar', 'rejected' => 'Ditolak', default => 'Menunggu' } }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>
        </div>
    </div>
</div>
@endsection
