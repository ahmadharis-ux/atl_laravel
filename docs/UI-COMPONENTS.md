# Panduan UI & Komponen — Atlantic Pedia

Aturan wajib saat membuat **halaman baru** atau **fitur baru** agar tampilan dan perilaku konsisten.
Semua komponen UI reusable berada di `resources/views/components/ui/` dan dipakai dengan prefix `<x-ui.*>`.

> Demo interaktif semua komponen: route **`/admin/components`** (`resources/views/admin/components.blade.php`).

---

## 1. Prinsip Umum

1. **Jangan tulis markup komponen dari nol.** Selalu pakai komponen `x-ui.*` yang sudah ada (card, input, table, modal, tooltip, dll).
2. **Jangan tambah CSS/JS inline baru** untuk hal yang sudah ada di `public/assets/css/components.css` atau `public/assets/js/components.js`. Kalau perlu gaya baru, tambahkan ke file itu agar reusable.
3. **Halaman hanya menyusun layout.** Isi halaman = kombinasi komponen, bukan HTML mentah.
4. **Semua halaman berlogika wajib pakai layout** yang sesuai:
   - Area publik/user → `resources/views/layouts/app.blade.php` (`@extends('layouts.app')`)
   - Area admin → `resources/views/layouts/admin.blade.php` (`@extends('layouts.admin')`)
   Layout sudah memuat Bootstrap, `atlantic.css`, `components.css`, `atlantic.js`, dan `components.js`.
5. **Gunakan token warna & kelas utilitas** dari `atlantic.css` (lihat bagian 6). Jangan pakai warna hex acak.

---

## 2. Struktur Halaman Standar

Setiap halaman admin mengikuti pola berikut:

```blade
@extends('layouts.admin')

@section('title', 'Judul Halaman - Admin Atlantic Pedia')

@section('content')
<div class="d-flex flex-column gap-4">

    {{-- Judul / header halaman bila perlu --}}
    <div class="card-ap r-card p-3">
        <h1 class="fw-bold fs-5 m-0">Judul Halaman</h1>
        <p class="tx-mute fs-12 m-0">Deskripsi singkat halaman.</p>
    </div>

    {{-- Konten: baris/kolom grid Bootstrap + komponen x-ui.* --}}
    <div class="row g-3">
        <div class="col-lg-8"><x-ui.card title="..."> ... </x-ui.card></div>
        <div class="col-lg-4"><x-ui.stat-card ... /></div>
    </div>
</div>
@endsection
```

Aturan layout:

- Grid pakai Bootstrap 5 (`row`, `col-lg-*`, `g-3`). **Responsif** — jangan buat file khusus mobile.
- Jarak antar blok pakai `g-3`/`gap-4`. Jangan pakai `<br>`.
- Judul section dalam halaman (opsional) pakai pola `sec-head` yang ada di halaman komponen.

---

## 2b. Judul Halaman (Section Head)

Bila satu halaman punya beberapa blok, tandai dengan pola berikut (lihat `/admin/components`):

```blade
<section id="nama-section" class="sec">
    <div class="sec-head">
        <span class="ic"><span class="ms">table_chart</span></span>
        <div><h2>Judul Blok</h2><p>Keterangan singkat.</p></div>
    </div>
    ... komponen ...
</section>
```

---

## 3. Referensi Komponen

### Card & Statistik

```blade
{{-- Kartu konten --}}
<x-ui.card title="Judul Kartu">
    <x-slot:header><span class="pill bg-mint-l tx-mintt">Aktif</span></x-slot:header>
    Isi kartu...
</x-ui.card>

{{-- Kartu statistik --}}
<x-ui.stat-card label="Total Penjualan" value="128.450" icon="payments"
    tone="bg-tealc-m tx-tealt" trend="+12,5%" />
```

| Prop | Deskripsi |
|---|---|
| `card`: `title`, `pad` (default `p-3`) | slot `header`, slot default |
| `stat-card`: `label`, `value`, `icon` (Material Symbol), `tone`, `trend` | |

### Form

