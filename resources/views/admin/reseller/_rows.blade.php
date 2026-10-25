@forelse ($items as $item)
    @php
        $status = $item->reseller_status ?? ($item->is_reseller ? 'active' : 'inactive');
        $statusLabels = [
            'pending' => ['Menunggu review', 'bg-gold tx-gold'],
            'active' => ['Aktif', 'bg-mint-l tx-mintt'],
            'suspended' => ['Ditangguhkan', 'bg-errc-l tx-err'],
            'rejected' => ['Ditolak', 'bg-errc-l tx-err'],
            'inactive' => ['Tidak aktif', 'bg-sky-l tx-navy'],
        ];
        [$statusLabel, $statusTone] = $statusLabels[$status] ?? $statusLabels['inactive'];
        $contactChannel = match ($item->reseller_contact_channel) {
            'telegram' => 'Telegram',
            'whatsapp' => 'WhatsApp',
            'other' => 'Lainnya',
            default => null,
        };
    @endphp
    <tr>
        <td>
            <div class="fw-semibold">{{ $item->name }}</div>
            <div class="tx-mute fs-12">{{ $item->email }}</div>
        </td>
        <td class="fs-12">
            @if ($item->reseller_website)
                <a href="{{ $item->reseller_website }}" target="_blank" rel="noopener noreferrer">{{ $item->reseller_website }}</a>
            @else
                <span class="tx-mute">Tidak dicantumkan</span>
            @endif
            <div class="tx-mute">{{ ['hosted' => 'Toko hosted', 'api' => 'Website + API', 'both' => 'Hosted + API'][$item->reseller_sales_channel] ?? '-' }}</div>
        </td>
        <td class="fs-12">
            <div>{{ $contactChannel ? $contactChannel.' · ' : '' }}{{ $item->reseller_contact ?? '-' }}</div>
            <div class="tx-mute">IP API: {{ $item->reseller_allowed_ips ? implode(', ', $item->reseller_allowed_ips) : 'tanpa allowlist' }}</div>
        </td>
        <td class="tx-mute fs-12 text-nowrap">
            {{ ($item->reseller_since ?? $item->reseller_terms_accepted_at)?->locale('id')->translatedFormat('d M Y') ?? '-' }}
        </td>
        <td>
            <span class="pill {{ $statusTone }}">{{ $statusLabel }}</span>
            @if ($item->reseller_status_reason)
                <div class="tx-err fs-11 mt-1">{{ $item->reseller_status_reason }}</div>
                <div class="tx-mute fs-11">{{ $item->resellerReviewer?->name ?? 'Admin' }} · {{ $item->reseller_reviewed_at?->locale('id')->translatedFormat('d M Y, H:i') }}</div>
            @elseif ($item->reseller_terms_version)
                <div class="tx-mute fs-11">Setuju ketentuan v{{ $item->reseller_terms_version }}</div>
            @endif
        </td>
        <td class="tx-mute fs-12 text-nowrap">
            @if ($item->reseller_last_api_at)
                <span title="{{ $item->reseller_last_api_at->locale('id')->translatedFormat('d M Y, H:i:s') }}">
                    {{ $item->reseller_last_api_at->locale('id')->diffForHumans() }}
                </span>
            @else
                Belum pernah
            @endif
        </td>
        <td class="num">{{ number_format($item->orders_count, 0, ',', '.') }}</td>
        <td class="num">{{ number_format($item->api_orders_count, 0, ',', '.') }}</td>
        <td class="num text-end fw-semibold">Rp {{ number_format((int) ($item->api_amount_sum ?? 0), 0, ',', '.') }}</td>
        <td class="num fs-12 tx-mute">{{ $item->reseller_api_key_hint ?? '-' }}</td>
        <td>
            @can('resellers.update')
                @if (in_array($status, ['pending', 'active', 'suspended'], true))
                    <x-ui.actions :actions="[[
                        'label' => 'Kelola status reseller',
                        'icon' => 'manage_accounts',
                        'tone' => 'tx-navy',
                        'modal' => 'modalResellerStatus-'.$item->getKey(),
                    ]]" />

                    <x-ui.modal
                        :id="'modalResellerStatus-'.$item->getKey()"
                        :title="'Kelola reseller · '.$item->name"
                        size="sm"
                    >
                        <div class="d-flex flex-column gap-3">
                            @if ($item->resellerStatusHistories->isNotEmpty())
                                <section>
                                    <h3 class="fw-semibold fs-12 mb-2">Riwayat Status</h3>
                                    <div class="d-flex flex-column gap-2">
                                        @foreach ($item->resellerStatusHistories as $history)
                                            <div class="card-ap r-sm p-2">
                                                <div class="d-flex justify-content-between gap-2">
                                                    <span class="fw-semibold fs-11">
                                                        {{ str($history->from_status ?? 'baru')->headline() }}
                                                        → {{ str($history->to_status)->headline() }}
                                                    </span>
                                                    <span class="tx-mute fs-11">{{ $history->created_at->locale('id')->translatedFormat('d M Y, H:i') }}</span>
                                                </div>
                                                <div class="tx-mute fs-11">{{ $history->actor?->name ?? 'Reseller' }}</div>
                                                @if ($history->reason)
                                                    <div class="fs-11 mt-1">{{ $history->reason }}</div>
                                                @endif
                                                @if ($history->terms_version)
                                                    <div class="tx-mute fs-11 mt-1">
                                                        Setuju ketentuan v{{ $history->terms_version }}
                                                        @if ($history->terms_accepted_at)
                                                            · {{ $history->terms_accepted_at->locale('id')->translatedFormat('d M Y, H:i') }}
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            @endif

                            @if ($status === 'pending')
                                <form method="POST" action="{{ route('admin.resellers.status.update', $item) }}" class="d-flex flex-column gap-2">
                                    @csrf
                                    <input type="hidden" name="action" value="approve">
                                    <p class="fs-12 mb-0">Setujui permohonan ini? Reseller dapat membuat API key dari halaman pengaturannya.</p>
                                    <button type="submit" class="btn-ap btn-mint justify-content-center">Setujui Permohonan</button>
                                </form>
                                <form method="POST" action="{{ route('admin.resellers.status.update', $item) }}" class="d-flex flex-column gap-2"
                                      data-confirm="Tolak permohonan reseller ini?" data-confirm-title="Tolak Permohonan" data-confirm-danger>
                                    @csrf
                                    <input type="hidden" name="action" value="reject">
                                    <x-ui.input name="reason" :id="'reject-reason-'.$item->getKey()" label="Alasan penolakan *" maxlength="1000" required />
                                    <button type="submit" class="btn-soft tx-err justify-content-center">Tolak Permohonan</button>
                                </form>
                            @elseif ($status === 'active')
                                <form method="POST" action="{{ route('admin.resellers.status.update', $item) }}" class="d-flex flex-column gap-2"
                                      data-confirm="Tangguhkan reseller ini? API key akan dicabut dan pesanan baru dari API serta toko hosted akan dihentikan."
                                      data-confirm-title="Tangguhkan Reseller" data-confirm-ok="Tangguhkan" data-confirm-danger>
                                    @csrf
                                    <input type="hidden" name="action" value="suspend">
                                    <x-ui.input name="reason" :id="'suspend-reason-'.$item->getKey()" label="Alasan penangguhan *" maxlength="1000" required />
                                    <button type="submit" class="btn-soft tx-err justify-content-center">Tangguhkan Reseller</button>
                                </form>
                            @elseif ($status === 'suspended')
                                <form method="POST" action="{{ route('admin.resellers.status.update', $item) }}" class="d-flex flex-column gap-2">
                                    @csrf
                                    <input type="hidden" name="action" value="reactivate">
                                    <p class="fs-12 mb-0">Aktifkan kembali toko dan akses reseller? API key lama sudah dicabut; reseller perlu membuat key baru.</p>
                                    <button type="submit" class="btn-ap btn-mint justify-content-center">Aktifkan Kembali</button>
                                </form>
                            @endif
                        </div>
                    </x-ui.modal>
                @endif
            @endcan
        </td>
    </tr>
@empty
    <tr>
        <td colspan="11">
            <x-ui.empty-state icon="storefront" title="Belum ada pengajuan reseller"
                description="Permohonan reseller akan muncul di sini untuk ditinjau." />
        </td>
    </tr>
@endforelse
