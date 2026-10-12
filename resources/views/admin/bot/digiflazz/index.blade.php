@extends('layouts.admin')

@section('title', 'Bot Digiflazz - Admin Atlantic Pedia')
@section('page-title', 'Bot Digiflazz - Konfigurasi Scraper')

@section('content')
<div class="d-flex flex-column gap-4" data-ajax
     data-ajax-url="{{ route('admin.bot.digiflazz.index') }}"
     data-persist-key="admin.bot.digiflazz">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h1 class="fw-bold fs-4 m-0">Bot Digiflazz Scraper</h1>
            <p class="tx-mute fs-12 m-0">Kelola konfigurasi scraper Digiflazz. Masukkan cookies & CSRF token, lalu jalankan sync.</p>
        </div>
        @can('products.update')
            <a href="{{ route('admin.bot.digiflazz.create') }}" class="btn-navy">
                <span class="ms ms-sm align-middle me-1">add</span>Tambah Konfigurasi
            </a>
        @endcan
    </div>

    <x-ui.card>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nama</th>
                        <th>Status</th>
                        <th>CSRF Token</th>
                        <th>Socket ID</th>
                        <th>Terakhir Sync</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody data-ajax-rows>
                    @forelse ($configs as $config)
                        @php
                            $latestLog = $config->syncLogs()->where('type', 'scrape_and_update')->jobRecords()->latest('started_at')->first();
                        @endphp
                        <tr>
                            <td class="fw-bold">{{ $config->name }}</td>
                            <td>
                                @if ($config->is_active)
                                    <span class="pill bg-mint-l tx-mintt">Aktif</span>
                                @else
                                    <span class="pill bg-glass tx-mute">Nonaktif</span>
                                @endif
                            </td>
                            <td class="font-monospace fs-11 text-truncate" style="max-width: 12rem;">{{ Str::mask($config->csrf_token, '*', 4, -4) }}</td>
                            <td class="font-monospace fs-11">{{ $config->socket_id ?? '-' }}</td>
                            <td>{{ $latestLog?->started_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td class="text-end">
                                <x-ui.actions :actions="[
                                    ['label' => 'Edit', 'icon' => 'edit', 'href' => route('admin.bot.digiflazz.edit', $config)],
                                    ['label' => 'Sync Log', 'icon' => 'terminal', 'action' => 'view', 'href' => $latestLog ? route('admin.bot.digiflazz.sync.show', $latestLog) : route('admin.bot.digiflazz.sync.index')],
                                    ['label' => 'Hapus', 'icon' => 'delete', 'tone' => 'tx-err', 'action' => 'delete', 'url' => route('admin.bot.digiflazz.destroy', $config), 'confirm' => 'Hapus konfigurasi ini?', 'confirm_danger' => true],
                                ]" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center tx-mute py-4">Belum ada konfigurasi. Klik "Tambah Konfigurasi" untuk memulai.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-slot:footer>
            <div class="d-flex justify-content-between align-items-center">
                <span class="fs-12 tx-mute">Menampilkan {{ $configs->firstItem() ?? 0 }}–{{ $configs->lastItem() ?? 0 }} dari {{ $configs->total() }} data</span>
                {{ $configs->links() }}
            </div>
        </x-slot:footer>
    </x-ui.card>
</div>
@endsection