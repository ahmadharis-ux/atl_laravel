# Rekap Fitur Atlantic Pedia Tab — Dari Awal Sampai Akhir

Dokumen ini merangkum seluruh fitur yang dikerjakan pada aplikasi Laravel ini,
dari yang pertama dikerjakan sampai yang terakhir, termasuk keputusan desain,
file penting, dan cara verifikasinya.

---

## 1. Pembayaran VA / E-Wallet untuk Produk (Checkout)

**Masalah:** Checkout produk sebelumnya hanya mendukung metode transfer manual.
Ditambahkan pembayaran via Virtual Account (VA) dan E-Wallet.

**Yang dikerjakan:**

- Migrasi `orders`: kolom `master_bank_id` (FK ke `master_banks`) dan `fee`.
- `Order::isPending()`, `Order::totalDue()` (amount + fee), relasi `masterBank()`.
- `CheckoutController`:
  - Target pembayaran memakai `MasterBank` aktif (VA/E-Wallet),
  - `resolveMasterBank()` menentukan bank sesuai metode yang dipilih user,
  - Fee dan `expires_at` diisi untuk semua metode belum-lunas,
  - Notifikasi admin saat order baru dibuat.
- Halaman checkout & detail order user: instruksi transfer + tombol copy
  (nomor VA, atas nama, nominal), `resources/views/user/checkout/show.blade.php`
  dan `resources/views/user/orders/show.blade.php`.
- Route `orders.pay` / `deposits.pay` (bayar via payment gateway) hanya
  di non-production (`middleware('pay.gate')` di `routes/web.php`).

**Trade-off / catatan:**

- Stok di-lock dan dipesan saat checkout untuk SEMUA metode (termasuk VA/QRIS
  yang belum bayar) → stok ditahan maksimal 3 jam sampai order expire.
- `orders:expire-pending` masih memakai `created_at + pending_ttl_minutes (180)`
  yang sama dengan `expires_at` (3 jam), jadi ekuivalen.

---

## 2. Admin: Tandai Lunas & Batalkan Order

**Yang dikerjakan:**

- `TransactionController::settle()` (line ~95) dan `expire()` (line ~111).
- Routes: `admin.transactions.settle|expire` (`routes/admin.php`),
  middleware `permission:transactions.update`.
- Tombol di `admin/transaction/show.blade.php`:
  - **Tandai Lunas** (hidden form POST ke settle),
  - **Batalkan** (confirm dulu, POST ke expire — mengembalikan stok).
- Row action di `admin/transaction/_rows.blade.php`:
  hanya tampil bila `isPending()` dan user punya `transactions.update`.

---

## 3. Auto-Expire Deposit & Order

**Yang dikerjakan:**

- `app/Console/Commands/ExpirePendingDeposits.php`
  → command `deposits:expire-pending`.
- Dijadwalkan tiap 10 menit di `routes/console.php`:

```php
Schedule::command('digiflazz:reconcile-orders')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('orders:expire-pending')->everyTenMinutes()->withoutOverlapping();
Schedule::command('deposits:expire-pending')->everyTenMinutes()->withoutOverlapping();
```

- Terverifikasi live: deposit pending yang lewat `expires_at` menjadi `expired`.

---

## 4. QRIS Download & Cek Status (Sync)

**Yang dikerjakan:**

- Download QRIS via server-side proxy (bukan hotlink eksternal):
  - `GET /saldo/topup/{deposit}/qris` → `deposits.qris` (`downloadQris`).
  - `GET /transaksi/{order}/qris` → `orders.qris`.
- Cek status manual oleh user (POST, throttle 10/menit):
  - `POST /saldo/topup/{deposit}/cek-status` → `deposits.sync`.
  - `POST /transaksi/{order}/cek-status` → `orders.sync`.

---

## 5. Admin Topup: Index = Detail

**Yang dikerjakan:**

- Halaman index topup admin menampilkan konfirmasi pembayaran yang sama
  lengkap dengan halaman detail (parity), commit `9b91a93`.

---

## 6. Push Notification Permission Banner

**Yang dikerjakan:**

- Banner izin push notification di `public/assets/js/push.js`,
  flag penyimpanan di `localStorage['atl_push_prompt']`
  (hanya muncul sekali, tidak mengganggu user yang sudah menolak).

**Gap yang diketahui (belum dikerjakan):**

- Preferensi notifikasi per-event,
- `EwalletTokenizationService` masih stub,
- Queue untuk pengiriman notification,
- Otomasi cron `schedule:run` di production.

