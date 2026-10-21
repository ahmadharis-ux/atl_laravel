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
        <pre class="ap-log-pre">X-API-Key: ap_XXXXXXXX...
# atau
Authorization: Bearer ap_XXXXXXXX...</pre>
        <p class="fs-12 mb-0">
            Belum punya API key? Aktifkan dulu keanggotaan reseller di
            <a href="{{ route('settings.reseller') }}">halaman Reseller</a> —
            key hanya ditampilkan satu kali saat dibuat.
            API key bersifat rahasia; jangan bagikan ke pihak lain.
        </p>
    </x-ui.card>

    <x-ui.card id="doc-endpoint" title="Base URL dan Daftar Endpoint">
        <pre class="ap-log-pre mb-3">Base URL: {{ $apiBase }}</pre>
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
        <pre class="ap-log-pre mb-2">curl "{{ $apiBase }}/profile" \
  -H "X-API-Key: ap_XXXXXXXX..." \
  -H "Accept: application/json"</pre>
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
        <pre class="ap-log-pre mb-2">curl "{{ $apiBase }}/balance" \
  -H "X-API-Key: ap_XXXXXXXX..." \
  -H "Accept: application/json"</pre>
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
        <p class="fs-12">Query opsional: <code>q</code> (pencarian nama/kode), <code>brand_id</code>, <code>category_id</code>, <code>payment_type</code> (<code>prabayar</code>|<code>pascabayar</code>), <code>per_page</code> (default 50, maks 100).</p>
        <pre class="ap-log-pre">curl -G "{{ $apiBase }}/products" \
  -H "X-API-Key: ap_XXXXXXXX..." \
  -H "Accept: application/json" \
  --data-urlencode "q=pulsa" \
  --data-urlencode "per_page=20"</pre>
        <pre class="ap-log-pre mb-2">{
  "data": [
    {
      "id": 12,
      "code": "XL5000",
      "name": "XL 5.000",
      "brand": "XL",
      "category": "Pulsa",
      "payment_type": "prabayar",
      "selling_price": 6500,
      "reseller_price": 6175,
      "discount": 325,
      "stock": 100,
      "unlimited_stock": false,
      "requires_zone": false,
      "target_label": "Nomor Tujuan"
    }
  ],
  "meta": { "current_page": 1, "per_page": 20, "last_page": 3, "total": 54 }
}</pre>
        <p class="fs-12 mb-0">
            <span class="fw-semibold">reseller_price</span> adalah yang kamu bayar;
            <span class="fw-semibold">selling_price</span> adalah harga jual publik — selisihnya adalah untungmu kalau kamu jual dengan harga publik.
        </p>
    </x-ui.card>

    <x-ui.card id="doc-pesanan" title="3. Buat Pesanan (Bayar dari Saldo)">
        <p class="fs-12">
            Field: <code>product_id</code> (wajib), <code>target</code> (wajib — nomor tujuan), <code>zone</code>
            (wajib untuk produk yang butuh zone, mis. Mobile Legends — cek dari field <code>requires_zone</code>).
            Pembayaran selalu lewat saldo; pastikan saldo cukup.
        </p>
        <pre class="ap-log-pre">curl -X POST "{{ $apiBase }}/orders" \
  -H "X-API-Key: ap_XXXXXXXX..." \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"product_id": 12, "target": "081234567890"}'</pre>
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
  "message": "422 Unprocessable Content",
  "errors": {
    "product_id": ["Stok produk habis."]
  }
}</pre>
    </x-ui.card>

    <div class="card-ap r-card p-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <span class="fs-12 tx-mute">Butuh bantuan? Hubungi tim Atlantic Pedia.</span>
        <a href="{{ route('settings.reseller') }}" class="btn-navy">Kembali ke Halaman Reseller</a>
    </div>
</div>
@endsection
