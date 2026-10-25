# Reseller API (v1)

API untuk akun reseller Atlantic Pedia: ambil daftar produk & harga khusus reseller, buat pesanan dari saldo, dan pantau status transaksi — semua lewat API key (tanpa sesi browser / CSRF).

Dokumentasi yang sama juga tersedia di aplikasi: **Pengaturan → Reseller → Dokumentasi API** (`/pengaturan/reseller/dokumentasi`).

## Base URL

```
https://<domain>/api/v1/reseller
```

## Autentikasi

Kirim API key di setiap request memakai salah satu header:

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

### Rate limit

**120 request per menit** per API key (balas `429` bila terlampaui).

## Pricing

- `selling_price` — harga jual publik.
- `reseller_price` — yang dibayar reseller: diskon `config/reseller.discount_percent` (env `RESELLER_DISCOUNT_PERCENT`, default **5%**) dari `selling_price`, **dibatasi tidak di bawah `cost_price`**.
- `discount` — selisih nominal `selling_price - reseller_price`; margin kotor indikatif bila dijual dengan harga publik, bukan jaminan laba bersih.

## Alur pembayaran (saldo)

Pesanan API **tidak** memakai QRIS/VA dan tidak menerapkan `reseller_price` di checkout web. Reseller wajib punya saldo dulu:

1. Top up di aplikasi web: **Deposit → Top Up Saldo** (`/saldo/topup`).
2. Pilih metode (QRIS/Midtrans atau transfer manual). Deposit berstatus `pending` dengan kode pembayaran.
3. Setelah pembayaran terverifikasi (webhook Midtrans atau verifikasi admin), saldo masuk lewat `WalletLedger` dan deposit menjadi `paid`.
4. Baru setelah itu `POST /orders` bisa berhasil. Endpoint memakai `reseller_price` dan mendebit saldo secara atomik; bila saldo kurang, balasan `422` (`Insufficient balance`).
5. Jika pesanan gagal dan di-refund, saldo dikembalikan lewat ledger (bukan pembayaran ulang).

Catatan: checkout web biasa memakai `selling_price` publik — harga reseller hanya berlaku untuk pesanan lewat API reseller.


## Endpoints

Semua endpoint membalas JSON. Pesanan selalu dibayar dari **saldo akun** (`payment_method = "saldo"`).

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/profile` | Profil, saldo, diskon berlaku |
| GET | `/balance` | Saldo + 10 mutasi wallet terakhir |
| GET | `/products` | Daftar produk aktif + harga reseller |
| GET | `/products/{id}` | Detail satu produk (404 bila tidak aktif) |
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

Daftar hanya berisi produk aktif dan diurutkan berdasarkan nama. Response memakai `data` untuk item dan `meta` untuk paginasi; `total` adalah jumlah hasil yang cocok dengan filter.

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

Mengambil detail produk aktif. Body response berisi objek `data` dengan kontrak field yang sama dengan setiap item pada endpoint list, tanpa `meta`.

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

Produk yang tidak ditemukan atau tidak aktif menghasilkan `404`. `requires_zone: true` berarti kirim field `zone` ketika membuat pesanan.

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
| `product_id` | ya | Harus produk **aktif** |
| `target` | ya | Nomor tujuan (3–64 karakter). Operator harus sesuai brand produk (kategori pulsa/paket data) |
| `zone` | kondisional | Wajib bila produk `requires_zone` |

```bash
curl -X POST "https://<domain>/api/v1/reseller/orders" \
  -H "X-API-Key: ap_XXXX" -H "Content-Type: application/json" \
  -d '{"product_id": 12, "target": "081234567890"}'
```

Respons `201`:

```json
{
  "data": {
    "order_number": "TRX-20261020-AB12CD",
    "status": "processing",
    "status_label": "Diproses",
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

Status pesanan: `pending` → `processing` → `success` / `failed` / `expired`. Pesanan dengan provider (Digiflazz) otomatis masuk antan fulfilment saat dibuat.

### GET `/orders`, `/orders/{order_number}`

List dibatasi ke pesanan milik API key sendiri (`meta` berisi paginasi, 20 per halaman). Contoh `data[].status`: `pending`, `processing`, `success`, `failed`, `expired`.

## Error

| Kode | Arti |
|---|---|
| `401` | API key tidak ditemukan / tidak valid / bukan reseller aktif |
| `404` | Produk tidak aktif/tidak ada, atau pesanan bukan milik sendiri |
| `422` | Validasi: field kurang, stok habis, saldo kurang (`errors.balance`), operator tidak cocok (`errors.target`), zone wajib (`errors.zone`) |
| `429` | Rate limit 120 req/menit |

```json
{
  "message": "422 Unprocessable Content",
  "errors": { "product_id": ["Stok produk habis."] }
}
```

## Terkait

- Panel & aktivasi reseller: `resources/views/user/settings/reseller.blade.php`, `app/Http/Controllers/User/ResellerController.php`
- Endpoint API: `routes/api.php`, `app/Http/Controllers/Api/Reseller/*`
- Middleware key: `app/Http/Middleware/AuthenticateReseller.php`
- Harga reseller: `app/Models/Product.php::resellerPrice()` + `config/reseller.php`
- Laporan admin: `/admin/resellers` (permission `resellers.view`)
- Tests: `tests/Feature/ResellerTest.php`, `ResellerApiTest.php`, `AdminResellerReportTest.php`