---

## 7. Sistem Poin & Tier (Loyalitas)

**Keputusan desain (dipilih user):**

- Poin tetap: **+1 poin per order sukses** dan **+1 poin per topup sukses**
  (tidak ada ledger di awal — hanya kolom `users.points`).
- 3 tier berdasarkan akumulasi poin:

| Tier  | Threshold (default) | Env                   |
|-------|---------------------|-----------------------|
| Silver | 0+ (default)      | —                     |
| Gold   | ≥ 100              | `LOYALTY_GOLD_POINTS` |
| Platinum | ≥ 500            | `LOYALTY_PLATINUM_POINTS` |

- Tier = **perubahan warna UI** (bukan benefit transaksi):
  - Base tema normal dipertahankan.
  - Silver = slate/abu, Gold = token gold (`--gold`/`--goldt`),
    Platinum = teal-ice (brand teal `#9ff0f7`/`#0b6f76`).

**Yang dikerjakan:**

- `app/Enums/UserTier.php`:
  `fromPoints()`, `label()`, `icon()` (star/workspace_premium/diamond),
  `cssClass()` → `tier-<value>`, `nextThreshold()`, `isMax()`.
- `config/loyalty.php` (fallback default threshold).
- CSS di `public/assets/css/atlantic.css`:
  - `.wallet.tier-silver|tier-gold|tier-platinum` (gradien kartu wallet),
  - `.pill.tier-*`, `.mini-poin.tier-silver|tier-platinum`.
- Tier badge (pill silver/gold/platinum) ditampilkan **hanya di topbar**
  (`partials/topbar.blade.php`, desktop + mobile) — berisi icon + label tier
  dan link ke halaman saldo; tooltip menampilkan jumlah poin.
  Gradien kartu (`.wallet.tier-*`, `.mini-poin.tier-*`) tetap dipakai di
  dashboard & saldo sebagai warna kartu, bukan badge.
- `LoyaltySetting::current()` di-cache 1 jam (`loyalty_setting.current`);
  flush otomatis saat admin update threshold.
- 3 user contoh sudah dibuat & email diverifikasi:
  - `silver@test.com` (50 poin), `gold@test.com` (150), `platinum@test.com` (600),
  - semua password `password`.

---

## 8. Merchandise & Penukaran Poin

**Scope (dipilih user):** "Tukar poin → merchandise" — katalog penuh + alur
redeem (admin CRUD, stok, fulfillment manual), bukan sekadar display.

### Database

- `database/migrations/..._create_merchandises_table.php`
  → tabel **`merchandise`** (uncountable! model set `protected $table`):
  `name, description, points_cost, stock, image, is_active`.
  > Pitfall: `constrained()` default menjadi `merchandises` (tidak ada) —
  > harus `constrained('merchandise')`.
- `..._create_merchandise_redemptions_table.php`
  → `merchandise_redemptions`: user_id, `merchandise_id`
  (FK `constrained('merchandise')`), `points_cost` (snapshot), `status`,
  `notes`.

### Enums, Model, Support

- `App\Enums\MerchandiseRedemptionStatus`: `Pending/Completed/Cancelled`
  (+`label()`, `icon()`, `tone()`).
- Model `Merchandise` (`scopeActive()`, `imageUrl()`) dan
  `MerchandiseRedemption` (cast status enum, relasi `user()`, `merchandise()`).
- Factories: `MerchandiseFactory` (`outOfStock()`, `inactive()`),
  `MerchandiseRedemptionFactory` (`completed()`, `cancelled()`).
- `app/Support/MerchandiseImageStorage.php` → WebP 800×800,
  disk dari `config/merchandise.php`.
  - **Fix penting:** path awal `public/merchandise` MENABRAK route
    `/merchandise` (folder fisik → 404). Dipindah ke
    **`public/uploads/merchandise`** (root + url di
    `config/filesystems.php` disk `merchandise`).
- `app/Support/MerchandiseRedeemer.php` — alur atomik:
  - `redeem()`: lock baris user + merchandise (`lockForUpdate`) dalam 1
    transaksi, cek `is_active`/`stock`/poin → potong poin, kurangi stok,
    buat redemption `Pending`.
    Pesan error: "Merchandise tidak tersedia." / "Stok merchandise habis." /
    "Poin kamu tidak mencukupi. Butuh {cost} poin."
  - `complete()`: hanya dari `Pending` → `Completed` (poin tidak berubah).
  - `cancel()`: hanya dari `Pending` → refund poin + stok kembali.

### Routes