```blade
<x-ui.input name="nama" label="Nama Produk" placeholder="..." hint="..." />
<x-ui.input name="email" type="email" label="Email" />
<x-ui.select name="kategori" label="Kategori" placeholder="Pilih..."
    :options="['pulsa' => 'Pulsa', 'data' => 'Paket Data']" />
<x-ui.textarea name="deskripsi" label="Deskripsi" :rows="3" />
<x-ui.radio-card name="jenis" value="prabayar" label="Prabayar" :checked="true" />
<x-ui.check-card name="notif" label="Kirim notifikasi" :checked="true" />
<x-ui.switch name="aktif" label="Aktifkan" :checked="true" />
```

- `input`: `name`, `label`, `type`, `value`, `hint` (error otomatis dari `@error`).
- `searchbox`: kotak pencarian **khusus produk** (GET form → `route('products.index')`) dengan dropdown saran gaya Shopee + riwayat per pengguna:

```blade
<x-ui.searchbox
    :action="route('products.index')"
    :suggest-url="route('products.lookup', ['type' => 'products'])"
    :clear-url="route('search.history.clear')"
    :history="$searchHistory" {{-- list<string> 3 kata kunci paling sering dicari --}}
    placeholder="Cari produk..."
/>
```

  - Props: `action`, `name` (default `q`), `value`, `placeholder`, `label` (teks tombol, kosongkan untuk tanpa tombol), `method` (default `GET`), `history` (array kata kunci milik user), `suggestUrl`, `clearUrl`.
  - Saat kosong: tampil "Sering Dicari" (**3 teratas** berdasarkan `hits` — pakai `SearchHistory::topQueries($id, 3)`) + tombol "Hapus". Saat mengetik: riwayat yang cocok difilter + baris "Cari …" + saran produk dengan harga (dari lookup `type=products`, `{value, text, url, hint}`). ↩/klik baris apa pun membuka `url` baris.
  - Riwayat disimpan **di DB** (`search_histories` per `user_id`, dengan kolom `hits`, maks 10, di-prune), dicatat oleh `ProductController::index` hanya untuk navigasi penuh non-AJAX (penyaring AJAX tidak ikut tercatat), dihapus lewat `DELETE /search-history`. Menghapus riwayat **hanya mengosongkan dropdown di bawah input** — section "Sesuai Pencarianmu" di beranda tetap tampil karena kata kunci terakhir diingat di sesi (`DashboardController`).
  - **Aturan:** pencarian dari beranda hanya mencari produk — bukan transaksi, ID, atau pengguna — lalu diarahkan ke halaman `Produk` dengan `?q=`. Di halaman Produk, kotak ini bisa dikaitkan ke filter AJAX dengan menambahkan `data-ajax-maybe` pada pemakaiannya: ketikan dan Enter ikut memuat ulang grid tanpa pindah URL (tidak tercatat ke riwayat, sesuai prinsip filter tanpa ganti URL). Selama ada kata kunci `q`, tab prabayar/pascabayar dilewati sehingga produk jenis apa pun langsung tampil; blok `data-ajax-tabs` diganti keterangan "Semua jenis" dan tab kembali begitu pencarian dikosongkan.
- `select`: `name`, `label`, `options` (array value=>text), `value`, `placeholder`, `hint`.
- `textarea`: `name`, `label`, `rows`, `value`, `hint`.
- `search-select` (select yang bisa dicari, berbasis Tom Select — tanpa jQuery):

