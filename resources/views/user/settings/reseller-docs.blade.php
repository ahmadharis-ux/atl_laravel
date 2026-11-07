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
            <li><a href="#doc-hosted-flow">Alur Uang Toko Hosted</a></li>
            <li><a href="#doc-endpoint">Base URL dan Daftar Endpoint</a></li>
            <li><a href="#doc-profil">1. Profil dan Cek Saldo</a></li>
            <li><a href="#doc-produk">2. Ambil Daftar Produk dan Harga Reseller</a></li>
            <li><a href="#doc-pesanan">3. Buat Pesanan (Bayar dari Saldo)</a></li>
            <li><a href="#doc-status">4. Pantau Status Pesanan</a></li>
            <li><a href="#doc-h2h">Kompatibilitas H2H Lama</a></li>
            <li><a href="#doc-webhook">Webhook Status Pesanan</a></li>
            <li><a href="#doc-error">6. Error dan Kode Status</a></li>
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
            <li>Produk prabayar yang sudah didukung fulfillment dapat dilihat lewat <code>GET /products</code>.</li>
            <li>Diskon reseller dihitung hingga {{ number_format($discountPercent, fmod($discountPercent, 1) === 0.0 ? 0 : 1, ',', '.') }}% dari harga publik; harga tidak pernah di bawah modal, jadi diskon aktual bisa lebih kecil.</li>
            <li>Pesanan dibayar dengan <span class="fw-semibold">saldo akun</span> (tanpa transfer manual).</li>
            <li>Pesanan API saat ini mendukung produk prabayar dengan fulfillment Digiflazz.</li>
            <li>Format respons JSON; kirim <code>Accept: application/json</code> agar error juga berupa JSON.</li>
        </ul>
    </x-ui.card>

    <x-ui.card id="doc-autentikasi" title="Autentikasi">
        <p class="fs-12">
            Kirim API key kamu di setiap request memakai salah satu header berikut:
        </p>
        <p class="fs-12">Bentuk header Bearer yang valid: <code>Authorization: Bearer ap_XXXXXXXX...</code>.</p>
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
        <p class="fs-12 mb-0 mt-2">
            Klien H2H lama dapat memakai endpoint kompatibilitas di bawah dengan field <code>api_key</code> pada body.
            Ganti API key lama dengan key reseller baru dari halaman pengaturan; key lama tidak dapat digunakan.
        </p>
        <p class="fs-12 mb-0 mt-2">
            Setiap <code>POST /orders</code> dan <code>POST /transaksi/create</code> juga wajib mengirim header
            <code>X-Transaction-PIN</code> berisi PIN transaksi 6 digit. Atur PIN dari
            <a href="{{ route('settings.pin') }}">Pengaturan PIN</a>; jangan kirim PIN pada body, query string, atau webhook.
        </p>
    </x-ui.card>

    <x-ui.card id="doc-hosted-flow" title="Alur Uang Toko Hosted">
        <p class="fs-12 mb-3">
            Toko hosted menerima pesanan dari customer dengan harga publik. Harga reseller dipakai sebagai biaya pemenuhan pesanan.
            Alurnya berbeda dari API reseller yang selalu memotong saldo reseller.
        </p>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card-ap r-sm p-3 h-100">
                    <h3 class="fs-13 fw-bold mb-2">Pembayaran tunai</h3>
                    <ol class="fs-12 tx-mute mb-0 ps-3">
                        <li>Customer menyerahkan harga publik langsung kepada reseller.</li>
                        <li>Reseller mengonfirmasi penerimaan tunai dengan PIN transaksi.</li>
                        <li>Saldo reseller dipotong sebesar <code>reseller_price</code>.</li>
                        <li>Selisih harga publik dan harga reseller menjadi keuntungan langsung reseller.</li>
                    </ol>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card-ap r-sm p-3 h-100">
                    <h3 class="fs-13 fw-bold mb-2">Pembayaran QRIS</h3>
                    <ol class="fs-12 tx-mute mb-0 ps-3">
                        <li>Customer membayar melalui halaman Midtrans, termasuk biaya QRIS bila ada.</li>
                        <li>Saldo reseller tidak dipotong.</li>
                        <li>Setelah berhasil, selisih harga dicatat sebagai komisi reseller.</li>
                        <li>Komisi dapat dilihat dan diajukan untuk pencairan melalui halaman reseller.</li>
                    </ol>
                </div>
            </div>
        </div>
        <div class="alert-ap r-sm bg-sky-l tx-navy fs-12 px-3 py-2 mt-3">
            Jika pembayaran tunai tidak dikonfirmasi, saldo reseller tidak dipotong dan pesanan dapat kedaluwarsa.
        </div>
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
                    <tr>
                        <td><span class="pill bg-sky-l tx-navy">POST</span></td>
                        <td><code>{{ url('/transaksi/create') }}</code></td>
                        <td>Kompatibilitas H2H lama: buat pesanan dengan kode produk.</td>
                    </tr>
                    <tr>
                        <td><span class="pill bg-sky-l tx-navy">POST</span></td>
                        <td><code>{{ url('/transaksi/status') }}</code></td>
                        <td>Kompatibilitas H2H lama: cek status pesanan prabayar.</td>
                    </tr>
                    <tr>
                        <td><span class="pill bg-sky-l tx-navy">POST</span></td>
                        <td><code>{{ url('/layanan/price_list') }}</code></td>
                        <td>Kompatibilitas H2H lama: daftar harga produk prabayar.</td>
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
            Daftar hanya berisi produk aktif prabayar dengan fulfillment Digiflazz dan diurutkan berdasarkan nama.
            Filter tersedia melalui query: <code>q</code> (nama/kode, maksimal 100 karakter),
            <code>brand_id</code>, <code>category_id</code>, dan <code>payment_type</code>
            (<code>prabayar</code>|<code>pascabayar</code>; filter pascabayar menghasilkan daftar kosong). Gunakan <code>per_page</code> antara 1–100;
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
                    <tr><td><code>payment_type</code></td><td>string</td><td><code>prabayar</code>; produk pascabayar belum didukung.</td></tr>
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
            Produk tidak ditemukan, tidak aktif, atau belum didukung pada endpoint detail menghasilkan <code>404</code>.
        </p>
    </x-ui.card>

    <x-ui.card id="doc-pesanan" title="3. Buat Pesanan (Bayar dari Saldo)">
        <p class="fs-12">
            Field: <code>product_id</code> atau <code>code</code> (wajib; <code>code</code> cocok untuk integrasi H2H lama),
            <code>target</code> (wajib), <code>zone</code> (wajib untuk produk yang butuh zone, mis. Mobile Legends),
            <code>reff_id</code> (opsional, maks. 50 karakter untuk idempotensi), dan <code>limit_price</code> (opsional).
            Untuk kompatibilitas, zona juga boleh dikirim sebagai <code>target: "userId|zoneId"</code>.
            Pembayaran selalu lewat saldo; pastikan saldo cukup.
        </p>
        <p class="fs-12">
            Header <code>X-Transaction-PIN</code> wajib untuk membuat pesanan, termasuk saat mengulang <code>reff_id</code>
            yang sama. Lima PIN salah mengunci transaksi selama 15 menit. Pesanan di atas Rp 500.000 ditunda selama 24 jam
            setelah PIN diubah atau direset.
        </p>
        <div class="position-relative mb-2">
            <pre class="ap-log-pre pe-5 mb-0">curl -X POST "{{ $apiBase }}/orders" \
  -H "X-API-Key: ap_XXXXXXXX..." \
  -H "X-Transaction-PIN: YOUR_6_DIGIT_PIN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"product_id": 12, "target": "081234567890", "reff_id": "order-0001", "limit_price": 7000}'</pre>
            <button type="button" class="ap-icon-btn position-absolute top-0 end-0 m-2" data-copy-text="curl -X POST &quot;{{ $apiBase }}/orders&quot; \
  -H &quot;X-API-Key: ap_XXXXXXXX...&quot; \
  -H &quot;X-Transaction-PIN: YOUR_6_DIGIT_PIN&quot; \
  -H &quot;Content-Type: application/json&quot; \
  -H &quot;Accept: application/json&quot; \
  -d '{&quot;product_id&quot;: 12, &quot;target&quot;: &quot;081234567890&quot;, &quot;reff_id&quot;: &quot;order-0001&quot;, &quot;limit_price&quot;: 7000}'" data-copy-label="cURL Buat Pesanan" data-tip="Salin"><span class="ms ms-sm">content_copy</span></button>
        </div>
        <div class="d-flex justify-content-end mb-3">
            <button type="button" class="btn-soft" data-copy-text="curl -X POST &quot;{{ $apiBase }}/orders&quot; \
  -H &quot;X-API-Key: ap_XXXXXXXX...&quot; \
  -H &quot;X-Transaction-PIN: YOUR_6_DIGIT_PIN&quot; \
  -H &quot;Content-Type: application/json&quot; \
  -H &quot;Accept: application/json&quot; \
  -d '{&quot;product_id&quot;: 12, &quot;target&quot;: &quot;081234567890&quot;, &quot;reff_id&quot;: &quot;order-0001&quot;, &quot;limit_price&quot;: 7000}'" data-copy-label="cURL Buat Pesanan"><span class="ms ms-sm">content_copy</span> Salin Contoh cURL</button>
        </div>
        <pre class="ap-log-pre mb-2">{
  "data": {
    "order_number": "TRX-20261020-AB12CD",
    "reff_id": "order-0001",
    "status": "processing",
    "status_label": "Diproses",
    "product": { "id": 12, "code": "XL5000", "name": "XL 5.000" },
    "target": "081234567890",
    "target_zone": null,
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
            Jika stok habis, saldo kurang, atau harga melebihi <code>limit_price</code>, request dibalas <code>422</code>
            dan tidak ada perubahan apa pun. Request ulang dengan <code>reff_id</code> yang sama mengembalikan pesanan
            yang sudah dibuat tanpa mendebit saldo atau stok lagi, tetapi tetap memerlukan PIN yang valid.
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

    <x-ui.card id="doc-h2h" title="Kompatibilitas H2H Lama">
        <p class="fs-12">
            Endpoint ini mempertahankan field dan bentuk respons transaksi utama untuk integrasi H2H lama.
            Gunakan API key reseller baru sebagai <code>api_key</code> form-urlencoded; request GET untuk membuat transaksi
            tidak didukung. Header <code>X-Transaction-PIN</code> wajib untuk membuat transaksi. Produk pascabayar dan provider
            selain Digiflazz belum tersedia pada API reseller.
        </p>
        <pre class="ap-log-pre mb-2">POST {{ url('/transaksi/create') }}
X-Transaction-PIN: YOUR_6_DIGIT_PIN
api_key={{ 'ap_XXXXXXXX' }}
code=ML86
reff_id=order-0001
target=12345678|1234
limit_price=7000</pre>
        <pre class="ap-log-pre mb-2">{
  "status": true,
  "message": "Pesanan berhasil dibuat.",
  "data": {
    "id": "TRX-20261020-AB12CD",
    "reff_id": "order-0001",
    "layanan": "Mobile Legends 86 Diamonds",
    "code": "ML86",
    "target": "12345678|1234",
    "price": 6500,
    "sn": null,
    "status": "processing",
    "created_at": "2026-10-20T10:15:00+07:00"
  },
  "code": 200
}</pre>
        <p class="fs-12 mb-0">
            Cek transaksi melalui <code>POST {{ url('/transaksi/status') }}</code> dengan field
            <code>api_key</code>, <code>id</code> dari respons create, dan <code>type=prabayar</code>.
            Daftar produk tersedia di <code>POST {{ url('/layanan/price_list') }}</code> dengan
            <code>api_key</code> dan <code>type=prabayar</code>.
        </p>
    </x-ui.card>

    <x-ui.card id="doc-webhook" title="Webhook Status Pesanan">
        <p class="fs-12">
            Atur URL dan buat secret webhook dari halaman Reseller. Notifikasi dikirim untuk pesanan API yang sukses,
            gagal, atau kedaluwarsa. Payload dikirim sebagai JSON; validasi tanda tangan menggunakan HMAC-SHA256 atas
            body mentah dan secret webhook.
        </p>
        <div class="table-responsive mb-2">
            <table class="table table-ui align-middle fs-12">
                <thead><tr><th>Header</th><th>Nilai</th></tr></thead>
                <tbody>
                    <tr><td><code>X-ATL-Signature</code></td><td><code>sha256=&lt;hmac_hex&gt;</code></td></tr>
                    <tr><td><code>X-ATL-Event</code></td><td><code>order.success</code>, <code>order.failed</code>, atau <code>order.expired</code></td></tr>
                    <tr><td><code>X-ATL-Delivery</code></td><td>ID delivery yang sama dipakai untuk semua percobaan ulang.</td></tr>
                </tbody>
            </table>
        </div>
        <p class="fs-12 mb-0">
            Webhook memakai antrean, timeout koneksi 5 detik, timeout respons 10 detik, dan maksimal 3 percobaan.
            DNS diperiksa dan alamat publik dipin untuk mencegah DNS rebinding; redirect tidak diikuti.
            Produksi memerlukan HTTPS port 443, ekstensi PHP cURL, akses egress langsung, dan queue worker aktif.
            Tanggapi dengan HTTP 2xx setelah notifikasi diterima.
        </p>
    </x-ui.card>

    <x-ui.card id="doc-error" title="6. Error dan Kode Status">
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
                        <td class="text-center"><span class="pill bg-errc-l tx-err">403</span></td>
                        <td>PIN transaksi belum dikirim atau belum diatur. Atur PIN di aplikasi lalu kirim header <code>X-Transaction-PIN</code>.</td>
                    </tr>
                    <tr>
                        <td class="text-center"><span class="pill bg-errc-l tx-err">404</span></td>
                        <td>Produk tidak ditemukan/tidak aktif, atau pesanan bukan milik kamu.</td>
                    </tr>
                    <tr>
                        <td class="text-center"><span class="pill bg-gold tx-gold">422</span></td>
                        <td>Validasi gagal: field kurang, stok habis, saldo kurang, operator tidak cocok, zone wajib diisi, atau PIN salah. Respons PIN salah memuat <code>errors.pin</code> dan <code>remaining_attempts</code>.</td>
                    </tr>
                    <tr>
                        <td class="text-center"><span class="pill bg-gold tx-gold">423</span></td>
                        <td>PIN terkunci setelah 5 percobaan salah, atau pesanan bernilai tinggi masih dalam masa tunggu 24 jam setelah reset PIN. Gunakan header <code>Retry-After</code> atau field <code>retry_after_seconds</code>.</td>
                    </tr>
                    <tr>
                        <td class="text-center"><span class="pill bg-errc-l tx-err">503</span></td>
                        <td>Endpoint kompatibilitas H2H lama memakai 503 jika produk tidak tersedia/belum didukung; API v1 membalas 422 untuk validasi pesanan dan 404 untuk detail produk.</td>
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
