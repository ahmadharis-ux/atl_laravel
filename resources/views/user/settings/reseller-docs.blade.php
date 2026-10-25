@extends('layouts.app')

@section('title', 'Dokumentasi API Reseller - Atlantic Pedia Tab')

@section('content')
<div class="d-flex flex-column gap-4">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('settings.reseller') }}" class="ap-icon-btn" data-tip="Kembali"><span class="ms ms-md">arrow_back</span></a>
        <div>
            <h1 class="fw-bold fs-4 m-0">Dokumentasi API Reseller</h1>
            <p class="tx-mute fs-12 m-0">Cara mengambil produk, cek harga reseller, dan membuat pesanan lewat API.</p>
        </div>
    </div>

    <x-ui.card title="Daftar Isi">
        <ul class="fs-13 mb-0 ps-3">
            <li><a href="#doc-ringkasan">Ringkasan</a></li>
            <li><a href="#doc-autentikasi">Autentikasi</a></li>
            <li><a href="#doc-endpoint">Base URL dan Daftar Endpoint</a></li>
            <li><a href="#doc-profil">1. Profil dan Cek Saldo</a></li>
            <li><a href="#doc-produk">2. Ambil Daftar Produk dan Harga Reseller</a></li>
            <li><a href="#doc-pesanan">3. Buat Pesanan (Bayar dari Saldo)</a></li>
            <li><a href="#doc-status">4. Pantau Status Pesanan</a></li>
            <li><a href="#doc-error">5. Error dan Kode Status</a></li>
        </ul>
    </x-ui.card>

    <x-ui.card id="doc-ringkasan" title="Ringkasan">
        <p class="fs-13 mb-2">
            API reseller memungkinkan kamu mengambil daftar produk beserta
            <span class="fw-semibold">harga khusus reseller</span>,
            membuat pesanan dari saldo akun, dan memantau status transaksi —
            semua dari aplikasi, kasir, atau bot kamu sendiri.
        </p>
        <ul class="fs-12 mb-0 ps-3">
            <li>Semua produk aktif dapat dilihat lewat <code>GET /products</code>.</li>
            <li>Harga reseller = <span class="fw-semibold">minimal {{ number_format($discountPercent, fmod($discountPercent, 1) === 0.0 ? 0 : 1, ',', '.') }}% lebih murah</span> dari harga jual publik (tidak di bawah modal).</li>
            <li>Pesanan dibayar dengan <span class="fw-semibold">saldo akun</span> (tanpa transfer manual).</li>
            <li>Format respons JSON; kirim <code>Accept: application/json</code> agar error juga berupa JSON.</li>
        </ul>
    </x-ui.card>

    <x-ui.card id="doc-autentikasi" title="Autentikasi">
        <p class="fs-12">
            Kirim API key kamu di setiap request memakai salah satu header berikut:
        </p>
        <div class="position-relative mb-2">
            <pre class="ap-log-pre pe-5 mb-0">X-API-Key: ap_XXXXXXXX...
# atau
Authorization: Bearer ap_XXXXXXXX...</pre>
            <button type="button" class="ap-icon-btn position-absolute top-0 end-0 m-2" data-copy-text="X-API-Key: ap_XXXXXXXX...
# atau
Authorization: Bearer ap_XXXXXXXX..." data-copy-label="Header Autentikasi" data-tip="Salin"><span class="ms ms-sm">content_copy</span></button>
        </div>
        <div class="d-flex justify-content-end mb-2">
            <button type="button" class="btn-soft" data-copy-text="X-API-Key: ap_XXXXXXXX...