```blade
{{-- Opsi sedikit (lokal) --}}
<x-ui.search-select name="kota" label="Kota" placeholder="Pilih kota..."
    :options="['jkt' => 'Jakarta', 'bdg' => 'Bandung']" />

{{-- Opsi banyak (remote, dimuat saat mengetik) --}}
<x-ui.search-select name="brand" label="Brand" placeholder="Semua Brand"
    :remote="true"
    :remote-url="route('admin.lookup', ['type' => 'brands'])"
    :value="request('brand')"
    :selected-label="$selectedBrand" />
```

  - Lokal: cukup beri `:options` seperti `select`.
  - Remote: `:remote="true"` + `:remote-url` (endpoint JSON `{ "results": [{ "value", "text" }] }`) + `:selected-label` agar nilai yang sedang terpilih tetap tampil **tanpa** memuat seluruh opsi. `min-chars` (default 1) mengatur jumlah huruf minimal sebelum pencarian dijalankan. Saat dropdown dibuka tanpa kata kunci, komponen memuat **5 opsi pertama** sebagai hint (`data-hint-limit`) agar tidak tampil kosong.
  - Endpoint lookup admin: `route('admin.lookup', ['type' => '...'])` dengan whitelist `brands`, `categories`, `types`, `sellers`, `products`. Halaman publik memakai `route('products.lookup', ['type' => '...'])` (hanya `brands` & `categories`). Hasil dibatasi `limit` (default 20, maks 50) supaya tetap ringan.
  - **Aturan:** jika opsi suatu select berpotensi banyak (> ~50), pakai `search-select` remote, bukan `select` biasa.

### Data Table

```blade
<x-ui.data-table
    id="tblProduk"
    title="Daftar Produk"
    search-placeholder="Cari produk..."
    :bulk="true"
    :columns="[
        ['label' => 'Nama'],
        ['label' => 'Status'],
        ['label' => 'Aksi', 'class' => 'text-end'],
    ]"
    :filters="[
        ['name' => 'status', 'label' => 'Semua Status',
         'options' => ['Aktif' => 'Aktif', 'Nonaktif' => 'Nonaktif']],
    ]"
>
    @foreach ($produk as $p)
        <tr data-status="{{ $p->status }}">
            <td class="chk-cell"><input class="form-check-input m-0 row-check" type="checkbox"></td>
            <td>{{ $p->nama }}</td>
            <td><span class="pill bg-mint-l tx-mintt">{{ $p->status }}</span></td>
            <td class="text-end">
                <x-ui.actions :actions="[
                    ['label' => 'Ubah', 'icon' => 'edit', 'href' => route('produk.edit', $p)],
                    ['label' => 'Hapus', 'icon' => 'delete', 'tone' => 'tx-err',
                     'action' => 'delete', 'confirm' => 'Hapus produk ini?', 'confirm_danger' => true],
                ]" />
            </td>
        </tr>
    @endforeach

    <x-slot:footer>
        <span class="fs-12 tx-mute">Menampilkan 1–10 dari 128 data</span>
        {{ $produk->links() }}
    </x-slot:footer>
</x-ui.data-table>
```

Aturan penting data table:

- Saat `bulk`, **setiap baris wajib diawali** `<td class="chk-cell"><input class="form-check-input m-0 row-check" type="checkbox"></td>`.
- Filter bekerja **client-side**: `<select data-dt-filter data-col="x">` mencocokkan `data-x` pada `<tr>`. Jadi setiap baris harus punya `data-<kolom>` yang sesuai.
- Jika opsi filter banyak atau data berhalaman (server-side), **jangan** pakai `:filters`. Buat filter server-side: form `GET` di atas tabel + `x-ui.search-select` remote, lalu panggil `->paginate()->withQueryString()` di controller. Contoh: `resources/views/admin/product/index.blade.php`.
- Agar filter **tidak me-reload halaman / tidak mengubah URL** (AJAX), bungkus dengan pola di bawah.
- `bulkActions` default: `delete`. Contoh nilai: `['value' => 'activate', 'label' => 'Aktifkan', 'icon' => 'toggle_on', 'tone' => 'btn-soft']`.
- Aksi baris **selalu** pakai `x-ui.actions`, bukan deretan `<button>` manual.
- Pagination: gunakan `{{ $payload->links() }}` untuk data server-side.

#### Filter AJAX (tanpa reload / tanpa ubah URL)

Bungkus form filter + hasil list dengan `data-ajax`, lalu tandai bagian yang diganti:

