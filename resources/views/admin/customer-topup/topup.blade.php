@extends('layouts.admin')

@section('title', 'Customer Topup - Admin Atlantic Pedia')
@section('page-title', 'Customer Topup')

@section('content')
<div class="d-flex flex-column gap-4">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h1 class="fw-bold fs-4 m-0">Transaksi Topup</h1>
            <p class="tx-mute fs-12 m-0">Daftar transaksi topup pelanggan. Data sementara (hardcode).</p>
        </div>
    </div>

    <x-ui.data-table
        id="tblTopup"
        title="Daftar Topup"
        search-placeholder="Cari transaksi, game ID, atau produk..."
        :columns="[
            ['label' => 'Tanggal'],
            ['label' => 'TransID'],
            ['label' => 'Payment Reference'],
            ['label' => 'Products'],
            ['label' => 'Game ID'],
            ['label' => 'Amount', 'class' => 'text-end'],
            ['label' => 'Status'],
            ['label' => 'Sn'],
            ['label' => 'Provider'],
            ['label' => 'Action', 'class' => 'text-end'],
        ]"
    >
        @foreach ($topups as $i => $t)
            @php
                $actions = [['label' => 'Detail', 'icon' => 'visibility', 'href' => '#']];
                if (auth()->user()->can('topups.update')) {
                    $actions[] = [
                        'label' => 'Kirim Ulang',
                        'icon' => 'refresh',
                        'href' => '#',
                        'confirm' => 'Kirim ulang transaksi '.$t['trx'].'?',
                    ];
                }
            @endphp
            <tr data-status="{{ $t['status'] }}">
                <td class="tx-mute fs-12 text-nowrap">{{ $t['date'] }}</td>
                <td><x-ui.tooltip :text="$t['trx']" class="fw-semibold fs-12">{{ $t['trx'] }}</x-ui.tooltip></td>
                <td><x-ui.tooltip :text="$t['ref']" class="tx-mute fs-12">{{ $t['ref'] }}</x-ui.tooltip></td>
                <td>{{ $t['product'] }}</td>
                <td class="fw-semibold fs-12">{{ $t['game_id'] }}</td>
                <td class="text-end fw-semibold">Rp {{ number_format($t['amount'], 0, ',', '.') }}</td>
                <td><span class="pill {{ $statusTones[$t['status']] ?? 'bg-tealc-m tx-tealt' }}">{{ $t['status'] }}</span></td>
                <td>
                    @if ($t['sn'] === '—')
                        <span class="tx-mute">—</span>
                    @else
                        <x-ui.tooltip :text="$t['sn']" truncate="8rem" class="fs-12 tx-mute">{{ $t['sn'] }}</x-ui.tooltip>
                    @endif
                </td>
                <td><span class="pill bg-tealc-m tx-tealt">{{ $t['provider'] }}</span></td>
                <td class="text-end"><x-ui.actions :actions="$actions" /></td>
            </tr>
        @endforeach

        <x-slot:footer>
            <span class="fs-12 tx-mute">Menampilkan 1–{{ count($topups) }} dari {{ count($topups) }} transaksi</span>
        </x-slot:footer>
    </x-ui.data-table>
</div>
@endsection