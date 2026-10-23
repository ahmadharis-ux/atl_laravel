# RESELLER — Brief Asli

> KETIKA USER BERHASIL MENDAFTAR DIA JUGA BISA MENJADI RESELLER
> TOLONG SEDIAKAN SEMUA DOKUMENTASI UNTUK MENAMPILKAN PRODUCT DARI KITA, ACCESS DARI KITA SEPERTI API KEY, DAN CARA TRANSAKSI YA JUGA, BENEFITNYA DARI SEGI HARGA PRODUCT YANG DIJUAL LEBIH MURAH KE CUSTOMER USER TERSEBUT. DAN ADA LAPORANNYA UNTUK USER YANG MEMILIH UNTUK MENJADI RESELLER.
>
> SISANYA TOLONG TAMBAHKAN DISINI DAN EKSEKUSI LIST MANA, KETIKA OPENCODE MENGERJAKAN MANA SAJA YANG SUDAH OK SILAHKAN CEKLIST DALAM LIST YANG OPENCODE BUAT. TIDAK PERLU MEMINTA PERSETUJUAN SAYA, SILAHKAN BUAT SESUAI BEST PRACTICE NANTI HASIL RESUME DISINI NANTI SAYA BACA.

---

## Checklist

- [x] **User bisa menjadi reseller** — Pengaturan → Reseller: aktifkan / nonaktifkan kapan saja (gratis, keanggotaan bersifat opsional).
- [x] **API key** — dibuat otomatis saat aktivasi (format `ap_…`), disimpan sebagai hash SHA-256, ditampilkan **sekali saja**; tersedia aksi Buat Ulang (key lama langsung mati) & Nonaktifkan (key dicabut).
- [x] **Dokumentasi: menampilkan produk kita** — `GET /api/v1/reseller/products` (+ filter q/brand/category/payment type, paginasi) dan `GET /products/{id}`; respons menyertakan `selling_price`, `reseller_price`, `discount`, stok, dan `requires_zone`.
- [x] **Dokumentasi: akses via API key** — header `X-API-Key: ap_…` atau `Authorization: Bearer ap_…`; penjelasan lengkap di halaman in-app **Pengaturan → Reseller → Dokumentasi API** dan di `docs/RESELLER-API.md`.
- [x] **Dokumentasi: cara transaksi** — `POST /api/v1/reseller/orders` (bayar dari saldo), riwayat & status lewat `GET /orders`, `GET /orders/{order_number}`; contoh curl + contoh JSON respons ada di kedua dokumen.
- [x] **Dokumentasi: benefit harga lebih murah** — `reseller_price` = diskon `RESELLER_DISCOUNT_PERCENT` (default 5%) dari harga jual, tidak pernah di bawah `cost_price`; selisih (`discount`) = potensi untung reseller.
- [x] **Laporan reseller untuk admin** — menu **Laporan → Reseller** (`/admin/resellers`): daftar user yang memilih jadi reseller + status aktif, sejak kapan, jumlah transaksi, transaksi via API, nominal API, hint API key; kartu statistik (total reseller, reseller aktif, transaksi & nominal API); pencarian + filter status + pagination (ajax).

### Best-practice (tambahan)

- [x] Migrasi: kolom `users.is_reseller/reseller_api_key/reseller_api_key_hint/reseller_since` (unique + index) & `orders.source` (default `web`, order API → `api`) — sudah dijalankan.
- [x] Keamanan API: key di-hash, reseller-only guard, throttle `120 request/menit` per key (`throttle:reseller-api`), JSON 401/404/422/429 yang jelas; API terpisah dari sesi/CSRF (`routes/api.php` via `withRouting(api:)`).
- [x] Konsistensi aturan transaksi: validasi zona (`requires_zone`) & operator tujuan dipakai bareng (`OrderTarget::ensureOperatorMatches`), nomor transaksi bareng (`OrderNumber::generate`, `TRX-Ymd-XXXXXX`).
- [x] Order API aman: stok + saldo di-lock & didebit dalam 1 transaksi DB; gagal (stok/saldo) → rollback penuh + 422; fulfilment provider di-dispatch otomatis.
- [x] Laporan admin diamankan permission `resellers.view` (group RBAC "Reseller"; default ADMIN/STAFF/CS; Superadmin semua) + sidebar "Laporan" kini punya anak menu Reseller.
- [x] Dokumen developer: `docs/RESELLER-API.md` (endpoint, field, contoh, error, related files).
- [x] Tests (36 baru, semua hijau): `tests/Feature/ResellerTest.php` (panel web + aktivasi/key/deaktivasi), `ResellerApiTest.php` (auth, produk, pricing, order success/422, zona, operator, list/detail), `AdminResellerReportTest.php` (permission, filter, statistik, ajax rows).

---

## Resume (hasil pengerjaan — untuk dibaca)

**Alur user:** aktifkan di `/pengaturan/reseller` → API key tampil sekali (tombol salin) → baca dokumentasi di `/pengaturan/reseller/dokumentasi` → transaksi via API dengan harga reseller → admin memantau lewat `/admin/resellers`.

**File utama:**

| Area | Lokasi |
|---|---|
| Panel & docs (user) | `app/Http/Controllers/User/ResellerController.php`, `resources/views/user/settings/reseller.blade.php`, `reseller-docs.blade.php` |
| API | `routes/api.php`, `app/Http/Controllers/Api/Reseller/{AccountController,ProductController,OrderController}.php`, `app/Http/Middleware/AuthenticateReseller.php` |
| Laporan admin | `app/Http/Controllers/Admin/ResellerController.php`, `resources/views/admin/reseller/{index,_rows,_footer}.blade.php` |
| Harga & key | `app/Models/Product.php::resellerPrice()`, `app/Support/ResellerKey.php`, `app/Support/OrderNumber.php`, `config/reseller.php` |
| RBAC & menu | `app/Support/Rbac.php` (permission `resellers.view`), `resources/views/partials/admin-sidebar.blade.php` |
| Migrasi | `2026_10_20_190443_add_reseller_fields_to_users_table.php`, `2026_10_20_190444_add_source_to_orders_table.php` (sudah jalan + `db:seed` untuk permission) |
| Dokumen | `docs/RESELLER-API.md` (dev), halaman in-app docs (untuk reseller) |

**Konfigurasi:** env `RESELLER_DISCOUNT_PERCENT` (default `5`), rate limit 120 req/menit/key.

**Verifikasi (gate terakhir):** `vendor/bin/pint --dirty --format agent` ✅ · `php artisan view:cache` ✅ · `php artisan test --compact` → **407 tests, 1376 assertions, 0 gagal** (baseline 371 + 36 reseller).

**Catatan:**
- Discount dibatasi `max(cost_price, …)` — produk bermarkup 0 tidak menghasilkan diskon (harga reseller = harga jual), by design agar tidak jual di bawah modal.
- Menonaktifkan reseller tidak menghapus `reseller_since` (riwayat tetap ada untuk laporan); key dicabut.
- Order API `source = 'api'`; order web tetap `'web'` — dipakai perhitungan kolom "Via API"/"Nominal API" di laporan.