```blade
<div data-ajax
     data-ajax-url="{{ route('admin.products.prepay.index') }}"
     data-persist-key="admin.products.prabayar">
    <form method="GET" action="{{ $listRoute }}" data-ajax-form> ... </form>
    <button type="button" data-ajax-reset>Reset</button>

    <x-ui.data-table ...>
        @include('..._rows')            {{-- <tbody data-ajax-rows> diisi server --}}
        <x-slot:footer>@include('..._footer')</x-slot:footer>
    </x-ui.data-table>
</div>
```

Aturan:

- `data-ajax-url` = endpoint yang sama dengan halaman (route index). `data-persist-key` = kunci `localStorage` (opsional; state filter otomatis disimpan & dipulihkan saat refresh).
- Form filter pakai `data-ajax-form`; kontainer hasil pakai `data-ajax-rows`, pagination pakai `data-ajax-footer` (otomatis ada di `x-ui.data-table`). Tombol reset filter pakai `data-ajax-reset` (bukan `<a href>`); tombol ini **hanya mengosongkan filter di dalam form** — bidang `data-ajax-maybe` (mis. kata kunci pencarian di luar form) tidak ikut dihapus.
- Untuk mengosongkan **pencarian saja** (di luar form filter), pakai `data-ajax-clear-search`. Handler-nya memakai delegasi event sehingga aman dipakai di dalam blok `data-ajax-tabs` yang isinya diganti lewat AJAX. Di halaman Produk, tombol ini tampil bersama keterangan "Semua jenis untuk ..." saat ada kata kunci.
- Opsional: tandai blok yang bisa berubah bentuk (mis. tab jenis pembayaran) dengan `data-ajax-tabs`; server cukup menyertakan kunci `tabs` di JSON untuk menggantinya.
- Controller: kembalikan JSON saat `$request->ajax()` berisi `rows` + `footer` (dan `tabs` bila ada `data-ajax-tabs`), hasil `view('...')->render()`, sisanya render view penuh. Logika query & filter tetap sama untuk kedua mode.
- Pecah markup baris/footer ke partial (`_rows.blade.php`, `_footer.blade.php`) agar dipakai bersama oleh view penuh dan respons JSON.
- Pagination server-side tetap `{{ $payload->links() }}`; klik link paginasi dicegat oleh JS (`?page=N`) tanpa mengubah URL.
- Contoh lengkap: `resources/views/admin/product/*` dan `resources/views/user/product/*`.

### Tombol Ikon & Tooltip

```blade
<x-ui.icon-button icon="edit" tip="Ubah data" />
<x-ui.icon-button icon="delete" tip="Hapus" tone="tx-err" href="#" />

{{-- Teks panjang: dipotong, penuh muncul saat hover --}}
<x-ui.tooltip text="teks lengkap yang panjang..." truncate="14rem">teks tampil...</x-ui.tooltip>

{{-- Tooltip pada elemen apa pun, tanpa memotong --}}
<span data-tip="Keterangan">Hover saya</span>
<span data-tip="Posisi bawah" data-tip-pos="bottom">Bawah</span>
```

- `tooltip`: `text` (isi tooltip), `position` (`top`/`bottom`/`left`/`right`), `truncate` (mis. `14rem` / `200px`, opsional).
- Tombol aksi ikon-saja **wajib** punya deskripsi (`tip` atau `data-tip`).

### Actions (Aksi Baris)

```blade
<x-ui.actions :actions="[
    ['label' => 'Lihat', 'icon' => 'visibility', 'action' => 'view'],
    ['label' => 'Ubah', 'icon' => 'edit', 'href' => '...'],
    ['label' => 'Hapus', 'icon' => 'delete', 'tone' => 'tx-err',
     'action' => 'delete', 'confirm' => '...', 'confirm_danger' => true],
]" />
```

- **≤ 3 aksi** → deretan ikon + tooltip.
- **> 3 aksi** → tombol titik tiga (`more_vert`) → menu berisi **ikon + teks**.
- Kunci per aksi: `label`, `icon`, `href`, `action`, `tone`, `confirm`, `confirm_danger` (opsional), `threshold` (default 3).

### Modal

