# Reseller API (v1)

API reseller Atlantic Pedia: ambil produk dan harga reseller, buat pesanan dari saldo, serta pantau status. Endpoint API v1 dan endpoint kompatibilitas H2H memakai API key tanpa sesi browser atau token CSRF. API key H2H dari sistem lama tidak otomatis berlaku; gunakan key reseller baru.

Dokumentasi yang sama juga tersedia di aplikasi: **Pengaturan → Reseller → Dokumentasi API** (`/pengaturan/reseller/dokumentasi`).

## Base URL

```
https://<domain>/api/v1/reseller
```

## Autentikasi

Untuk API v1, kirim API key di setiap request memakai salah satu header:

```
X-API-Key: ap_XXXXXXXX...
Authorization: Bearer ap_XXXXXXXX...
```

- Ajukan keanggotaan di **Pengaturan → Reseller** dan setujui ketentuannya. API key baru dapat dibuat setelah admin menyetujui permohonan.
- Key ditampilkan **satu kali** saat dibuat/disimpan ulang; yang tersimpan di server hanya hash SHA-256.
- "Buat ulang API key" langsung mencabut key lama.
- Bila reseller dinonaktifkan atau ditangguhkan, semua API key dicabut (401) dan pesanan baru dari toko hosted dihentikan.
- IP server dapat dibatasi melalui allowlist opsional pada pengajuan. Request dari IP yang tidak diizinkan mendapat balasan `403`.
- Aktivitas dicatat sebagai waktu pemakaian API terakhir (pembaruan paling banyak sekali per menit), bukan indikator reseller sedang online.
- Endpoint H2H kompatibilitas di root domain juga menerima `api_key` pada body form-urlencoded; cara ini hanya untuk ketiga endpoint lama yang didaftarkan.

Gunakan bentuk Bearer yang valid `Authorization: Bearer ap_XXXXXXXX...` bila tidak memakai header `X-API-Key`.

### PIN transaksi untuk debit saldo

Setiap pembuatan pesanan yang memotong saldo wajib mengirim header berikut:

```
X-Transaction-PIN: YOUR_6_DIGIT_PIN
```

- Header ini wajib untuk `POST /orders` dan `POST /transaksi/create`; endpoint baca tidak memerlukannya.
- PIN terdiri dari 6 angka dan diatur dari **Pengaturan -> PIN Transaksi** pada aplikasi web.
- PIN tidak boleh dikirim dalam body, query string, webhook, atau dicatat pada log aplikasi. Kirim hanya melalui header HTTPS.
- PIN tetap wajib pada request ulang dengan `reff_id` yang sama. Idempotensi mencegah debit ganda, bukan menggantikan verifikasi PIN.
- Lima PIN salah mengunci PIN selama 15 menit. Setelah PIN diubah/reset, pesanan di atas Rp 500.000 ditunda hingga 24 jam. Nilai ini dapat dikonfigurasi oleh operator.

### Rate limit

**120 request per menit** per API key (balas `429` bila terlampaui).

## Pricing

- `selling_price` — harga jual publik.
- `reseller_price` — yang dibayar reseller: memakai diskon global yang diatur admin di **Admin → Halaman & Pembayaran**, atau override harga khusus per produk. Env `RESELLER_DISCOUNT_PERCENT` hanya menjadi fallback bila admin belum menetapkan nilai. Harga tetap **tidak boleh di bawah `cost_price`**.
- `discount` — selisih nominal `selling_price - reseller_price`; margin kotor indikatif bila dijual dengan harga publik, bukan jaminan laba bersih.

## Alur pembayaran (saldo)

Pesanan API **tidak** memakai QRIS/VA dan tidak menerapkan `reseller_price` di checkout web. Reseller wajib punya saldo dulu:

1. Top up di aplikasi web: **Deposit → Top Up Saldo** (`/saldo/topup`).
2. Pilih metode (QRIS/Midtrans atau transfer manual). Deposit berstatus `pending` dengan kode pembayaran.
3. Setelah pembayaran terverifikasi (webhook Midtrans atau verifikasi admin), saldo masuk lewat `WalletLedger` dan deposit menjadi `paid`.
4. Baru setelah itu `POST /orders` bisa berhasil. Endpoint memakai `reseller_price` dan mendebit saldo secara atomik; bila saldo kurang, balasan `422` (`Insufficient balance`).
5. Jika pesanan gagal dan di-refund, saldo dikembalikan lewat ledger (bukan pembayaran ulang).

Catatan: checkout web biasa memakai `selling_price` publik — harga reseller hanya berlaku untuk pesanan lewat API reseller.

## Toko hosted reseller dan alur uang

Toko hosted dapat dibuka dari halaman **Pengaturan → Reseller → Toko**. Customer membayar dengan harga publik, sedangkan harga reseller dipakai sebagai biaya pemenuhan pesanan.

### Pembayaran tunai

1. Customer menyerahkan uang sebesar harga publik langsung kepada reseller.
2. Pesanan menunggu konfirmasi reseller.
3. Reseller mengonfirmasi penerimaan tunai dan memasukkan PIN transaksi.
4. Saldo reseller dipotong sebesar `reseller_price` untuk membayar biaya produk.
5. Selisih `selling_price - reseller_price` menjadi keuntungan reseller secara langsung.

Jika reseller tidak mengonfirmasi, saldo tidak dipotong dan pesanan dapat kedaluwarsa.

### Pembayaran QRIS

1. Customer membayar `selling_price` melalui halaman pembayaran Midtrans, ditambah biaya QRIS bila ada.
2. Saldo reseller tidak dipotong.
3. Setelah pembayaran berhasil, pesanan diproses dan selisih `selling_price - reseller_price` dicatat sebagai komisi reseller.
4. Komisi dapat dilihat di halaman toko reseller dan diajukan untuk pencairan sesuai proses admin.

Pembayaran tunai dan QRIS pada toko hosted berbeda dari `POST /orders` API: pesanan API selalu menggunakan saldo reseller dan memerlukan header `X-Transaction-PIN`.


## Endpoints

Semua endpoint membalas JSON. Pesanan selalu dibayar dari **saldo akun** (`payment_method = "saldo"`).

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/profile` | Profil, saldo, diskon berlaku |
| GET | `/balance` | Saldo + 10 mutasi wallet terakhir |
| GET | `/products` | Daftar produk prabayar Digiflazz yang didukung + harga reseller |
| GET | `/products/{id}` | Detail produk yang didukung (404 bila tidak tersedia) |
| POST | `/orders` | Buat pesanan (bayar dari saldo) |
| GET | `/orders` | Riwayat pesanan milik sendiri |
| GET | `/orders/{order_number}` | Detail pesanan (404 bila bukan milik sendiri) |

### GET `/products`

Query opsional:

| Parameter | Tipe | Aturan | Fungsi |
|---|---|---|---|
| `q` | string | Maksimal 100 karakter | Cari berdasarkan nama atau kode produk |
| `brand_id` | integer | — | Filter berdasarkan ID brand |
| `category_id` | integer | — | Filter berdasarkan ID kategori |
| `payment_type` | string | `prabayar` atau `pascabayar` | Filter jenis pembayaran produk |
| `per_page` | integer | 1–100; default 50 | Jumlah item per halaman |

Daftar hanya berisi produk aktif prabayar dengan fulfillment Digiflazz dan diurutkan berdasarkan nama. Filter `payment_type` menerima `prabayar` atau `pascabayar`, tetapi daftar pascabayar akan kosong karena jenis tersebut belum didukung. Response memakai `data` untuk item dan `meta` untuk paginasi; `total` adalah jumlah hasil yang cocok dengan filter.

```bash
curl -G "https://<domain>/api/v1/reseller/products" \
  -H "X-API-Key: ap_XXXX" --data-urlencode "q=pulsa" --data-urlencode "per_page=20"
