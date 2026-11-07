@extends('layouts.admin')

@section('title', 'Pencairan Reseller - Admin Atlantic Pedia')
@section('page-title', 'Pencairan Reseller')

@section('content')
<div class="d-flex flex-column gap-4">
    <div>
        <h1 class="fw-bold fs-4 m-0">Pencairan Komisi Reseller</h1>
        <p class="tx-mute fs-12 m-0">Verifikasi permintaan, lakukan transfer manual, lalu tandai hasilnya.</p>
    </div>

    @if (session('success'))
        <div class="card-ap r-card p-3 bg-mint-l fs-12 fw-semibold">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="card-ap r-card p-3 bg-errc-l fs-12 tx-err">{{ $errors->all()[0] }}</div>
    @endif

    <div class="row g-3 row-cols-1 row-cols-sm-3">
        <div class="col"><x-ui.stat-card label="Menunggu verifikasi" value="{{ number_format($stats['pending'], 0, ',', '.') }}" icon="pending_actions" tone="bg-gold tx-gold" /></div>
        <div class="col"><x-ui.stat-card label="Nominal menunggu" value="Rp {{ number_format($stats['pendingAmount'], 0, ',', '.') }}" icon="payments" tone="bg-sky-l tx-navy" /></div>
        <div class="col"><x-ui.stat-card label="Total telah dibayar" value="Rp {{ number_format($stats['paidAmount'], 0, ',', '.') }}" icon="account_balance" tone="bg-mint-l tx-mintt" /></div>
    </div>

    <x-ui.data-table
        id="resellerPayouts"
        title="Permintaan Pencairan"
        :server="false"
        :filters="[
            ['name' => 'status', 'col' => 'status', 'label' => 'Semua status', 'options' => ['pending' => 'Menunggu', 'paid' => 'Dibayar', 'rejected' => 'Ditolak']],
        ]"
        :columns="[
            ['label' => 'Reseller'],
            ['label' => 'Nominal'],
            ['label' => 'Rekening'],
            ['label' => 'Diajukan'],
            ['label' => 'Status'],
            ['label' => 'Aksi'],
        ]"
    >
        @forelse ($payouts as $payout)
            <tr data-status="{{ $payout->status }}">
                <td>
                    <div class="fw-semibold">{{ $payout->user?->name ?? 'Akun dihapus' }}</div>
                    <div class="tx-mute fs-11">{{ $payout->user?->email }}</div>
                </td>
                <td class="num fw-semibold">Rp {{ number_format($payout->amount, 0, ',', '.') }}</td>
                <td>
                    <div>{{ $payout->bank_name }} · {{ $payout->account_holder }}</div>
                    <div class="num tx-mute fs-11">{{ $payout->account_number }}</div>
                </td>
                <td>{{ $payout->created_at?->format('d/m/Y H:i') }}</td>
                <td>
                    <span class="pill {{ $payout->status === 'paid' ? 'bg-mint-l tx-mintt' : ($payout->status === 'rejected' ? 'bg-errc-l tx-err' : 'bg-gold tx-gold') }}">
                        {{ match ($payout->status) { 'paid' => 'Dibayar', 'rejected' => 'Ditolak', default => 'Menunggu' } }}
                    </span>
                    @if ($payout->admin_note)<div class="tx-mute fs-11 mt-1">{{ $payout->admin_note }}</div>@endif
                </td>
                <td>
                    @if ($payout->status === 'pending' && auth()->user()->can('resellers.payouts.update'))
                        <div class="d-flex flex-wrap gap-1">
                            <form method="POST" action="{{ route('admin.reseller-payouts.update', $payout) }}"
                                  data-confirm="Pastikan transfer Rp {{ number_format($payout->amount, 0, ',', '.') }} ke rekening ini sudah dilakukan.">
                                @csrf
                                <input type="hidden" name="action" value="paid">
                                <input type="hidden" name="admin_note" value="Transfer manual telah dilakukan.">
                                <button type="submit" class="btn-soft tx-mintt">Tandai dibayar</button>
                            </form>
                            <form method="POST" action="{{ route('admin.reseller-payouts.update', $payout) }}"
                                  data-confirm="Tolak permintaan pencairan ini? Komisi akan tersedia kembali bagi reseller."
                                  data-confirm-danger>
                                @csrf
                                <input type="hidden" name="action" value="rejected">
                                <input type="hidden" name="admin_note" value="Permintaan ditolak oleh admin.">
                                <button type="submit" class="btn-soft tx-err">Tolak</button>
                            </form>
                        </div>
                    @else
                        <span class="tx-mute fs-11">{{ $payout->processor?->name ?? '-' }}</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center tx-mute py-4">Belum ada permintaan pencairan.</td></tr>
        @endforelse
        <x-slot:footer>{{ $payouts->links() }}</x-slot:footer>
    </x-ui.data-table>
</div>
@endsection