```blade
{{-- Pemicu --}}
<button type="button" class="btn-navy" data-modal-open="modalData">Lihat Data</button>
<button type="button" class="btn-soft" data-confirm="Yakin hapus data ini?"
    data-confirm-title="Hapus Data" data-confirm-ok="Hapus" data-confirm-danger>Hapus</button>

{{-- Modal menampilkan data --}}
<x-ui.modal id="modalData" title="Detail Pengguna" size="lg">
    ...isi (form/grid/read-only)...
    <x-slot:footer>
        <button type="button" class="btn-soft" data-modal-close>Batal</button>
        <button type="button" class="btn-navy" data-modal-close>Simpan</button>
    </x-slot:footer>
</x-ui.modal>
```

- Buka: `data-modal-open="id"`. Tutup: `data-modal-close` (tombol/backdrop, ESC juga menutup).
- `modal`: `id`, `title`, `size` (`sm`/`lg`), `closeBtn`.
- **Konfirmasi aksi apapun** (termasuk aksi di `x-ui.actions`) cukup tambah `data-confirm` pada tombol/link — modal konfirmasi muncul otomatis.
- Programatik: `AP.openModal('id')`, `AP.closeModal('id')`, `AP.confirm({ message, title, okLabel, danger }, callback)`.

### Chart

```blade
<x-ui.chart id="chartPenjualan" type="line" :config="$config" />
{{-- $config = ['data' => [...], 'options' => [...]] (Chart.js) --}}
```

`type`: `line`, `bar`, `doughnut`, dll. Chart.js dimuat otomatis bila ada komponen ini.

### Rich Text Editor

```blade
<x-ui.editor id="editorKonten" name="konten" :value="$konten" />
```

Quill dimuat otomatis. Nilai akhir tersimpan di input hidden `name`.

### Wizard

```blade
<x-ui.wizard id="wzData" :steps="['Data Diri', 'Alamat', 'Konfirmasi']" submit-label="Simpan">
    <div class="wz-panel active" data-panel="1"> ... </div>
    <div class="wz-panel" data-panel="2"> ... </div>
    <div class="wz-panel" data-panel="3"> ... </div>
</x-ui.wizard>
```

Panel **wajib** `data-panel` berurutan dan panel pertama `active`.

### Pill / Badge

Komponen pill memakai kelas dari `atlantic.css`:

```blade
<span class="pill bg-mint-l tx-mintt">Aktif</span>
<span class="pill bg-gold tx-gold">Pending</span>
<span class="pill bg-errc-l tx-err">Nonaktif</span>
<span class="pill bg-tealc-m tx-tealt">Admin</span>
```

### Empty State

Tampilan saat data kosong atau pencarian tanpa hasil. Jangan tampilkan teks polos di dalam card yang menyerupai kartu produk — pakai komponen ini agar terlihat sengaja.

```blade
<x-ui.empty-state
    icon="search_off"
    title="Produk tidak ditemukan"
    description="Tidak ada hasil untuk “pulsa”. Coba kata kunci lain atau ubah filter.">
    <a href="{{ route('products.index') }}" class="btn-ap btn-navy">Lihat semua produk</a>
</x-ui.empty-state>
```

| Prop | Deskripsi |
|---|---|
| `icon` | Material Symbol (default `search_off`) |
| `title` | Judul singkat (wajib) |
| `description` | Keterangan tambahan (opsional) |
| `tone` | Kelas warna latar ikon, mis. `bg-tealc-m tx-tealt`, `bg-mint-l tx-mintt` |
| slot default | Area aksi/tombol (opsional) |
| `class="empty-state-fill"` | (opsional) Membuat empty state selebar baris grid dan setinggi ±22rem. Wajib dipakai saat empty state dirender langsung sebagai anak baris ber-`row-cols-*` (mis. daftar produk) agar tidak menyusut jadi satu sel. |

Dipakai di: daftar produk (`user.product._cards`, dengan `empty-state-fill`), produk terlaris beranda, dan riwayat transaksi halaman Saldo.

---

## 4. Kapan Pakai Apa?

