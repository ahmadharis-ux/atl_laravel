@php($kind = $tab === 'data' ? 'data' : 'pulsa')

@forelse ($products as $p)
    @include('user.product._card', ['p' => $p])
@empty
    @if ($awaitingOperator)
        <x-ui.empty-state
            class="empty-state-fill"
            icon="sim_card"
            title="Masukkan nomor HP dulu"
            description="Paket {{ $kind }} akan muncul setelah nomor tujuan dideteksi. Isi nomor di kolom Nomor Tujuan di atas."
        />
    @elseif ($filtering ?? false)
        <x-ui.empty-state
            class="empty-state-fill"
            icon="search_off"
            title="Tidak ada paket sesuai filter"
            description="Tidak ada paket {{ $kind }} dalam rentang harga tersebut. Coba ubah atau reset filter."
        />
    @else
        <x-ui.empty-state
            class="empty-state-fill"
            icon="search_off"
            title="Belum ada paket {{ $kind }}"
            description="Operator {{ $operatorName }} belum memiliki paket {{ $kind }} yang tersedia saat ini."
        />
    @endif
@endforelse
