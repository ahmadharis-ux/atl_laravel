@extends('layouts.admin')

@section('title', 'Komponen UI - Atlantic Pedia')
@section('page-title', 'Komponen UI')

@php
    $lineConfig = [
        'data' => [
            'labels' => ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
            'datasets' => [[
                'label' => 'Penjualan',
                'data' => [12, 18, 14, 22, 19, 28, 24],
                'borderColor' => '#00696f',
                'backgroundColor' => 'rgba(159,240,247,.35)',
                'fill' => true,
                'tension' => 0.35,
            ]],
        ],
        'options' => ['plugins' => ['legend' => ['display' => false]], 'scales' => ['y' => ['beginAtZero' => true]]],
    ];
    $barConfig = [
        'data' => [
            'labels' => ['Pulsa', 'Data', 'PLN', 'Game', 'Voucher'],
            'datasets' => [[
                'label' => 'Transaksi',
                'data' => [120, 190, 90, 145, 80],
                'backgroundColor' => ['#00696f', '#0b6f76', '#64fdb9', '#9ff0f7', '#a76b00'],
                'borderRadius' => 6,
            ]],
        ],
        'options' => ['plugins' => ['legend' => ['display' => false]]],
    ];
    $doughnutConfig = [
        'data' => [
            'labels' => ['Pulsa', 'Data', 'Token', 'Game'],
            'datasets' => [[
                'data' => [45, 25, 20, 10],
                'backgroundColor' => ['#00696f', '#64fdb9', '#9ff0f7', '#a76b00'],
            ]],
        ],
        'options' => ['plugins' => ['legend' => ['position' => 'bottom']]],
    ];
@endphp