| Kebutuhan | Gunakan |
|---|---|
| Teks panjang di tabel/inline yang bisa terpotong | `x-ui.tooltip ... truncate` |
| Deskripsi tombol ikon-saja | `x-ui.icon-button tip="..."` / `data-tip` |
| Aksi per baris tabel | `x-ui.actions` |
| Popup konfirmasi sebelum aksi | `data-confirm` pada tombol/link |
| Menampilkan data singkat / form kecil | `x-ui.modal` |
| Daftar data + cari/filter/bulk | `x-ui.data-table` |
| Input tunggal | `x-ui.input/select/textarea` |
| Data/pencarian kosong tanpa hasil | `x-ui.empty-state` |

---

## 5. Warna & Token (dari `atlantic.css`)

Gunakan **selalu** variabel/kelas ini, jangan hardcode hex.

- **Warna teks**: `tx-navy`, `tx-teal`, `tx-tealt`, `tx-mintt`, `tx-err`, `tx-mute`, `tx-gold`.
- **Latar**: `bg-navy`, `bg-mintt`, `bg-tealc-m`, `bg-mint-l`, `bg-gold`, `bg-errc-l`.
- **Tombol**: `btn-navy` (utama), `btn-soft` (sekunder), `btn-white`, `btn-ap`.
- **Pill/badge**: lihat bagian Pill di atas.
- **Kartu**: `card-ap r-card` (atau komponen `x-ui.card`).
- **Ikon**: Material Symbols → `<span class="ms ms-md">nama_ikon</span>`.

---

## 6. Checklist Membuat Halaman Baru

- [ ] `@extends` layout yang benar (`layouts.app` atau `layouts.admin`).
- [ ] Set `@section('title')`.
- [ ] Konten memakai `x-ui.*`, bukan markup komponen mentah.
- [ ] Grid Bootstrap responsif (`col-lg-*`, `g-3`); tidak ada file khusus mobile.
- [ ] Semua tombol aksi ikon-saja punya tooltip/`tip`.
- [ ] Aksi baris tabel pakai `x-ui.actions`.
- [ ] Aksi destruktif pakai `data-confirm` (+ `data-confirm-danger`).
- [ ] Tabel dengan `bulk` menyertakan `td.chk-cell` + `input.row-check` di tiap baris & `data-<kolom>` untuk filter.
- [ ] Tidak ada CSS/JS inline baru (tambahkan ke `components.css`/`components.js` bila benar-benar perlu).
- [ ] Sudah dicek: `php artisan view:clear && php artisan view:cache`, `node --check public/assets/js/components.js`, dan render halaman tanpa error.

---

## 7. Menambah Komponen Baru

1. Buat file di `resources/views/components/ui/nama-komponen.blade.php` (anonymous component).
2. Deklarasikan API lewat `@props([...])` dan merge class dengan `$attributes->merge(['class' => '...'])`.
3. Gaya visual → tambahkan ke `public/assets/css/components.css`.
4. Perilaku JS → tambahkan fungsi di `public/assets/js/components.js` dan panggil dari `boot()`.
5. Tampilkan contohnya di halaman `/admin/components`.
6. Dokumentasikan di file ini.

---

## 8. Role & Permission (RBAC)

Sistem memakai **spatie/laravel-permission**. Sumber kebenaran tunggal ada di:

- `app/Support/Roles.php` → daftar role + level otoritas + label/warna.
- `app/Support/Rbac.php` → daftar permission (per modul), izin default per role, dan aturan hierarki.

### Role

| Role | Level | Akses admin | Keterangan |
|---|---|---|---|
| `superadmin` | 100 | Ya | Akses penuh, semua izin |
| `admin` | 80 | Ya | Kelola pengguna + peran (tanpa hapus peran sistem) |
| `staff` | 60 | Ya | Tambah/ubah pengguna |
| `cs` | 40 | Ya | Hanya lihat pengguna |
| `user` | 10 | Tidak | Registrasi mandiri (default) |