```

```json
{
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
}
```

#### Kontrak field produk

List dan detail memakai bentuk objek produk yang sama.

| Field | Tipe JSON | Null? | Arti |
|---|---|---:|---|
| `id` | integer | Tidak | ID produk untuk `product_id` saat membuat pesanan |
| `code` | string | Tidak | Kode produk |
| `name` | string | Tidak | Nama produk |
| `brand` | string atau null | Ya | Nama brand; null jika relasi brand tidak tersedia |
| `category` | string atau null | Ya | Nama kategori; null jika relasi kategori tidak tersedia |
| `payment_type` | string | Tidak | `prabayar` atau `pascabayar` |
| `region` | string atau null | Ya | Wilayah produk; null berarti umum/tidak ditentukan |
| `selling_price` | integer | Tidak | Harga jual publik dalam Rupiah |
| `reseller_price` | integer | Tidak | Harga yang dibayar reseller dalam Rupiah |
| `discount` | integer | Tidak | Selisih nominal `selling_price - reseller_price` dalam Rupiah, bukan persentase |
| `stock` | integer atau null | Ya | Stok tersedia; null bila stok tidak terbatas |
| `unlimited_stock` | boolean | Tidak | Menyatakan stok tidak dibatasi |
| `requires_zone` | boolean | Tidak | Bila true, `zone` wajib dikirim saat membuat pesanan |
| `target_label` | string | Tidak | Label tujuan untuk membantu integrasi form |

Harga dalam respons berupa bilangan bulat Rupiah. `reseller_price` adalah jumlah yang didebit untuk pesanan API; `discount` adalah selisih nominal terhadap harga jual publik, bukan jaminan laba karena harga jual reseller dapat berbeda.

### GET `/products/{id}`

Mengambil detail produk aktif yang didukung untuk fulfillment API. Body response berisi objek `data` dengan kontrak field yang sama dengan setiap item pada endpoint list, tanpa `meta`.

```bash
curl "https://<domain>/api/v1/reseller/products/12" \
  -H "X-API-Key: ap_XXXX" \
  -H "Accept: application/json"
```

```json
{
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
}
```

Produk yang tidak ditemukan, tidak aktif, atau belum didukung menghasilkan `404`. `requires_zone: true` berarti kirim field `zone` ketika membuat pesanan.

Parameter query yang tidak valid (mis. `per_page=101`) menghasilkan `422` dengan format validasi:

```json
{
  "message": "The per page field must not be greater than 100.",
  "errors": {
    "per_page": ["The per page field must not be greater than 100."]
  }
}
```

### POST `/orders`

| Field | Wajib | Keterangan |
|---|---|---|
| `product_id` atau `code` | salah satu | Produk aktif prabayar Digiflazz |
| `target` | ya | Tujuan (3–150 karakter). Operator harus sesuai brand untuk pulsa/paket data |
| `zone` | kondisional | Maksimal 20 karakter; wajib bila produk `requires_zone` |
| `reff_id` | tidak | ID unik milik klien (maksimal 50 karakter; huruf/angka/titik/garis bawah/minus). Retry dengan ID yang sama mengembalikan pesanan sebelumnya tanpa debit ulang |
| `limit_price` | tidak | Batas maksimal harga yang diterima; request ditolak bila `reseller_price` lebih tinggi |

Untuk game yang memerlukan zona, kirim `zone` terpisah. Format H2H lama `target: "userId|zoneId"` juga diterima.

```bash
curl -X POST "https://<domain>/api/v1/reseller/orders" \
  -H "X-API-Key: ap_XXXX" \
  -H "X-Transaction-PIN: YOUR_6_DIGIT_PIN" \
  -H "Content-Type: application/json" \
  -d '{"product_id": 12, "target": "081234567890", "reff_id": "order-0001", "limit_price": 7000}'