**User** (`routes/web.php`, group `auth|non-admin|verified`):

```php
GET  /merchandise                    → merchandise.index
POST /merchandise/{merchandise}/tukar → merchandise.redeem
```

**Admin** (`routes/admin.php`):

```php
GET    admin/merchandise                      (permission:merchandise.view)
POST   admin/merchandise                      (permission:merchandise.update)
PUT    admin/merchandise/{merchandise}        (permission:merchandise.update)
DELETE admin/merchandise/{merchandise}        (permission:merchandise.update)
GET    admin/redemptions                      (permission:redemptions.view)
POST   admin/redemptions/{redemption}/complete (permission:redemptions.update)
POST   admin/redemptions/{redemption}/cancel   (permission:redemptions.update)
```

### RBAC

`app/Support/Rbac.php` — permission baru:

- `merchandise.view`, `merchandise.update`
- `redemptions.view`, `redemptions.update`

Default: ADMIN+STAFF → keempatnya; CS → hanya `.view`.
Seeder (`DatabaseSeeder`) iterasi `Rbac::all()` → jalankan ulang:

```bash
php artisan db:seed --class=DatabaseSeeder --force
```

### Views

- **Admin katalog** (`resources/views/admin/merchandise/`):
  `index.blade.php` + `_rows` + `_footer` + `_modals`
  — pola modal CRUD ajax seperti dashboard-banner
  (`x-ui.data-table`, `x-ui.actions`, `data-confirm`).
- **Admin penukaran** (`resources/views/admin/redemption/`):
  daftar + filter status + row action **Tandai Selesai** / **Batalkan**
  (hidden form + `confirm_danger` untuk batalkan).
- **User merchandise** (`resources/views/user/merchandise/index.blade.php`):
  kartu poin + tier, grid katalog (gambar/biaya/stok/tombol Tukar),
  **Riwayat Poin**, **Riwayat Penukaran**.
- **Sidebar:**
  - User: menu Merchandise **DIHAPUS** — navigasi ada di
    **Pengaturan → kartu "Merchandise"** (`user/settings/index.blade.php`).
  - Admin: grup menu **Merchandise** berisi
    Katalog / Penukaran / Pengaturan Tier (`partials/admin-sidebar.blade.php`).

---

## 9. Pengaturan Tier dari Admin

**Yang dikerjakan:**

- Tabel `loyalty_settings` (singleton): `gold_points`, `platinum_points`.
- Model `LoyaltySetting::current()` — auto-create dari config bila kosong.
- `UserTier::thresholds()` membaca DB, **fallback** `config/loyalty.php`
  bila tabel belum ada; validasi `platinum > gold`.
- Admin controller `LoyaltySettingController` (index + update),
  validasi: minimal 1, Platinum harus > Gold.
- Routes `admin/loyalty-settings` GET/PUT,
  permission **`loyalty.view` / `loyalty.update`**
  (Admin+Staff keduanya, CS hanya view) — sudah ter-seed.
- View `resources/views/admin/loyalty-setting/index.blade.php`:
  form threshold + preview range tiap tier.
- Sidebar admin: Merchandise → **Pengaturan Tier**.

---

## 10. Ledger Riwayat Poin

**Yang dikerjakan:**

- Tabel `point_transactions`:
  `user_id, type, points_change, points_after, reference (morph),
  description`.
- `App\Enums\PointTransactionType`:
  `OrderReward`, `TopupReward`, `MerchandiseRedeemed`, `MerchandiseRefund`
  (+`label()`, `icon()`).
- `User::changePoints($delta, $type, $description, $reference)`:
  update poin + tulis ledger dalam satu langkah.
- Dipanggil dari:
  - `User::addPoint()` → `OrderReward` (order sukses,
    `OrderPaymentSettler` + `OrderFulfillment`),
  - `DepositSettler` → `TopupReward` (referensi deposit),
  - `MerchandiseRedeemer::redeem()` → `MerchandiseRedeemed`,
  - `MerchandiseRedeemer::cancel()` → `MerchandiseRefund`.
- Tampil di user: kartu **"Riwayat Poin"** di halaman merchandise
  (delta ±, saldo setelah, deskripsi, waktu).

---

## 11. Landing Page Publik + CMS

**Tujuan:** halaman depan publik yang bisa diubah admin tanpa deploy, sekaligus jalur masuk pendaftaran.