- Registrasi mandiri (`/register`) otomatis mendapat role **`user`**.
- Role admin/staff/CS/superadmin **hanya dibuat** dari area admin oleh pengguna yang punya `users.create`.
- Hierarki (`Rbac::assignableRoles` / `canManageUser`): pengguna hanya bisa membuat/mengelola akun dengan **level lebih rendah**. Superadmin bebas. Peran pengguna tidak bisa mengubah peran akun sendiri.

### Permission per modul

`dashboard.view`, `users.view`, `users.create`, `users.update`, `users.delete`, `users.assign-role`, `roles.view`, `roles.create`, `roles.update`, `roles.delete`, `topups.view`, `topups.update`, `products.view`, `products.update`.

### Submenu sidebar

Menu dengan anak (contoh "Customer Topup" → Topup, Product) didefinisikan di `resources/views/partials/admin-sidebar.blade.php` lewat key `children`. Sub-link di-gate dengan `@can` masing-masing, dan toggle buka/tutup ditangani `data-side-toggle` (JS di `atlantic.js`).

### Cara pakai di kode

```blade
{{-- Blade --}}
@can('users.create')
    <a href="{{ route('admin.users.create') }}" class="btn-navy">Tambah</a>
@endcan
```

```php
// Controller / kode
$request->user()->can('users.update');
$user->hasRole(\App\Support\Roles::ADMIN);
\App\Support\Rbac::canManageUser($actor, $target); // bool
\App\Support\Rbac::assignableRoles($actor);        // daftar role yang boleh ditetapkan
```

```php
// Route
Route::get('/users', [UserController::class, 'index'])
    ->middleware('permission:users.view');
Route::get('/x')->middleware('role:admin'); // atau role_or_permission
```

### Aturan menambah permission / role baru

1. Tambahkan nama permission di `Rbac::groups()` (grup = judul section di UI).
2. Sesuaikan izin default role di `Rbac::defaults()`.
3. Sinkronkan ke DB: `php artisan db:seed` (idempotent) atau buat seeder baru.
4. Halaman yang relevan: bungkus tombol/aksi dengan `@can(...)` dan pasang middleware `permission:` pada route-nya.
5. Selalu bungkus aksi baris tabel dengan cek `Rbac::canManageUser()` agar aturan hierarki tetap berlaku.

### Akun demo (hasil `php artisan db:seed`)

| Email | Password | Role |
|---|---|---|
| `superadmin@atlantic.test` | `super12345` | Super Admin |
| `admin@atlantic.test` | `admin123` | Admin |
| `staff@atlantic.test` | `staff12345` | Staff |
| `cs@atlantic.test` | `cs1234567` | CS |
| `user@atlantic.test` | `user12345` | User |

---

## 9. Loader (Loading Overlay)

Overlay loading global yang **otomatis muncul saat form disubmit** (kecuali method `GET`) dan menampilkan **waktu berjalan** (detik) di bawah animasi spinner. Sudah terpasang di `layouts.app` dan `layouts.admin` via `<x-ui.loader />` — cukup submit form biasa, tidak perlu tambahan apa pun.

```blade
{{-- Default (di layout) --}}
<x-ui.loader />

{{-- Kustom label --}}
<x-ui.loader text="Menyimpan data..." />
```

| Prop | Deskripsi |
|---|---|
| `id` | id elemen overlay (default `ap-loader`) |
| `text` | label di bawah spinner (default `Memproses...`) |

**Opt-out** (mis. form filter atau form yang tidak reload halaman) — tambahkan salah satu:
```blade
<form method="GET">...</form>            {{-- otomatis dilewati --}}
<form data-no-loader>...</form>          {{-- dilewati manual --}}
<form data-loader="off">...</form>       {{-- dilewati manual --}}
```

**Kontrol manual via JS:**
```js
AP.showLoader(); // tampilkan
AP.hideLoader(); // sembunyikan
```

Catatan: aksi hapus lewat `x-ui.actions`/`data-confirm` juga memicu loader karena submit form memakai `requestSubmit()`. Loader otomatis disembunyikan bila navigate balik (bfcache `pageshow`) atau bila submit dibatalkan `preventDefault`.