```

Respons `201`:

```json
{
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
}
```

Dalam satu transaksi database: kunci baris produk & saldo, cek status/stok, decrement stok + tambah `sold_count`, buat order dengan `source = "api"`, lalu debit saldo. Gagal (stok habis / saldo kurang / operator tidak cocok) → **rollback penuh** + `422`.

Status pesanan: `pending` → `processing` → `success` / `failed` / `expired`. Pesanan API prabayar Digiflazz otomatis masuk antrean fulfillment.

### GET `/orders`, `/orders/{order_number}`

List dibatasi ke pesanan milik API key sendiri (`meta` berisi paginasi, 20 per halaman). Contoh `data[].status`: `pending`, `processing`, `success`, `failed`, `expired`.

## Error

| Kode | Arti |
|---|---|
| `401` | API key tidak ditemukan / tidak valid / bukan reseller aktif |
| `403` | PIN transaksi tidak dikirim atau belum diatur. Atur PIN di aplikasi lalu kirim `X-Transaction-PIN`. |
| `404` | Produk tidak aktif/tidak ada, atau pesanan bukan milik sendiri |
| `422` | Validasi: field kurang, stok habis, saldo kurang (`errors.balance`), operator tidak cocok (`errors.target`), zone wajib (`errors.zone`), atau PIN salah (`errors.pin`, `remaining_attempts`) |
| `423` | PIN terkunci atau pesanan bernilai tinggi masih dalam masa tunggu setelah reset PIN. Respons menyertakan `Retry-After` dan `retry_after_seconds`. |
| `503` | Endpoint kompatibilitas H2H: produk tidak tersedia/belum didukung, atau jenis pascabayar |
| `429` | Rate limit 120 req/menit |

```json
{
  "message": "422 Unprocessable Content",
  "errors": { "product_id": ["Stok produk habis."] }
}
```

## Kompatibilitas H2H lama

Endpoint berikut berada di root domain, bukan di bawah `/api/v1/reseller`. Gunakan POST form-urlencoded dengan API key reseller baru di field `api_key`. Khusus `POST /transaksi/create`, kirim juga `X-Transaction-PIN` sebagai header HTTPS:

| Method | Endpoint | Field utama |
|---|---|---|
| POST | `/transaksi/create` | Header `X-Transaction-PIN`; body `api_key`, `code`, `reff_id`, `target`, opsional `limit_price` |
| POST | `/transaksi/status` | `api_key`, `id` (ID transaksi dari respons create), `type=prabayar` |
| POST | `/layanan/price_list` | `api_key`, `type=prabayar` |

`target` dapat memakai format `userId|zoneId`. Bentuk respons transaksi lama mempertahankan field `id`, `reff_id`, `layanan`, `code`, `target`, `price`, `sn`, dan `status`. Pascabayar serta provider selain Digiflazz belum didukung; produk tersebut tidak ditampilkan atau dipesan.

## Webhook status pesanan

Atur URL webhook dan buat secret dari **Pengaturan → Reseller**. Perubahan status akhir pesanan API (sukses, gagal, kedaluwarsa) dikirim sebagai JSON dengan header `X-ATL-Event`, `X-ATL-Delivery`, dan `X-ATL-Signature: sha256=<hmac_hex>`. Signature adalah HMAC-SHA256 dari body mentah menggunakan secret webhook.

Webhook memakai queue, timeout koneksi 5 detik, timeout respons 10 detik, maksimal 3 percobaan dengan jeda 30 dan 120 detik. DNS diverifikasi dan alamat IP publik yang lolos pemeriksaan dipin untuk mencegah DNS rebinding/SSRF; redirect tidak diikuti dan webhook harus memakai HTTPS port 443 di produksi. Server memerlukan ekstensi PHP cURL dan akses egress langsung ke IP publik tujuan. Jalankan queue worker pada lingkungan produksi. Beri respons HTTP 2xx setelah notifikasi diterima; gunakan `X-ATL-Delivery` untuk mencegah pemrosesan ganda saat retry.

## Terkait

- Panel & aktivasi reseller: `resources/views/user/settings/reseller.blade.php`, `app/Http/Controllers/User/ResellerController.php`
- Endpoint API: `routes/api.php`, `app/Http/Controllers/Api/Reseller/*`
- Middleware key: `app/Http/Middleware/AuthenticateReseller.php`
- Harga reseller: `app/Models/Product.php::resellerPrice()` + `config/reseller.php`
- Laporan admin: `/admin/resellers` (permission `resellers.view`)
- Tests: `tests/Feature/ResellerTest.php`, `ResellerApiTest.php`, `AdminResellerReportTest.php`