# atau
Authorization: Bearer ap_XXXXXXXX..." data-copy-label="Header Autentikasi"><span class="ms ms-sm">content_copy</span> Salin Contoh</button>
        </div>
        <p class="fs-12 mb-0">
            Belum punya API key? Ajukan keanggotaan reseller dan tunggu persetujuan admin di
            <a href="{{ route('settings.reseller') }}">halaman Reseller</a> —
            key hanya ditampilkan satu kali saat dibuat.
            API key bersifat rahasia; jangan bagikan ke pihak lain.
        </p>
        <p class="fs-12 mb-0 mt-2">
            Jika allowlist IP diatur, request API hanya diterima dari IP server yang terdaftar.
            Keanggotaan yang ditangguhkan akan kehilangan akses API dan toko untuk pesanan baru.
        </p>
    </x-ui.card>

    <x-ui.card id="doc-endpoint" title="Base URL dan Daftar Endpoint">
        <div class="position-relative mb-2">
            <pre class="ap-log-pre pe-5 mb-0">Base URL: {{ $apiBase }}</pre>
            <button type="button" class="ap-icon-btn position-absolute top-0 end-0 m-2" data-copy-text="{{ $apiBase }}" data-copy-label="Base URL" data-tip="Salin"><span class="ms ms-sm">content_copy</span></button>
        </div>
        <div class="d-flex justify-content-end mb-2">
            <button type="button" class="btn-soft" data-copy-text="{{ $apiBase }}" data-copy-label="Base URL"><span class="ms ms-sm">content_copy</span> Salin Contoh</button>
        </div>
        <div class="table-responsive">
            <table class="table table-ui align-middle">
                <thead>
                    <tr>
                        <th class="text-center" style="width:70px">Method</th>
                        <th>Endpoint</th>
                        <th>Fungsi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="pill bg-mint-l tx-mintt">GET</span></td>
                        <td><code>/profile</code></td>
                        <td>Profil akun, saldo, dan diskon berlaku.</td>
                    </tr>
                    <tr>
                        <td><span class="pill bg-mint-l tx-mintt">GET</span></td>
                        <td><code>/balance</code></td>
                        <td>Saldo akun + 10 mutasi wallet terakhir.</td>
                    </tr>
                    <tr>
                        <td><span class="pill bg-mint-l tx-mintt">GET</span></td>
                        <td><code>/products</code></td>
                        <td>Daftar produk aktif + harga reseller.</td>
                    </tr>
                    <tr>
                        <td><span class="pill bg-mint-l tx-mintt">GET</span></td>
                        <td><code>/products/{id}</code></td>
                        <td>Detail satu produk.</td>
                    </tr>
                    <tr>
                        <td><span class="pill bg-sky-l tx-navy">POST</span></td>
                        <td><code>/orders</code></td>
                        <td>Buat pesanan baru (bayar dari saldo).</td>
                    </tr>
                    <tr>
                        <td><span class="pill bg-mint-l tx-mintt">GET</span></td>
                        <td><code>/orders</code></td>
                        <td>Riwayat pesanan milik kamu (20 per halaman).</td>
                    </tr>
                    <tr>
                        <td><span class="pill bg-mint-l tx-mintt">GET</span></td>
                        <td><code>/orders/{order_number}</code></td>
                        <td>Detail satu pesanan.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.card id="doc-profil" title="1. Profil dan Cek Saldo">
        <p class="fs-12 mb-2">
            Dua endpoint untuk cek status akun:
            <code>/profile</code> (identitas, status keanggotaan, &amp; diskon berlaku) dan
            <code>/balance</code> (saldo terkini + 10 mutasi wallet terakhir).
        </p>

        <p class="fs-12 fw-semibold mb-1">GET <code>/profile</code></p>
        <div class="position-relative mb-2">
            <pre class="ap-log-pre pe-5 mb-0">curl "{{ $apiBase }}/profile" \
  -H "X-API-Key: ap_XXXXXXXX..." \
  -H "Accept: application/json"</pre>
            <button type="button" class="ap-icon-btn position-absolute top-0 end-0 m-2" data-copy-text="curl &quot;{{ $apiBase }}/profile&quot; \
  -H &quot;X-API-Key: ap_XXXXXXXX...&quot; \
  -H &quot;Accept: application/json&quot;" data-copy-label="cURL Profil" data-tip="Salin"><span class="ms ms-sm">content_copy</span></button>
        </div>
        <div class="d-flex justify-content-end mb-3">
            <button type="button" class="btn-soft" data-copy-text="curl &quot;{{ $apiBase }}/profile&quot; \
  -H &quot;X-API-Key: ap_XXXXXXXX...&quot; \
  -H &quot;Accept: application/json&quot;" data-copy-label="cURL Profil"><span class="ms ms-sm">content_copy</span> Salin Contoh cURL</button>
        </div>
        <pre class="ap-log-pre mb-3">{
  "data": {
    "name": "Budi Reseller",
    "email": "budi@example.test",
    "is_reseller": true,
    "reseller_since": "2026-10-15T10:15:00+07:00",
    "balance": 150000,
    "discount_percent": 5,
    "api_key_hint": "...a1b2"
  }
}</pre>

        <p class="fs-12 fw-semibold mb-1">GET <code>/balance</code></p>
        <div class="position-relative mb-2">
            <pre class="ap-log-pre pe-5 mb-0">curl "{{ $apiBase }}/balance" \
  -H "X-API-Key: ap_XXXXXXXX..." \
  -H "Accept: application/json"</pre>
            <button type="button" class="ap-icon-btn position-absolute top-0 end-0 m-2" data-copy-text="curl &quot;{{ $apiBase }}/balance&quot; \
  -H &quot;X-API-Key: ap_XXXXXXXX...&quot; \
  -H &quot;Accept: application/json&quot;" data-copy-label="cURL Saldo" data-tip="Salin"><span class="ms ms-sm">content_copy</span></button>
        </div>
        <div class="d-flex justify-content-end mb-3">
            <button type="button" class="btn-soft" data-copy-text="curl &quot;{{ $apiBase }}/balance&quot; \
  -H &quot;X-API-Key: ap_XXXXXXXX...&quot; \
  -H &quot;Accept: application/json&quot;" data-copy-label="cURL Saldo"><span class="ms ms-sm">content_copy</span> Salin Contoh cURL</button>
        </div>
        <pre class="ap-log-pre mb-0">{
  "data": {
    "balance": 150000,
    "transactions": [
      {
        "type": "purchase",
        "direction": "debit",
        "amount": 6175,
        "balance_after": 143825,
        "description": "Pesanan TRX-20261020-AB12CD",
        "created_at": "2026-10-20T10:15:00+07:00"
      }
    ]
  }
}</pre>
    </x-ui.card>

    <x-ui.card id="doc-produk" title="2. Ambil Daftar Produk dan Harga Reseller">
        <p class="fs-12 mb-2">
            Daftar hanya berisi produk aktif dan diurutkan berdasarkan nama.
            Filter tersedia melalui query: <code>q</code> (nama/kode, maksimal 100 karakter),
            <code>brand_id</code>, <code>category_id</code>, dan <code>payment_type</code>
            (<code>prabayar</code>|<code>pascabayar</code>). Gunakan <code>per_page</code> antara 1–100;
            default 50 item per halaman.
        </p>
        <div class="position-relative mb-2">
            <pre class="ap-log-pre pe-5 mb-0">curl -G "{{ $apiBase }}/products" \
  -H "X-API-Key: ap_XXXXXXXX..." \
  -H "Accept: application/json" \
  --data-urlencode "q=pulsa" \
  --data-urlencode "per_page=20"</pre>
            <button type="button" class="ap-icon-btn position-absolute top-0 end-0 m-2" data-copy-text="curl -G &quot;{{ $apiBase }}/products&quot; \
  -H &quot;X-API-Key: ap_XXXXXXXX...&quot; \
  -H &quot;Accept: application/json&quot; \
  --data-urlencode &quot;q=pulsa&quot; \
  --data-urlencode &quot;per_page=20&quot;" data-copy-label="cURL Produk" data-tip="Salin"><span class="ms ms-sm">content_copy</span></button>
        </div>
        <div class="d-flex justify-content-end mb-3">
            <button type="button" class="btn-soft" data-copy-text="curl -G &quot;{{ $apiBase }}/products&quot; \
  -H &quot;X-API-Key: ap_XXXXXXXX...&quot; \
  -H &quot;Accept: application/json&quot; \
  --data-urlencode &quot;q=pulsa&quot; \
  --data-urlencode &quot;per_page=20&quot;" data-copy-label="cURL Produk"><span class="ms ms-sm">content_copy</span> Salin Contoh cURL</button>
        </div>
        <pre class="ap-log-pre mb-2">{
  "data": [
    {
      "id": 12,
      "code": "XL5000",
      "name": "XL 5.000",
      "brand": "XL",
      "category": "Pulsa",
      "payment_type": "prabayar",
      "region": null,
      "selling_price": 6500,
      "reseller_price": 6175,
      "discount": 325,
      "stock": 100,
      "unlimited_stock": false,
      "requires_zone": false,
      "target_label": "Nomor HP"
    }
  ],
  "meta": { "current_page": 1, "per_page": 20, "last_page": 3, "total": 54 }
}</pre>
        <p class="fs-12 mb-0">
            <span class="fw-semibold">reseller_price</span> adalah yang kamu bayar;
            <span class="fw-semibold">selling_price</span> adalah harga jual publik;
            <span class="fw-semibold">discount</span> adalah selisih nominal dalam Rupiah, bukan persentase atau jaminan laba bersih.
        </p>
        <h3 class="fs-13 fw-bold mt-3 mb-2">Kontrak field produk</h3>
        <div class="table-responsive">
            <table class="table table-ui align-middle fs-12">
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Tipe / nullability</th>
                        <th>Arti</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td><code>id</code></td><td>integer</td><td>ID untuk <code>product_id</code> saat membuat pesanan.</td></tr>
                    <tr><td><code>code</code>, <code>name</code></td><td>string</td><td>Kode dan nama produk.</td></tr>
                    <tr><td><code>brand</code>, <code>category</code></td><td>string atau null</td><td>Nama relasi; null bila relasi tidak tersedia.</td></tr>
                    <tr><td><code>payment_type</code></td><td>string</td><td><code>prabayar</code> atau <code>pascabayar</code>.</td></tr>
                    <tr><td><code>region</code></td><td>string atau null</td><td>Wilayah produk; null berarti produk umum/tidak ditentukan.</td></tr>
                    <tr><td><code>selling_price</code>, <code>reseller_price</code>, <code>discount</code></td><td>integer</td><td>Nominal Rupiah. Harga reseller yang didebit; discount adalah selisih harga, bukan persentase.</td></tr>
                    <tr><td><code>stock</code></td><td>integer atau null</td><td>Jumlah stok; null jika <code>unlimited_stock</code> true.</td></tr>
                    <tr><td><code>unlimited_stock</code>, <code>requires_zone</code></td><td>boolean</td><td>Status stok tanpa batas dan apakah pesanan memerlukan <code>zone</code>.</td></tr>
                    <tr><td><code>target_label</code></td><td>string</td><td>Label tujuan untuk membantu form integrasi.</td></tr>
                </tbody>
            </table>
        </div>

        <h3 class="fs-13 fw-bold mt-3 mb-2">Detail produk aktif</h3>
        <p class="fs-12">Endpoint <code>GET /products/{id}</code> mengembalikan object <code>data</code> dengan field yang sama seperti item daftar dan tanpa <code>meta</code>.</p>
        <pre class="ap-log-pre mb-2">{
  "data": {
    "id": 12,
    "code": "ML172",
    "name": "Mobile Legends 172 Diamonds",
    "brand": "Mobile Legends",
    "category": "Games",
    "payment_type": "prabayar",
    "region": null,
    "selling_price": 6500,
    "reseller_price": 6175,
    "discount": 325,
    "stock": null,
    "unlimited_stock": true,
    "requires_zone": true,
    "target_label": "ID Game"
  }
}</pre>
        <p class="fs-12 mb-0">
            Daftar memakai <code>meta.current_page</code>, <code>meta.per_page</code>,
            <code>meta.last_page</code>, dan <code>meta.total</code> (semuanya integer).
            Produk tidak ditemukan atau tidak aktif pada endpoint detail menghasilkan <code>404</code>.
        </p>
    </x-ui.card>

    <x-ui.card id="doc-pesanan" title="3. Buat Pesanan (Bayar dari Saldo)">
        <p class="fs-12">
            Field: <code>product_id</code> (wajib), <code>target</code> (wajib — nomor tujuan), <code>zone</code>
            (wajib untuk produk yang butuh zone, mis. Mobile Legends — cek dari field <code>requires_zone</code>).
            Pembayaran selalu lewat saldo; pastikan saldo cukup.
        </p>
        <div class="position-relative mb-2">
            <pre class="ap-log-pre pe-5 mb-0">curl -X POST "{{ $apiBase }}/orders" \
  -H "X-API-Key: ap_XXXXXXXX..." \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"product_id": 12, "target": "081234567890"}'</pre>
            <button type="button" class="ap-icon-btn position-absolute top-0 end-0 m-2" data-copy-text="curl -X POST &quot;{{ $apiBase }}/orders&quot; \
  -H &quot;X-API-Key: ap_XXXXXXXX...&quot; \
  -H &quot;Content-Type: application/json&quot; \
  -H &quot;Accept: application/json&quot; \
  -d '{&quot;product_id&quot;: 12, &quot;target&quot;: &quot;081234567890&quot;}'" data-copy-label="cURL Buat Pesanan" data-tip="Salin"><span class="ms ms-sm">content_copy</span></button>
        </div>
        <div class="d-flex justify-content-end mb-3">
            <button type="button" class="btn-soft" data-copy-text="curl -X POST &quot;{{ $apiBase }}/orders&quot; \
  -H &quot;X-API-Key: ap_XXXXXXXX...&quot; \
  -H &quot;Content-Type: application/json&quot; \
  -H &quot;Accept: application/json&quot; \
  -d '{&quot;product_id&quot;: 12, &quot;target&quot;: &quot;081234567890&quot;}'" data-copy-label="cURL Buat Pesanan"><span class="ms ms-sm">content_copy</span> Salin Contoh cURL</button>
        </div>
        <pre class="ap-log-pre mb-2">{
  "data": {
    "order_number": "TRX-20261020-AB12CD",
    "status": "processing",
    "status_label": "Diproses",
    "product": { "id": 12, "code": "XL5000", "name": "XL 5.000" },
    "target": "081234567890",
    "amount": 6175,
    "fee": 0,
    "total": 6175,
    "payment_method": "saldo",
    "created_at": "2026-10-20T10:15:00+07:00"
  },
  "message": "Pesanan berhasil dibuat."
}</pre>
        <p class="fs-12 mb-0">
            <span class="fw-semibold">Stok di-decrement dan saldo didebit dalam satu transaksi.</span>
            Jika stok habis atau saldo kurang, request dibalas <code>422</code> dan tidak ada perubahan apa pun.
        </p>
    </x-ui.card>

    <x-ui.card id="doc-status" title="4. Pantau Status Pesanan">
        <pre class="ap-log-pre mb-3">curl "{{ $apiBase }}/orders/TRX-20261020-AB12CD" \
  -H "X-API-Key: ap_XXXXXXXX..." \
  -H "Accept: application/json"</pre>
        <p class="fs-12">Nilai <code>status</code> (dengan <code>status_label</code>-nya):</p>
        <div class="table-responsive mb-2">
            <table class="table table-ui align-middle">
                <thead>
                    <tr>
                        <th style="width:140px">Status</th>
                        <th>Label</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>pending</code></td>
                        <td><span class="pill bg-gold tx-gold">Menunggu</span></td>
                        <td>Pesanan dibuat, belum diproses.</td>
                    </tr>
                    <tr>
                        <td><code>processing</code></td>
                        <td><span class="pill bg-sky-l tx-navy">Diproses</span></td>
                        <td>Sedang dikirim ke provider.</td>
                    </tr>
                    <tr>
                        <td><code>success</code></td>
                        <td><span class="pill bg-mint-l tx-mintt">Sukses</span></td>
                        <td>Transaksi selesai — barang masuk ke nomor tujuan.</td>
                    </tr>
                    <tr>
                        <td><code>failed</code></td>
                        <td><span class="pill bg-errc-l tx-err">Gagal</span></td>
                        <td>Transaksi gagal di provider.</td>
                    </tr>
                    <tr>
                        <td><code>expired</code></td>
                        <td><span class="pill bg-glass tx-mute">Kedaluwarsa</span></td>
                        <td>Pesanan kedaluwarsa tanpa diproses.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="fs-12 mb-0">
            Riwayat semua pesanan: <code>GET /orders</code> (20 per halaman, urut terbaru — object <code>meta</code> berisi
            <code>current_page</code>, <code>last_page</code>, <code>total</code>).
        </p>
    </x-ui.card>

    <x-ui.card id="doc-error" title="5. Error dan Kode Status">
        <div class="table-responsive">
            <table class="table table-ui align-middle">
                <thead>
                    <tr>
                        <th class="text-center" style="width:70px">Kode</th>
                        <th>Arti dan Solusi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-center"><span class="pill bg-errc-l tx-err">401</span></td>
                        <td>API key tidak ditemukan/tidak valid, atau akun bukan reseller aktif. Cek header <code>X-API-Key</code> atau buat ulang key.</td>
                    </tr>
                    <tr>
                        <td class="text-center"><span class="pill bg-errc-l tx-err">404</span></td>
                        <td>Produk tidak ditemukan/tidak aktif, atau pesanan bukan milik kamu.</td>
                    </tr>
                    <tr>
                        <td class="text-center"><span class="pill bg-gold tx-gold">422</span></td>
                        <td>Validasi gagal: field kurang, stok habis, saldo kurang, operator tidak cocok, atau zone wajib diisi. Cek objek <code>errors</code>.</td>
                    </tr>
                    <tr>
                        <td class="text-center"><span class="pill bg-errc-l tx-err">429</span></td>
                        <td>Terlalu banyak request — batas 120 request/menit per API key. Coba lagi sebentar lagi.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <pre class="ap-log-pre mb-0">{
  "message": "The per page field must not be greater than 100.",
  "errors": {
    "per_page": ["The per page field must not be greater than 100."]
  }
}</pre>
    </x-ui.card>

    <div class="card-ap r-card p-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <span class="fs-12 tx-mute">Butuh bantuan? Hubungi tim Atlantic Pedia.</span>
        <a href="{{ route('settings.reseller') }}" class="btn-navy">Kembali ke Halaman Reseller</a>
    </div>
</div>
@endsection