- Route `/` kini landing page publik (`LandingController`); dashboard user pindah ke `/dashboard`. User yang sudah login diarahkan otomatis ke dashboard sesuai perannya.
- Tabel: `landing_settings` (singleton), `landing_services`, `landing_testimonials`, `landing_faqs`.
- Model menyediakan `scopeActive`; `LandingSetting::current()` membuat baris default bila belum ada.
- CMS admin: **Landing Page** → Pengaturan / Layanan / Testimoni / FAQ (`routes/admin.php`, `Admin\Landing\*`, `resources/views/admin/landing/*`).
- Izin baru: `landing.view`, `landing.update` (ADMIN & STAFF). `LandingSeeder` mengisi konten dummy (aman dijalankan berulang) dan dipanggil dari `DatabaseSeeder`.
- Layout publik `layouts/landing.blade.php`, tampilan `landing/index.blade.php`, CSS `.landing-*` di `public/assets/css/components.css`.

## 12. Tiket & Aduan + Bantuan WhatsApp

- Tabel `tickets` (kode `TKT-YYYYMMDD-XXXXXX`, kategori aduan/saran/supplier, status, `closed_at`) dan `ticket_replies`.
- Enum `TicketCategory` / `TicketStatus`; helper `TicketNumber`.
- User: `/bantuan` (index, buat, detail, balas). Admin: `/admin/tickets` (filter status/jenis, balas, ubah status) dengan izin `tickets.view` / `tickets.update`.
- Notifikasi database: tiket baru → admin dengan `tickets.view`; balasan/perubahan status → pemilik tiket.
- Tombol bantuan WhatsApp otomatis muncul di detail pesanan/top up bermasalah (`failed`/`expired`) dengan detail terisi: `app/Support/SupportWhatsapp.php`, `x-ui.whatsapp-help`. Nomor diambil dari `SUPPORT_WHATSAPP_NUMBER` atau nomor pada pengaturan landing page. Bila kosong, tombol disembunyikan.

## Perintah Verifikasi / Kualitas

```bash
php artisan test --compact          # 371 tests, 1244 assertions (hijau)
vendor/bin/pint --dirty --format agent   # formatting
php artisan view:clear && php artisan view:cache   # cache view
php artisan route:list --name=merchandise           # cek route
php artisan db:seed --class=DatabaseSeeder --force  # sync permission
php artisan config:clear                            # JANGAN config:cache saat test
```

### Test yang relevan

| File | Cakupan |
|------|---------|
| `CheckoutTest` | VA/E-Wallet checkout + master bank mismatch |
| `AdminTransactionTest` | settle/expire + permission |
| `ExpirePendingDepositsTest` | 4 skenario expire deposit |
| `UserTierTest` | threshold Silver/Gold/Platinum + cssClass |
| `UserMerchandiseTest` | redeem sukses / poin kurang / stok habis / inactive / cancel refund |
| `AdminMerchandiseTest` | CRUD + upload gambar + permission |
| `AdminMerchandiseRedemptionTest` | complete/cancel + refund + permission |
| `AdminLoyaltySettingTest` | update threshold + tier berubah + validasi |
| `PointLedgerTest` | setiap perubahan poin tercatat di ledger |

---

## Bug yang Pernah Ditemui & Cara Fix-nya

1. **404 `/merchandise`** — folder `public/merchandise` (penyimpanan gambar)
   menabrak route. Fix: pindah ke `public/uploads/merchandise` +
   update disk `merchandise` di `config/filesystems.php`.
2. **Migration FK salah nama** — `constrained()` plural → `merchandises`
   (tabel tak ada). Fix: `constrained('merchandise')`.
3. **CSRF 419 di test** — `config:cache` meng-cache `app.env=local`
   sehingga `runningUnitTests()` false. Fix: `php artisan config:clear`
   (jangan cache config saat mengembangkan).
4. **Test 403** — helper `admin()` belum memberi `dashboard.view`
   (dipakai `canAccessAdmin()`). Fix: selalu sertakan `dashboard.view`.
5. **Morph reference null** — `'reference' => $model` di `create()` tidak
   mengisi morph. Fix: set `reference_type` + `reference_id` manual.

---

## Environment / Akses Cepat

- **Admin:** `admin@atlanticpedia.com` / `password`
  (atau akun seeder `admin@atlantic.test` / `admin123`)
- **User contoh tier:**
  - `silver@test.com` (50 poin, Silver)
  - `gold@test.com` (150 poin, Gold)
  - `platinum@test.com` (600 poin, Platinum)
  - password semua: `password` — email sudah diverifikasi.
- Production DB: MySQL (row lock `lockForUpdate()` efektif).
