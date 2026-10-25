@extends('layouts.app')

@section('title', 'Reseller - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-4">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('settings.index') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-4 m-0">Reseller</h1>
            <p class="tx-mute fs-12 m-0">Jual ulang produk Atlantic Pedia dengan harga khusus reseller.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="card-ap r-card p-3 d-flex align-items-center gap-2 bg-mint-l">
            <span class="ms tx-mintt">check_circle</span>
            <span class="fw-semibold fs-12">{{ session('status') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="card-ap r-card p-3 d-flex align-items-center gap-2 bg-errc-l">
            <span class="ms tx-err">error</span>
            <span class="fw-semibold fs-12 tx-err">{{ session('error') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="card-ap r-card p-3 d-flex align-items-center gap-2 bg-errc-l">
            <span class="ms tx-err">error</span>
            <span class="fw-semibold fs-12 tx-err">{{ $errors->all()[0] }}</span>
        </div>
    @endif

    @if (! $user->is_reseller)
        @if ($user->reseller_since !== null)
            <x-ui.card title="Komisi dan Pencairan">
                <p class="fs-12 tx-mute">Keanggotaan toko sedang nonaktif, tetapi komisi yang sudah diperoleh tetap dapat dipantau dan dicairkan.</p>
                <a href="{{ route('settings.reseller.store') }}" class="btn-soft">Buka riwayat komisi &amp; pencairan</a>
            </x-ui.card>
        @endif

        @if ($user->reseller_status === 'pending')
            <x-ui.card title="Permohonan Sedang Ditinjau">
                <div class="d-flex align-items-start gap-2">
                    <span class="ms tx-gold">pending</span>
                    <p class="fs-12 mb-0">Data dan persetujuan ketentuan sudah diterima. Toko dan API akan tersedia setelah admin menyetujui permohonan.</p>
                </div>
                <p class="fs-11 tx-mute mt-2 mb-0">Dikirim {{ $user->reseller_terms_accepted_at?->locale('id')->translatedFormat('d M Y, H:i') }} · Ketentuan versi {{ $user->reseller_terms_version }}</p>
            </x-ui.card>
        @elseif ($user->reseller_status === 'suspended')
            <x-ui.card title="Keanggotaan Ditangguhkan">
                <p class="fs-12 mb-1">Akses toko dan API dinonaktifkan sementara. Riwayat transaksi, komisi, dan pencairan tetap tersimpan.</p>
                @if ($user->reseller_status_reason)
                    <p class="fs-12 tx-err mb-0"><span class="fw-semibold">Alasan:</span> {{ $user->reseller_status_reason }}</p>
                @endif
            </x-ui.card>
        @elseif ($user->reseller_status === 'rejected')
            <x-ui.card title="Permohonan Belum Disetujui">
                <p class="fs-12 mb-1">Kamu dapat memperbarui data dan mengirim permohonan kembali.</p>
                @if ($user->reseller_status_reason)
                    <p class="fs-12 tx-err mb-0"><span class="fw-semibold">Catatan admin:</span> {{ $user->reseller_status_reason }}</p>
                @endif
            </x-ui.card>
        @endif

        @if (! in_array($user->reseller_status, ['pending', 'suspended'], true))
            <x-ui.card title="Menjadi Reseller">
                <p class="fs-13 mb-3">
                    Ajukan keanggotaan reseller Atlantic Pedia untuk mendapatkan harga khusus
                    <span class="fw-semibold">{{ rtrim(rtrim(number_format($discountPercent, 0, ',', '.'), '0'), '.') }}% lebih murah</span>
                    melalui API. Kamu juga dapat menggunakan toko hosted tanpa integrasi API.
                </p>

                <div class="row g-3 mb-3">
                    <div class="col-sm-4">
                        <div class="card-ap r-sm p-3 h-100">
                            <span class="ibox ibox-48 bg-mint-l tx-mintt mb-2">{{ rtrim(rtrim(number_format($discountPercent, 0, ',', '.'), '0'), '.') }}%</span>
                            <div class="fw-semibold fs-12">Harga Lebih Murah</div>
                            <div class="tx-mute fs-11">Harga reseller berlaku untuk pesanan API atau konfirmasi tunai toko hosted.</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card-ap r-sm p-3 h-100">
                            <span class="ibox ibox-48 bg-sky-l tx-navy mb-2"><span class="ms">api</span></span>
                            <div class="fw-semibold fs-12">Akses API &amp; API Key</div>
                            <div class="tx-mute fs-11">Opsional. API key baru dibuat setelah permohonan disetujui.</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card-ap r-sm p-3 h-100">
                            <span class="ibox ibox-48 bg-gold tx-gold mb-2"><span class="ms">payments</span></span>
                            <div class="fw-semibold fs-12">Toko Hosted</div>
                            <div class="tx-mute fs-11">Pelanggan membayar harga publik; komisi QRIS dapat diminta pencairannya.</div>
                        </div>
                    </div>
                </div>

                <div class="card-ap r-card p-3 mb-3 bg-sky-l">
                    <h2 class="fw-semibold fs-13 mb-2">Ketentuan Reseller · Versi {{ $termsVersion }}</h2>
                    <ul class="fs-12 tx-mute mb-0 ps-3">
                        <li>Data usaha dan kontak harus benar serta dapat digunakan admin untuk menghubungi kamu.</li>
                        <li>Domain wajib untuk integrasi API; toko hosted dapat digunakan tanpa domain sendiri.</li>
                        <li>IP server bersifat opsional. Jika diisi, hanya alamat tersebut yang boleh mengakses API.</li>
                        <li>Jaga API key secara rahasia. Membuat ulang key langsung mencabut key sebelumnya.</li>
                        <li>Harga API memakai harga reseller dan dibayar dari saldo; toko hosted memakai harga publik.</li>
                        <li>Atlantic Pedia dapat menolak atau menangguhkan akses bila ditemukan penyalahgunaan atau pelanggaran.</li>
                        <li>Penangguhan menghentikan pesanan API dan toko baru, tetapi tidak menghapus riwayat transaksi dan komisi.</li>
                    </ul>
                    <a href="{{ route('pages.terms') }}" class="fs-11">Baca syarat dan ketentuan umum</a>
                </div>

                <form method="POST" action="{{ route('settings.reseller.activate') }}" class="d-flex flex-column gap-3">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <x-ui.select
                                name="sales_channel"
                                label="Cara berjualan *"
                                placeholder="Pilih kanal penjualan"
                                :options="['hosted' => 'Toko hosted Atlantic', 'api' => 'Website sendiri dengan API', 'both' => 'Keduanya']"
                                :value="old('sales_channel', $user->reseller_sales_channel)"
                                required
                            />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input
                                name="website"
                                type="url"
                                label="Domain / website"
                                :value="old('website', $user->reseller_website)"
                                placeholder="https://contoh.com"
                                hint="Wajib untuk integrasi API; opsional jika hanya memakai toko hosted."
                            />
                        </div>
                        <div class="col-md-6">
                            <x-ui.select
                                name="contact_channel"
                                label="Kanal kontak *"
                                placeholder="Pilih kanal kontak"
                                :options="['telegram' => 'Telegram', 'whatsapp' => 'WhatsApp', 'other' => 'Lainnya']"
                                :value="old('contact_channel', $user->reseller_contact_channel)"
                                required
                            />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input
                                name="contact"
                                label="Username, nomor, atau tautan kontak *"
                                :value="old('contact', $user->reseller_contact)"
                                placeholder="@username atau tautan"
                                required
                                maxlength="255"
                            />
                        </div>
                        <div class="col-12">
                            <x-ui.input
                                name="allowed_ips"
                                label="IP server untuk API (opsional)"
                                :value="old('allowed_ips', implode(', ', $user->reseller_allowed_ips ?? []))"
                                placeholder="203.0.113.10, 2001:db8::10"
                                hint="Pisahkan beberapa IP dengan koma atau spasi. Maksimal 20 alamat; pastikan IP server stabil."
                            />
                        </div>
                    </div>

                    <x-ui.check-card
                        name="accept_terms"
                        label="Saya sudah membaca dan menyetujui Ketentuan Reseller versi {{ $termsVersion }}."
                        required
                        :checked="old('accept_terms')"
                    />
                    @error('accept_terms')<div class="err show">{{ $message }}</div>@enderror
                    @error('reseller')<div class="err show">{{ $message }}</div>@enderror

                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn-navy">Kirim Permohonan</button>
                        <a href="{{ route('settings.reseller.docs') }}" class="btn-soft">Lihat Dokumentasi API</a>
                    </div>
                </form>
            </x-ui.card>
        @endif
    @else
        <div class="card-ap r-card p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <span class="ibox ibox-48 bg-mint-l tx-mintt"><span class="ms">storefront</span></span>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-semibold fs-13">Reseller Aktif</span>
                        <span class="pill bg-mint-l tx-mintt">Aktif</span>
                    </div>
                    <div class="tx-mute fs-11">Menjadi reseller sejak {{ $user->reseller_since?->locale('id')->translatedFormat('d F Y') }} · Diskon {{ rtrim(rtrim(number_format($discountPercent, 0, ',', '.'), '0'), '.') }}% dari harga jual</div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('settings.reseller.store') }}" class="btn-ap btn-mint">Kelola Toko &amp; Komisi</a>
                <a href="{{ route('settings.reseller.docs') }}" class="btn-soft">Dokumentasi API</a>
            </div>
        </div>

        <x-ui.card title="API Key">
            <x-slot:header>
                <span class="pill bg-sky-l tx-navy">Kredensial API</span>
            </x-slot:header>

            @if (session('api_key'))
                <p class="fs-12 tx-mute mb-2">
                    Ini API key kamu. <span class="fw-semibold">Salin dan simpan di tempat aman sekarang</span> —
                    key asli tidak akan ditampilkan lagi setelah halaman ini ditutup.
                </p>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <pre class="ap-log-pre flex-grow-1 mb-0">{{ session('api_key') }}</pre>
                    <button type="button" class="btn-soft" data-copy="{{ session('api_key') }}" data-copy-label="API Key">
                        <span class="ms ms-sm">content_copy</span> Salin
                    </button>
                </div>
            @else
                <p class="fs-12 tx-mute mb-2">
                    @if ($user->reseller_api_key_hint)
                        API key aktif: <span class="fw-semibold num">{{ $user->reseller_api_key_hint }}</span>
                        (hanya awalan yang ditampilkan). Server menyimpan hash key, bukan key aslinya.
                    @else
                        Belum ada API key. Buat key jika kamu akan memakai integrasi API.
                    @endif
                </p>
            @endif

            <div class="d-flex flex-wrap gap-2">
                <form method="POST" action="{{ route('settings.reseller.regenerate') }}"
                      data-confirm="Buat ulang API key akan menonaktifkan key lama. Pastikan kamu sudah menyimpan key baru. Lanjutkan?">
                    @csrf
                    <button type="submit" class="btn-soft">
                        <span class="ms ms-sm">{{ $user->reseller_api_key_hint ? 'refresh' : 'key' }}</span>
                        {{ $user->reseller_api_key_hint ? 'Buat Ulang API Key' : 'Buat API Key' }}
                    </button>
                </form>
                <form method="POST" action="{{ route('settings.reseller.deactivate') }}"
                      data-confirm="Nonaktifkan keanggotaan reseller? API key akan dicabut dan akses API langsung berhenti."
                      data-confirm-title="Nonaktifkan Reseller" data-confirm-ok="Nonaktifkan" data-confirm-danger>
                    @csrf
                    <button type="submit" class="btn-soft tx-err">
                        <span class="ms ms-sm">person_off</span> Nonaktifkan
                    </button>
                </form>
            </div>
        </x-ui.card>

        <x-ui.card title="Cara Pakai Singkat">
            <ol class="fs-12 mb-3 ps-3">
                <li>Ambil daftar produk &amp; harga reseller lewat <code>GET {{ url('/api/v1/reseller/products') }}</code>.</li>
                <li>Pastikan saldo kamu cukup untuk membayar pesanan (harga reseller).</li>
                <li>Buat pesanan lewat <code>POST {{ url('/api/v1/reseller/orders') }}</code> dengan tujuan (target) produk.</li>
                <li>Pantau status pesanan lewat <code>GET {{ url('/api/v1/reseller/orders') }}</code>.</li>
            </ol>
            <a href="{{ route('settings.reseller.docs') }}" class="btn-navy">Buka Dokumentasi API Lengkap</a>
        </x-ui.card>
    @endif
</div>
@endsection
