@extends('layouts.admin')

@section('title', 'Detail Pengguna - Admin Atlantic Pedia')
@section('page-title', 'Detail Pengguna')

@section('content')
@php
    $role = $user->primaryRole();
    $isReseller = $user->reseller_since !== null || $user->is_reseller;
@endphp

<div class="d-flex flex-column gap-4">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.users.index') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-4 m-0">Detail Pengguna</h1>
            <p class="tx-mute fs-12 m-0">Informasi akun dan riwayat transaksi.</p>
        </div>
        @can('users.update')
            @if (\App\Support\Rbac::canManageUser(auth()->user(), $user))
                <a href="{{ route('admin.users.edit', $user) }}" class="btn-soft ms-auto"><span class="ms ms-sm">edit</span> Ubah</a>
            @endif
        @endcan
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <x-ui.card title="Profil Pengguna">
                <div class="d-flex align-items-center gap-3">
                    <span class="ibox ibox-48 bg-tealc-m tx-tealt fw-bold rounded-circle">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    <div>
                        <div class="fw-bold fs-5">{{ $user->name }}</div>
                        <div class="tx-mute fs-12">{{ $user->email }} · {{ $user->phone ?: 'Telepon belum diisi' }}</div>
                        <div class="d-flex gap-2 mt-2">
                            <span class="pill {{ \App\Support\Roles::tone($role ?? \App\Support\Roles::USER) }}">{{ $user->roleLabel() }}</span>
                            @if ($isReseller)
                                <span class="pill bg-mint-l tx-mintt">Reseller</span>
                            @endif
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>
        <div class="col-lg-5">
            <div class="row g-3 row-cols-2">
                <div class="col"><x-ui.stat-card label="Saldo" value="Rp {{ number_format((int) $user->balance, 0, ',', '.') }}" icon="account_balance_wallet" tone="bg-sky-l tx-navy" /></div>
                <div class="col"><x-ui.stat-card label="Poin" value="{{ number_format((int) $user->points, 0, ',', '.') }}" icon="stars" tone="bg-gold tx-gold" /></div>
            </div>
        </div>
    </div>

    @if ($isReseller)
        <div class="row g-3 row-cols-1 row-cols-md-3">
            <div class="col"><x-ui.stat-card label="Status reseller" value="{{ str($user->reseller_status ?? 'active')->headline() }}" icon="storefront" tone="bg-mint-l tx-mintt" /></div>
            <div class="col"><x-ui.stat-card label="Total komisi" value="Rp {{ number_format((int) $commissions->sum('amount'), 0, ',', '.') }}" icon="payments" tone="bg-tealc-m tx-tealt" /></div>
            <div class="col"><x-ui.stat-card label="Transaksi" value="{{ number_format($user->orders()->count(), 0, ',', '.') }}" icon="receipt_long" tone="bg-gold tx-gold" /></div>
        </div>
    @endif

    <x-ui.data-table
        id="tblUserOrders"
        title="Riwayat Transaksi"
        :columns="[
            ['label' => 'Pesanan'],
            ['label' => 'Sumber'],
            ['label' => 'Nominal', 'class' => 'text-end'],
            ['label' => 'Status'],
            ['label' => 'Tanggal'],
        ]"
    >
        @forelse ($orders as $order)
            @php($source = match ($order->source) { 'api' => 'API reseller', 'store_cash' => 'Toko · Tunai', 'store_qris' => 'Toko · QRIS', default => ucfirst((string) $order->source) })
            <tr>
                <td><div class="fw-semibold">{{ $order->order_number }}</div><div class="tx-mute fs-11">{{ $order->product?->name ?? 'Produk tidak tersedia' }}</div></td>
                <td><span class="pill bg-sky-l tx-navy fs-11">{{ $source }}</span></td>
                <td class="num text-end">Rp {{ number_format((int) $order->amount, 0, ',', '.') }}</td>
                <td><span class="pill fs-11 {{ $order->status->tone() }}">{{ $order->status->label() }}</span></td>
                <td class="tx-mute fs-12">{{ $order->created_at->locale('id')->translatedFormat('d M Y, H:i') }}</td>
            </tr>
        @empty
            <tr><td colspan="5"><x-ui.empty-state icon="receipt_long" title="Belum ada transaksi" description="Riwayat transaksi pengguna akan tampil di sini." /></td></tr>
        @endforelse
        <x-slot:footer>
            <span class="fs-12 tx-mute">Menampilkan {{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }} dari {{ $orders->total() }} transaksi</span>
            {{ $orders->links() }}
        </x-slot:footer>
    </x-ui.data-table>

    @if ($isReseller)
        <x-ui.data-table
            id="tblUserCommissions"
            title="Riwayat Komisi Reseller"
            :columns="[
                ['label' => 'Pesanan'],
                ['label' => 'Produk'],
                ['label' => 'Komisi', 'class' => 'text-end'],
                ['label' => 'Tanggal'],
            ]"
        >
            @forelse ($commissions as $commission)
                <tr>
                    <td>{{ $commission->order?->order_number ?? '-' }}</td>
                    <td>{{ $commission->order?->product?->name ?? 'Produk tidak tersedia' }}</td>
                    <td class="num text-end fw-semibold tx-mintt">+Rp {{ number_format((int) $commission->amount, 0, ',', '.') }}</td>
                    <td class="tx-mute fs-12">{{ $commission->created_at->locale('id')->translatedFormat('d M Y, H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="4"><x-ui.empty-state icon="payments" title="Belum ada komisi" description="Komisi reseller akan tampil setelah transaksi QRIS berhasil." /></td></tr>
            @endforelse
        </x-ui.data-table>
    @endif
</div>
@endsection
