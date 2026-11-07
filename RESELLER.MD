# RESELLER — Brief Asli

> KETIKA USER BERHASIL MENDAFTAR DIA JUGA BISA MENJADI RESELLER
> TOLONG SEDIAKAN SEMUA DOKUMENTASI UNTUK MENAMPILKAN PRODUCT DARI KITA, ACCESS DARI KITA SEPERTI API KEY, DAN CARA TRANSAKSI YA JUGA, BENEFITNYA DARI SEGI HARGA PRODUCT YANG DIJUAL LEBIH MURAH KE CUSTOMER USER TERSEBUT. DAN ADA LAPORANNYA UNTUK USER YANG MEMILIH UNTUK MENJADI RESELLER.
>
> SISANYA TOLONG TAMBAHKAN DISINI DAN EKSEKUSI LIST MANA, KETIKA OPENCODE MENGERJAKAN MANA SAJA YANG SUDAH OK SILAHKAN CEKLIST DALAM LIST YANG OPENCODE BUAT. TIDAK PERLU MEMINTA PERSETUJUAN SAYA, SILAHKAN BUAT SESUAI BEST PRACTICE NANTI HASIL RESUME DISINI NANTI SAYA BACA.

---

## Checklist

- [x] **User bisa mengajukan menjadi reseller** — Pengaturan → Reseller: ajukan keanggotaan gratis; admin meninjau dan menyetujui sebelum toko/API aktif. Reseller aktif dapat menonaktifkan keanggotaannya.
- [x] **API key** — setelah permohonan disetujui, reseller membuat key dari panel (format `ap_…`); disimpan sebagai hash SHA-256 dan key asli ditampilkan **sekali saja**. Buat Ulang mencabut key lama; penangguhan atau deaktivasi juga mencabut key.
- [x] **Dokumentasi: menampilkan produk kita** — `GET /api/v1/reseller/products` (+ filter q/brand/category/payment type, paginasi) dan `GET /products/{id}`; hanya produk aktif prabayar Digiflazz yang didukung fulfillment ditampilkan, dengan harga, stok, dan kebutuhan zona.
- [x] **Dokumentasi: akses via API key** — header `X-API-Key: ap_…` atau `Authorization: Bearer ap_…`; penjelasan lengkap di halaman in-app **Pengaturan → Reseller → Dokumentasi API** dan di `docs/RESELLER-API.md`.
- [x] **Dokumentasi: cara transaksi** — `POST /api/v1/reseller/orders` (bayar dari saldo), riwayat & status lewat `GET /orders`, `GET /orders/{order_number}`; kompatibilitas H2H tersedia di `/transaksi/create`, `/transaksi/status`, dan `/layanan/price_list`.
- [x] **Dokumentasi: benefit harga lebih murah** — `reseller_price` = diskon `RESELLER_DISCOUNT_PERCENT` (default 5%) dari harga jual, tidak pernah di bawah `cost_price`; selisih (`discount`) = potensi untung reseller.
- [x] **Laporan reseller untuk admin** — menu **Laporan → Reseller** (`/admin/resellers`): daftar user yang memilih jadi reseller + status aktif, sejak kapan, jumlah transaksi, transaksi via API, nominal API, hint API key; kartu statistik (total reseller, reseller aktif, transaksi & nominal API); pencarian + filter status + pagination (ajax).

### Best-practice (tambahan)

- [x] Migrasi: kolom `users.is_reseller/reseller_api_key/reseller_api_key_hint/reseller_since` (unique + index) & `orders.source` (default `web`, order API → `api`) — sudah dijalankan.
- [x] Keamanan API: key di-hash, reseller-only guard, throttle `120 request/menit` per key (`throttle:reseller-api`), API key body hanya diizinkan untuk route H2H kompatibilitas; route API tidak memakai sesi/CSRF.
- [x] Konsistensi aturan transaksi: validasi zona (`requires_zone`) & operator tujuan dipakai bareng (`OrderTarget::ensureOperatorMatches`), nomor transaksi bareng (`OrderNumber::generate`, `TRX-Ymd-XXXXXX`).
- [x] Order API aman: stok + saldo di-lock & didebit dalam 1 transaksi DB; `reff_id` idempoten, `limit_price` diperiksa, produk tak didukung ditolak, dan fulfillment provider di-dispatch otomatis.
- [x] Webhook status pesanan: payload HMAC-SHA256, delivery ID stabil saat retry, timeout dan retry queue, konfigurasi URL/secret dari panel reseller.
- [x] Laporan admin diamankan permission `resellers.view` (group RBAC "Reseller"; default ADMIN/STAFF/CS; Superadmin semua) + sidebar "Laporan" kini punya anak menu Reseller.
- [x] Dokumen developer: `docs/RESELLER-API.md` (endpoint, field, contoh, error, related files).
- [x] Feature tests mencakup panel dan lifecycle permohonan/key, API modern dan endpoint H2H kompatibel, idempotensi/limit harga, fulfillment dan webhook, serta laporan admin.

---

## Resume (hasil pengerjaan — untuk dibaca)