@section('content')
<div class="d-flex flex-column gap-4">

    <div class="card-ap r-card p-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h1 class="fw-bold fs-4 m-0">Komponen UI</h1>
                <p class="tx-mute m-0 fs-12">Dibuat sebagai komponen Blade yang reusable (folder <code>resources/views/components/ui</code>).</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @foreach (['Cards' => '#card','Chart' => '#chart', 'Data Table' => '#table', 'Form' => '#form', 'Editor' => '#editor', 'Wizard' => '#wizard', 'Tooltip' => '#tooltip', 'Aksi' => '#actions', 'Modal' => '#modal'] as $label => $href)
                    <a href="{{ $href }}" class="pill bg-tealc-m tx-tealt">{{ $label }}</a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ================= CARDS ================= --}}
    <section id="card" class="sec">
        <div class="sec-head">
            <span class="ic"><span class="ms">dashboard</span></span>
            <div><h2>Card</h2><p>Komponen <code>&lt;x-ui.stat-card&gt;</code> dan <code>&lt;x-ui.card&gt;</code>.</p></div>
        </div>

        <div class="row row-cols-2 row-cols-lg-4 g-3 mb-3">
            <div class="col"><x-ui.stat-card label="Total Penjualan" value="128.450" icon="payments" tone="bg-tealc-m tx-tealt" trend="+12,5%" /></div>
            <div class="col"><x-ui.stat-card label="Pengguna Aktif" value="2.340" icon="group" tone="bg-mint-l tx-mintt" trend="+4,1%" /></div>
            <div class="col"><x-ui.stat-card label="Transaksi" value="18.902" icon="receipt_long" tone="bg-sky-l tx-navy" trend="+8,7%" /></div>
            <div class="col"><x-ui.stat-card label="Refund" value="132" icon="undo" tone="bg-gold tx-gold" trend="-2,3%" /></div>
        </div>

        <div class="row g-3">
            <div class="col-lg-8">
                <x-ui.card title="Kartu Konten" class="h-100">
                    <x-slot:header><span class="pill bg-mint-l tx-mintt">Aktif</span></x-slot:header>
                    <p class="tx-mute fs-12 mb-3">Contoh kartu dengan header, badge status, body, dan footer aksi.</p>
                    <div class="d-flex gap-2">
                        <button class="btn-navy btn-sm px-3">Simpan</button>
                        <button class="btn-soft btn-sm px-3">Batal</button>
                    </div>
                </x-ui.card>
            </div>
            <div class="col-lg-4">
                <div class="balance r-card d-flex flex-column gap-2 h-100">
                    <span class="fs-11 fw-bold text-uppercase tx-tealc" style="letter-spacing:.08em">Saldo Dompet</span>
                    <div class="d-flex align-items-baseline gap-2">
                        <span class="tx-tealc fw-bold fs-5">Rp</span>
                        <span class="num fs-2 fw-800" style="letter-spacing:-.02em">4.250.000</span>
                    </div>
                    <a href="#" class="btn-ap btn-mint mt-auto"><span class="ms">add</span>Top Up</a>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= CHARTS ================= --}}
    <section id="chart" class="sec">
        <div class="sec-head">
            <span class="ic"><span class="ms">insights</span></span>
            <div><h2>Chart &amp; Grafik</h2><p>Komponen <code>&lt;x-ui.chart&gt;</code> (Chart.js otomatis dimuat).</p></div>
        </div>

        <div class="row g-3 g-lg-4">
            <div class="col-lg-8">
                <x-ui.card title="Penjualan 7 Hari" class="h-100">
                    <x-slot:header><span class="pill bg-tealc-m tx-tealt">Mingguan</span></x-slot:header>
                    <x-ui.chart id="chartLine" type="line" :config="$lineConfig" />
                </x-ui.card>
            </div>
            <div class="col-lg-4">
                <x-ui.card title="Metode Pembayaran" class="h-100">
                    <x-ui.chart id="chartDoughnut" type="doughnut" :config="$doughnutConfig" />
                </x-ui.card>
            </div>
            <div class="col-12">
                <x-ui.card title="Transaksi per Produk">
                    <x-ui.chart id="chartBar" type="bar" :config="$barConfig" />
                </x-ui.card>
            </div>
        </div>
    </section>

    {{-- ================= DATA TABLE ================= --}}
    <section id="table" class="sec">
        <div class="sec-head">
            <span class="ic"><span class="ms">table_chart</span></span>
            <div><h2>Data Table</h2><p>Komponen <code>&lt;x-ui.data-table&gt;</code> dengan pencarian, filter, dan bulk action.</p></div>
        </div>

        <x-ui.data-table
            id="tblUsers"
            title="Daftar Pengguna"
            search-placeholder="Cari pengguna..."
            :bulk="true"
            :columns="[['label' => 'Nama'], ['label' => 'Email'], ['label' => 'Peran'], ['label' => 'Catatan'], ['label' => 'Status'], ['label' => 'Aksi', 'class' => 'text-end']]"
            :filters="[
                ['name' => 'status', 'label' => 'Semua Status', 'options' => ['Aktif' => 'Aktif', 'Pending' => 'Pending', 'Nonaktif' => 'Nonaktif']],
                ['name' => 'peran', 'label' => 'Semua Peran', 'options' => ['Admin' => 'Admin', 'Merchant' => 'Merchant', 'Reseller' => 'Reseller']],
            ]"
            :bulk-actions="[
                ['value' => 'activate', 'label' => 'Aktifkan', 'icon' => 'toggle_on', 'tone' => 'btn-soft'],
                ['value' => 'deactivate', 'label' => 'Nonaktifkan', 'icon' => 'toggle_off', 'tone' => 'btn-soft'],
                ['value' => 'delete', 'label' => 'Hapus', 'icon' => 'delete', 'tone' => 'text-danger border-0 fw-bold'],
            ]"
        >
            @foreach ([
                ['Andi Saputra', 'andi@mail.com', 'Admin', 'Akun administrator utama dengan akses penuh ke seluruh modul aplikasi.', 'Aktif'],
                ['Budi Santoso', 'budi@mail.com', 'Merchant', 'Mitra merchant wilayah Jakarta Selatan, aktif sejak Januari 2024.', 'Aktif'],
                ['Citra Lestari', 'citra@mail.com', 'Merchant', 'Menunggu verifikasi dokumen KTP dan NPWP.', 'Pending'],
                ['Dewi Lestari', 'dewi@mail.com', 'Reseller', 'Reseller tidak aktif, terakhir transaksi 6 bulan lalu.', 'Nonaktif'],
                ['Eko Wijaya', 'eko@mail.com', 'Merchant', 'Merchant dengan volume transaksi tertinggi bulan ini.', 'Aktif'],
            ] as $row)
                @php $tone = $row[4] === 'Aktif' ? 'bg-mint-l tx-mintt' : ($row[4] === 'Pending' ? 'bg-gold tx-gold' : 'bg-errc-l tx-err'); @endphp
                <tr data-status="{{ $row[4] }}" data-peran="{{ $row[2] }}">
                    <td class="chk-cell"><input class="form-check-input m-0 row-check" type="checkbox" aria-label="Pilih baris"></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="ibox" style="width:2rem;height:2rem;border-radius:.5rem;background:rgba(159,240,247,.4);color:var(--tealt);font-size:.8rem;font-weight:700">{{ strtoupper(substr($row[0], 0, 1)) }}</span>
                            <span class="fw-semibold">{{ $row[0] }}</span>
                        </div>
                    </td>
                    <td><x-ui.tooltip :text="$row[1]" truncate="10rem" class="tx-mute fs-12">{{ $row[1] }}</x-ui.tooltip></td>
                    <td><span class="pill bg-tealc-m tx-tealt">{{ $row[2] }}</span></td>
                    <td><x-ui.tooltip :text="$row[3]" truncate="16rem" class="tx-mute fs-12">{{ $row[3] }}</x-ui.tooltip></td>
                    <td><span class="pill {{ $tone }}">{{ $row[4] }}</span></td>
                    <td class="text-end">
                        <x-ui.actions :actions="[
                            ['label' => 'Ubah', 'icon' => 'edit', 'href' => '#'],
                            ['label' => 'Aktifkan', 'icon' => 'toggle_on', 'action' => 'activate'],
                            ['label' => 'Reset Sandi', 'icon' => 'lock_reset', 'action' => 'reset'],
                            ['label' => 'Hapus', 'icon' => 'delete', 'tone' => 'tx-err', 'action' => 'delete', 'confirm' => 'Yakin ingin menghapus data ini?', 'confirm_danger' => true],
                        ]" />
                    </td>
                </tr>
            @endforeach

            <x-slot:footer>
                <span class="fs-12 tx-mute">Menampilkan 1–10 dari 128 data</span>
                <nav>
                    <ul class="pagination pagination-sm m-0">
                        <li class="page-item disabled"><span class="page-link">«</span></li>
                        <li class="page-item active"><span class="page-link">1</span></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item"><a class="page-link" href="#">»</a></li>
                    </ul>
                </nav>
            </x-slot:footer>
        </x-ui.data-table>
    </section>

    {{-- ================= FORM ================= --}}
    <section id="form" class="sec">
        <div class="sec-head">
            <span class="ic"><span class="ms">edit_note</span></span>
            <div><h2>Form</h2><p>Komponen <code>&lt;x-ui.input&gt;</code>, <code>select</code>, <code>search-select</code>, <code>textarea</code>, <code>radio-card</code>, <code>check-card</code>, <code>switch</code>, <code>searchbox</code>.</p></div>
        </div>

        <x-ui.card>
            <div class="row g-3">
                <div class="col-md-6"><x-ui.input name="f-nama" label="Nama Produk" placeholder="Contoh: Pulsa Telkomsel 25.000" /></div>
                <div class="col-md-6"><x-ui.input name="f-email" type="email" label="Email Kontak" placeholder="nama@email.com" /></div>
                <div class="col-md-4">
                    <x-ui.select name="f-kat" label="Kategori" :options="['pulsa' => 'Pulsa', 'data' => 'Paket Data', 'pln' => 'Token PLN', 'game' => 'Voucher Game']" />
                </div>
                <div class="col-md-4"><x-ui.input name="f-harga" type="number" label="Harga" placeholder="25000" /></div>
                <div class="col-md-4"><x-ui.select name="f-status" label="Status" :options="['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif']" /></div>

                <div class="col-md-6">
                    <x-ui.search-select name="f-brand" label="Brand (bisa dicari)" :options="['telkomsel' => 'Telkomsel', 'indosat' => 'Indosat', 'xl' => 'XL Axiata', 'tri' => 'Tri', 'smartfren' => 'Smartfren']" placeholder="Pilih brand..." />
                </div>
                <div class="col-md-6">
                    <x-ui.search-select name="f-seller" label="Seller (remote)" :remote="true" :remote-url="route('admin.lookup', ['type' => 'sellers'])" placeholder="Cari seller..." hint="Opsi diambil dari server saat mengetik." />
                </div>
                <div class="col-12">
                    <x-ui.multi-select name="f-tags" label="Multi-select (bisa dicari, banyak nilai)" :options="['pulsa' => 'Pulsa', 'data' => 'Paket Data', 'pln' => 'Token PLN']" placeholder="Pilih beberapa..." hint="Dipakai untuk filter kategori/brand/type pada Bot Digiflazz." />
                </div>

                <div class="col-md-6">
                    <label class="ui-label">Jenis Produk</label>
                    <div class="d-flex flex-column gap-2">
                        <x-ui.radio-card name="jenis" value="prabayar" label="Prabayar" :checked="true" />
                        <x-ui.radio-card name="jenis" value="pascabayar" label="Pascabayar" />
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="ui-label">Opsi Tambahan</label>
                    <div class="d-flex flex-column gap-2">
                        <x-ui.check-card name="notif" label="Kirim notifikasi WhatsApp" :checked="true" />
                        <x-ui.check-card name="beranda" label="Tampilkan di beranda" />
                        <x-ui.switch name="f-aktif" label="Aktifkan produk" :checked="true" />
                    </div>
                </div>

                <div class="col-12"><x-ui.textarea name="f-desc" label="Deskripsi Singkat" :rows="3" placeholder="Tulis deskripsi produk..." hint="Maksimal 200 karakter." /></div>

                <div class="col-md-6"><x-ui.currency-input name="f-harga" label="Harga" hint="Input mata uang: otomatis format ribuan & rata kanan." /></div>

                <div class="col-12">
                    <label class="ui-label">Pencarian Produk (khusus produk, saran + riwayat)</label>
                    <x-ui.searchbox
                        :action="route('products.index')"
                        :suggest-url="route('products.lookup', ['type' => 'products'])"
                        :clear-url="route('search.history.clear')"
                        :history="['pulsa telkomsel', 'token pln', 'diamond ml']"
                        placeholder="Cari produk..."
                    />
                    <div class="ui-hint">GET ke <code>route('products.index')</code> field <code>q</code>. Ketik untuk saran (lookup <code>type=products</code>); riwayat per pengguna dari DB (props <code>history</code>, <code>suggest-url</code>, <code>clear-url</code>).</div>
                </div>
            </div>
        </x-ui.card>
    </section>

    {{-- ================= EDITOR ================= --}}
    <section id="editor" class="sec">
        <div class="sec-head">
            <span class="ic"><span class="ms">edit_note</span></span>
            <div><h2>Rich Text Editor</h2><p>Komponen <code>&lt;x-ui.editor&gt;</code> (Quill otomatis dimuat).</p></div>
        </div>

        <x-ui.card>
            <x-ui.editor id="editorDemo" name="konten" value="<h2>Judul pengumuman</h2><p>Tulis konten di sini.</p>" />
            <div class="d-flex gap-2 mt-3">
                <button type="button" class="btn-navy"
                    onclick="var q=document.getElementById('editorDemo')._quill; alert(q?q.root.innerHTML:'Editor belum siap');">
                    Lihat HTML
                </button>
                <button class="btn-soft">Reset</button>
            </div>
        </x-ui.card>
    </section>

    {{-- ================= WIZARD ================= --}}
    <section id="wizard" class="sec">
        <div class="sec-head">
            <span class="ic"><span class="ms">linear_scale</span></span>
            <div><h2>Wizard</h2><p>Komponen <code>&lt;x-ui.wizard&gt;</code> — panel didefinisikan lewat slot.</p></div>
        </div>

        <x-ui.wizard id="wzDemo" :steps="['Data Diri', 'Alamat', 'Konfirmasi']" submit-label="Simpan Data">
            <div class="wz-panel active" data-panel="1">
                <div class="row g-3">
                    <div class="col-md-6"><x-ui.input name="wz-nama" label="Nama Lengkap" placeholder="Nama Anda" /></div>
                    <div class="col-md-6"><x-ui.input name="wz-email" type="email" label="Email" placeholder="nama@email.com" /></div>
                </div>
            </div>
            <div class="wz-panel" data-panel="2">
                <div class="row g-3">
                    <div class="col-md-8"><x-ui.input name="wz-alamat" label="Alamat" placeholder="Jalan, No, Kota" /></div>
                    <div class="col-md-4"><x-ui.input name="wz-kodepos" label="Kode Pos" placeholder="12345" /></div>
                </div>
            </div>
            <div class="wz-panel" data-panel="3">
                <p class="tx-mute m-0">Periksa kembali data Anda sebelum menyimpan.</p>
            </div>
        </x-ui.wizard>
    </section>

    {{-- ================= TOOLTIP ================= --}}
    <section id="tooltip" class="sec">
        <div class="sec-head">
            <span class="ic"><span class="ms">chat_bubble</span></span>
            <div><h2>Tooltip</h2><p>Komponen <code>&lt;x-ui.tooltip&gt;</code> &amp; <code>&lt;x-ui.icon-button&gt;</code> — memunculkan teks penuh saat hover.</p></div>
        </div>

        <div class="row g-3">
            <div class="col-lg-7">
                <x-ui.card title="Teks Panjang (Truncate)" class="h-100">
                    <p class="fs-12 tx-mute">Arahkan kursor ke teks bergaris bawah — teks penuh muncul di tooltip.</p>
                    <div class="d-flex flex-column gap-2">
                        <div>
                            <span class="ui-label">Email</span>
                            <x-ui.tooltip text="andi.saputra.administrator@atlanticpedia.co.id" truncate="14rem" class="tx-tealt fw-semibold">andi.saputra.administrator@atlanticpedia.co.id</x-ui.tooltip>
                        </div>
                        <div>
                            <span class="ui-label">Catatan</span>
                            <x-ui.tooltip text="Akun administrator utama dengan akses penuh ke seluruh modul aplikasi, termasuk manajemen pengguna dan pengaturan sistem." truncate="22rem" class="tx-mute">Akun administrator utama dengan akses penuh ke seluruh modul aplikasi, termasuk manajemen pengguna dan pengaturan sistem.</x-ui.tooltip>
                        </div>
                        <div>
                            <span class="ui-label">Posisi Tooltip</span>
                            <div class="d-flex gap-2 flex-wrap">
                                @foreach (['top' => 'Atas', 'bottom' => 'Bawah', 'left' => 'Kiri', 'right' => 'Kanan'] as $pos => $lbl)
                                    <x-ui.tooltip :text="'Tooltip posisi '.$lbl" :position="$pos" class="pill bg-tealc-m tx-tealt">{{ $lbl }}</x-ui.tooltip>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>
            <div class="col-lg-5">
                <x-ui.card title="Tombol Ikon" class="h-100">
                    <p class="fs-12 tx-mute">Tombol dengan ikon saja menampilkan deskripsi saat hover.</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <x-ui.icon-button icon="visibility" tip="Lihat detail" />
                        <x-ui.icon-button icon="edit" tip="Ubah data" />
                        <x-ui.icon-button icon="content_copy" tip="Duplikat" />
                        <x-ui.icon-button icon="download" tip="Unduh" />
                        <x-ui.icon-button icon="share" tip="Bagikan" />
                        <x-ui.icon-button icon="delete" tip="Hapus" tone="tx-err" />
                    </div>
                    <hr class="my-3">
                    <div class="d-flex gap-2 flex-wrap">
                        <x-ui.icon-button icon="add" tip="Tambah baru" tone="tx-mintt" />
                        <span class="ap-icon-btn bg-tealc-m" data-tip="Tombol terang" data-tip-pos="bottom"><span class="ms ms-md">lightbulb</span></span>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </section>

    {{-- ================= ACTIONS ================= --}}
    <section id="actions" class="sec">
        <div class="sec-head">
            <span class="ic"><span class="ms">bolt</span></span>
            <div><h2>Aksi Baris (Actions)</h2><p>Komponen <code>&lt;x-ui.actions&gt;</code> — ≤ 3 aksi tampil ikon, &gt; 3 aksi jadi menu titik tiga.</p></div>
        </div>

        <x-ui.card>
            <div class="row g-4">
                <div class="col-lg-6">
                    <span class="ui-label">Aksi ≤ 3 — ikon + tooltip</span>
                    <div class="d-flex align-items-center gap-3">
                        <x-ui.actions :actions="[
                            ['label' => 'Lihat', 'icon' => 'visibility', 'action' => 'view'],
                            ['label' => 'Ubah', 'icon' => 'edit', 'href' => '#'],
                            ['label' => 'Hapus', 'icon' => 'delete', 'tone' => 'tx-err', 'action' => 'delete', 'confirm' => 'Hapus item ini?', 'confirm_danger' => true],
                        ]" />
                        <span class="fs-12 tx-mute">→ hover tiap ikon untuk melihat deskripsi.</span>
                    </div>
                </div>
                <div class="col-lg-6">
                    <span class="ui-label">Aksi &gt; 3 — menu titik tiga</span>
                    <div class="d-flex align-items-center gap-3">
                        <x-ui.actions :actions="[
                            ['label' => 'Lihat Detail', 'icon' => 'visibility', 'action' => 'view'],
                            ['label' => 'Ubah', 'icon' => 'edit', 'href' => '#'],
                            ['label' => 'Duplikat', 'icon' => 'content_copy', 'action' => 'duplicate'],
                            ['label' => 'Reset Sandi', 'icon' => 'lock_reset', 'action' => 'reset'],
                            ['label' => 'Hapus', 'icon' => 'delete', 'tone' => 'tx-err', 'action' => 'delete', 'confirm' => 'Hapus item ini?', 'confirm_danger' => true],
                        ]" />
                        <span class="fs-12 tx-mute">→ klik ikon titik tiga (ikon + teks di menu).</span>
                    </div>
                </div>
            </div>
        </x-ui.card>
    </section>

    {{-- ================= MODAL ================= --}}
    <section id="modal" class="sec">
        <div class="sec-head">
            <span class="ic"><span class="ms">web_asset</span></span>
            <div><h2>Modal</h2><p>Komponen <code>&lt;x-ui.modal&gt;</code> untuk data singkat, dan konfirmasi aksi otomatis via <code>data-confirm</code>.</p></div>
        </div>

        <x-ui.card>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn-navy" data-modal-open="modalData">
                    <span class="ms ms-md align-middle me-1">visibility</span>Tampilkan Data
                </button>
                <button type="button" class="btn-soft" data-modal-open="modalSmall">
                    <span class="ms ms-md align-middle me-1">info</span>Data Singkat
                </button>
                <button type="button" class="btn-soft" data-confirm="Hubungi pengguna ini via WhatsApp?" data-confirm-title="Konfirmasi" data-confirm-ok="Ya, Hubungi">
                    <span class="ms ms-md align-middle me-1">chat</span>Konfirmasi Aksi
                </button>
                <button type="button" class="btn-soft tx-err fw-bold" data-confirm="Data yang dihapus tidak dapat dikembalikan. Lanjutkan?" data-confirm-title="Hapus Data" data-confirm-ok="Hapus" data-confirm-danger>
                    <span class="ms ms-md align-middle me-1">delete</span>Hapus dengan Konfirmasi
                </button>
                <button type="button" class="btn-soft" data-modal-open="modalBannerDemo">
                    <span class="ms ms-md align-middle me-1">web_asset</span>Banner Popup
                </button>
            </div>
        </x-ui.card>

        <x-ui.modal id="modalData" title="Detail Pengguna" size="lg">
            <div class="row g-3">
                <div class="col-sm-6">
                    <span class="ui-label">Nama</span>
                    <div class="fw-semibold">Andi Saputra</div>
                </div>
                <div class="col-sm-6">
                    <span class="ui-label">Email</span>
                    <div>andi.saputra@atlanticpedia.co.id</div>
                </div>
                <div class="col-sm-6">
                    <span class="ui-label">Peran</span>
                    <div><span class="pill bg-tealc-m tx-tealt">Admin</span></div>
                </div>
                <div class="col-sm-6">
                    <span class="ui-label">Status</span>
                    <div><span class="pill bg-mint-l tx-mintt">Aktif</span></div>
                </div>
                <div class="col-12">
                    <span class="ui-label">Catatan</span>
                    <p class="tx-mute m-0 fs-12">Akun administrator utama dengan akses penuh ke seluruh modul aplikasi.</p>
                </div>
            </div>
            <x-slot:footer>
                <button type="button" class="btn-soft" data-modal-close>Batal</button>
                <button type="button" class="btn-navy" data-modal-close>Simpan</button>
            </x-slot:footer>
        </x-ui.modal>

        <x-ui.modal id="modalSmall" title="Konfirmasi Singkat" size="sm">
            <p class="m-0">Anda akan mengirim notifikasi ke pengguna ini. Lanjutkan?</p>
            <x-slot:footer>
                <button type="button" class="btn-soft" data-modal-close>Tidak</button>
                <button type="button" class="btn-navy" data-modal-close>Ya</button>
            </x-slot:footer>
        </x-ui.modal>

        <x-ui.banner-popup
            id="modalBannerDemo"
            :image="asset('assets/images/logo_with_right_text.png')"
            title="Promo Spesial"
            body="Overlay transparan — hanya banner yang tampil, tanpa kotak modal."
        />
    </section>

    {{-- ================= EMPTY STATE ================= --}}
    <section id="empty" class="sec">
        <div class="sec-head">
            <span class="ic"><span class="ms">search_off</span></span>
            <div><h2>Empty State</h2><p>Komponen <code>&lt;x-ui.empty-state&gt;</code> untuk tampilan saat data/kosong atau pencarian tanpa hasil.</p></div>
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <x-ui.empty-state icon="search_off" title="Produk tidak ditemukan" description="Tidak ada hasil untuk “pulsa telkomsel”. Coba kata kunci lain atau ubah filter.">
                    <a href="#" class="btn-ap btn-navy"><span class="ms ms-sm">storefront</span>Lihat semua produk</a>
                </x-ui.empty-state>
            </div>
            <div class="col-lg-6">
                <x-ui.empty-state icon="receipt_long" title="Belum ada transaksi" description="Riwayat transaksi kamu akan tampil di sini." tone="bg-mint-l tx-mintt" />
            </div>
        </div>
    </section>
</div>
@endsection