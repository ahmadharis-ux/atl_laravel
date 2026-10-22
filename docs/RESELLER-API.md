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

- API key dibuat di **Pengaturan → Reseller** (aktifkan keanggotaan reseller).
- Key ditampilkan **satu kali** saat dibuat/disimpan ulang; yang tersimpan di server hanya hash SHA-256.
- "Buat ulang API key" langsung mencabut key lama.
- Bila keanggotaan reseller dinonaktifkan, semua API key dicabut (401).

### Rate limit

**120 request per menit** per API key (balas `429` bila terlampaui).

## Pricing

- `selling_price` — harga jual publik.
- `reseller_price` — yang dibayar reseller: diskon `config/reseller.discount_percent` (env `RESELLER_DISCOUNT_PERCENT`, default **5%**) dari `selling_price`, **dibatasi tidak di bawah `cost_price`**.
- `discount` — selisih `selling_price - reseller_price` (potensi untung reseller bila dijual dengan harga publik).

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

Query opsional: `q` (nama/kode), `brand_id`, `category_id`, `payment_type` (`prabayar`|`pascabayar`), `per_page` (default 50, maks 100).

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
}
```

`stock: null` berarti stok tidak terbatas. `requires_zone: true` → field `zone` wajib saat membuat pesanan (mis. Mobile Legends).

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