**Alur user:** ajukan keanggotaan di `/pengaturan/reseller` → tunggu persetujuan admin → setelah aktif, buat dan simpan API key sekali tampil → baca dokumentasi di `/pengaturan/reseller/dokumentasi` → transaksi via API dengan harga reseller → admin memantau lewat `/admin/resellers`.

**File utama:**

| Area | Lokasi |
|---|---|
| Panel & docs (user) | `app/Http/Controllers/User/ResellerController.php`, `resources/views/user/settings/reseller.blade.php`, `reseller-docs.blade.php` |
| API | `routes/api.php`, `app/Http/Controllers/Api/Reseller/{AccountController,ProductController,OrderController}.php`, `app/Http/Middleware/AuthenticateReseller.php` |
| Laporan admin | `app/Http/Controllers/Admin/ResellerController.php`, `resources/views/admin/reseller/{index,_rows,_footer}.blade.php` |
| Harga & key | `app/Models/Product.php::resellerPrice()`, `app/Support/ResellerKey.php`, `app/Support/OrderNumber.php`, `config/reseller.php` |
| RBAC & menu | `app/Support/Rbac.php` (permission `resellers.view`), `resources/views/partials/admin-sidebar.blade.php` |
| Migrasi | `2026_10_20_190443_add_reseller_fields_to_users_table.php`, `2026_10_20_190444_add_source_to_orders_table.php`, `2026_11_01_173745_add_h2h_parity_fields_to_orders_and_users_table.php` |
| Dokumen | `docs/RESELLER-API.md` (dev), halaman in-app docs (untuk reseller) |

**Konfigurasi:** env `RESELLER_DISCOUNT_PERCENT` (default `5`), rate limit 120 req/menit/key.

**Batas dukungan H2H:** hanya produk prabayar dengan fulfillment Digiflazz yang dapat ditampilkan/dipesan. Pascabayar dan provider lain belum didukung. Key H2H lama tidak otomatis berlaku; reseller harus membuat API key baru di panel.

**Verifikasi terbaru:** Pint ✅ · view cache ✅ · test reseller terfokus **56 tests, 301 assertions, 0 gagal** ✅ · `composer validate --strict` ✅ · `composer audit --locked` bersih. Advisory medium `symfony/mime` ditangani dengan patch lock-only **7.4.19 → 7.4.22**. Playwright mengirim POST ke route H2H tanpa API key dan menerima JSON `401` yang diharapkan (route tidak tertahan CSRF).

**Full suite:** **545 tests; 537 lulus, 8 gagal**. Kegagalan sama dengan baseline: dua test banner admin, pagination admin, tampilan logo brand, mismatch operator checkout, dua test halaman fitur disabled, dan halaman brand. Tidak ada kegagalan baru dari reseller/webhook; delapan test baseline tersebut tetap perlu ditriase sebelum gate rilis aplikasi dinyatakan hijau.

**Status environment yang diperiksa:** konfigurasi aktif mesin ini masih `APP_ENV=local`, `APP_DEBUG=true`, database MySQL lokal, queue database. Migrasi H2H tercatat jalan pada database lokal saja; ini tidak membuktikan schema produksi sudah diperbarui.

### Yang perlu disiapkan pemilik aplikasi sebelum go-live

- Domain produksi + HTTPS valid; isi `APP_URL`, set `APP_ENV=production` dan `APP_DEBUG=false`, lalu build/refresh config cache sesuai proses deploy.
- Kredensial dan saldo akun Digiflazz **produksi**, serta daftar produk/kode prabayar yang sudah diverifikasi. Uji transaksi live secara kecil dan terkontrol.
- Database produksi, backup teruji, serta jendela deploy untuk menjalankan `php artisan migrate --force`; verifikasi migration status dan permission admin setelahnya.
- Supervisor/container yang menjaga `php artisan queue:work` tetap hidup, dengan timeout worker di bawah `retry_after`; sediakan monitoring dan pemeriksaan `failed_jobs`.
- Kredensial Midtrans/metode top-up yang benar jika saldo reseller diisi melalui pembayaran online.
- Jika webhook dipakai: endpoint reseller HTTPS port 443, ekstensi PHP cURL, egress langsung ke IP publik tujuan (proxy keluar dinonaktifkan untuk request webhook), serta idempotensi berdasarkan `X-ATL-Delivery`.
- Buat API key reseller baru setelah approval; key H2H CI lama tidak dimigrasikan. Simpan key/secret di secret manager, bukan chat atau repository.
- Cocokkan payload dengan klien integrasi lama yang disamarkan dan luluskan skenario staging: sukses, gagal/refund, timeout/retry, stok/saldo kurang, reff_id berulang, webhook, dan restart worker.

**Catatan:**
- Discount dibatasi `max(cost_price, …)` — produk bermarkup 0 tidak menghasilkan diskon (harga reseller = harga jual), by design agar tidak jual di bawah modal.
- Menonaktifkan reseller tidak menghapus `reseller_since` (riwayat tetap ada untuk laporan); key dicabut.
- Order API `source = 'api'`; order web tetap `'web'` — dipakai perhitungan kolom "Via API"/"Nominal API" di laporan.
- Webhook membutuhkan queue worker, ekstensi PHP cURL, dan egress HTTPS port 443. DNS alamat publik dipin per percobaan; redirect dan host/IP internal ditolak.
