LAPORAN

BERFUNGSI MENAMPILKAN SEMUA DATA YANG ADA PADA APLIKASI
1. LAPORAN TENTANG TOTAL TRANSAKSI DAN LABA
2. LAPORAN PRODUCT YANG PALING LAKU DAN YANG PALING TIDAK LAKU
3. LAPORAN PROVIDER YANG SERING GANGGUAN
4. LAPORAN PROVIDER YANG SELALU AMAN TIDAK PERNAH GANGGUAN
5. LAPORAN USER DARI SEGI AKTIFITAS SEMUA TRANSAKSI


DARI LAPORAN DI ATAS TOLONG TAMBAHKAN LAPORAN YANG PERLU DIBUAT UNTUK KEPUTUSAN BUSINESS KEDEPANNYA, AGAR BISA DI ADAPTASI DI ANALYSIS SWOT DAN JUGA BERFUNGSI UNTUK CRM
LIST YANG OPENCODE TAMBAHAKAN DISINI SILAHKAN TAMBAHKAN DAN CEKLIS KALO SUDAH DI IMPLEMENTASI.
TIDAK PERLU MEMINTA PERSETUJUAN SAYA BUATKAN SAJA NANTI HASILNYA BUAT DISINI SAYA AKAN BACA.
BEST PRACTICE LAPORAN SESUAI TUJUAN DIATAS
SEMUA LAPORAN TERSEBUT BISA DI IMPORT JADI EXCEL DAN PDF

---

## CHECKLIST (sudah diimplementasi)

### 5 Laporan wajib
- [x] 1. Total transaksi & laba — halaman **Ringkasan** (`/admin/reports/overview`): kartu Total Transaksi, Pendapatan, Laba Kotor (Pendapatan − HPP), Rata-rata Transaksi, Tingkat Sukses, Perlu Tindak Lanjut; tabel Tren Harian (pendapatan + laba per hari).
- [x] 2. Produk paling laku & tidak laku — halaman **Produk** (`/admin/reports/products`): Top 10 laku (sukses terbanyak) dan "Paling Tidak Laku" = produk AKTIF dengan penjualan tersedikit hingga nol (kandidat review harga/bundling/henti).
- [x] 3. Provider sering gangguan — halaman **Provider** (`/admin/reports/providers`): tabel "Provider Sering Gangguan" diurutkan tingkat gagal (Gagal % = gagal / (sukses+gagal)) + jumlah kegagalan + insiden terakhir.
- [x] 4. Provider selalu aman — halaman **Provider**, bagian "Provider Selalu Aman": tingkat gagal 0% dengan minimal ada pesanan sukses (kandidat perluasan beban).
- [x] 5. Aktivitas transaksi user/pelanggan — halaman **Pelanggan** (`/admin/reports/customers`): total/sukses/gagal/belanja/rata-rata/transaksi terakhir per pelanggan + filter segmen & pencarian.

### Tambahan (untuk keputusan bisnis / SWOT / CRM)
- [x] 6. Tren harian pendapatan & laba — tabel per hari + grafik garis 30 hari (transaksi & pendapatan) di halaman Ringkasan. *SWOT: S/O — pola pertumbuhan.*
- [x] 7. Campuran metode pembayaran — "Metode Pembayaran" di Ringkasan. *SWOT: O — kanal yang dominan.*
- [x] 8. Komposisi status pesanan + backlog — "Status Pesanan" & kartu Perlu Tindak Lanjut (pending/diproses). *SWOT: W — bottleneck operasional.*
- [x] 9. Analisis hari & jam sibuk — "Transaksi per Hari dalam Seminggu" + "Jam Sibuk" (8 jam tersibuk). *SWOT: O — jadwal promo/push notification.*
- [x] 10. Kontribusi brand & kategori — bagian "Kontribusi Brand" dan "Kontribusi Kategori" (nilai jual + pangsa %) di halaman Produk. *SWOT: S/O — fokus portofolio.*
- [x] 11. Top spender — "Top Spender (Sepanjang Masa)" di halaman Pelanggan. *CRM: program loyalitas & upsell.*
- [x] 12. Segmen retensi pelanggan (CRM) — kartu & filter segmen **Baru / Aktif / Berisiko Churn / Tidak Aktif** (aktif ≤30 hari; churn = lama 31–90 hari & ≥2 sukses; selebihnya tidak aktif) + tabel "Aktivitas Transaksi Pelanggan". *CRM: win-back & campaign.*

### Ekspor (wajib Excel + PDF untuk SEMUA laporan)
- [x] Excel (.xlsx) — tombol **Excel** di tiap halaman laporan → multi-sheet (sheet Ringkasan + 1 sheet per tabel), nilai uang/persen numerik dengan format sel.
- [x] PDF — tombol **PDF** di tiap halaman laporan → A4 multi-halaman, header tabel berulang, footer "Halaman N / M" (tanpa library eksternal; writer PDF minimal).
- [x] Filter periode (from/to) berlaku sama persis untuk layar maupun ekspor (default 30 hari terakhir; halaman Pelanggan = seluruh waktu/CRM).

---

## RESUME (untuk dilanjutkan agent berikutnya)

**Status: SELESAI — semua gate hijau.**

- **Akses**: route `admin.reports.*` dilindungi `permission:reports.view` (grup RBAC baru **Laporan**, default: ADMIN, STAFF, CS). Jalankan `php artisan db:seed` setelah pull.
- **Struktur**:
  - Builder laporan: `app/Support/Report/{OverviewReport, ProductReport, ProviderReport, CustomerReport}.php` — kontrak `['cards' => [...], 'sections' => [title, note, columns[[label,align,type]], rows[]], 'chart'?]`.
  - Support: `ReportPeriod.php` (filter tanggal), `ReportFormatter.php` (tampilan teks), `ExcelReport.php` (PhpSpreadsheet 5 multi-sheet), `PdfReport.php` (writer PDF manual Courier), `format type`: `text|int|currency|percent`.
  - Controller: `app/Http/Controllers/Admin/Report/` — `OverviewController`, `ProductReportController`, `ProviderReportController`, `CustomerReportController`, `ExportController` (`GET /admin/reports/{section}/export?format=xlsx|pdf`, section di-wHEREIn).
  - Route: `routes/admin.php` → grup `reports`, `Route::redirect('/', …overview)`.
  - View: `resources/views/admin/report/{overview,products,providers,customers}.blade.php` + partial `_filter.blade.php` (tanggal+export) dan `_section.blade.php` (render tabel dari sections memakai `ReportFormatter`). Sidebar: grup **Laporan** = Ringkasan/Produk/Provider/Pelanggan (`reports.view`) + Reseller (`resellers.view`).
  - Test: `tests/Feature/AdminReportTest.php` (11 test: akses 403/tamu, kalkulasi laba, filter periode, top/bottom produk, ranking provider, segmen CRM, ekspor xlsx=ZIP magic `PK`, pdf=`%PDF-1.`+`%%EOF`, validasi format/section).
- **Gate terakhir**: `vendor/bin/pint --dirty --format agent` ✅ · `php artisan view:clear && php artisan view:cache` ✅ · `php artisan test --compact` → **418/418, 1439 assertions** ✅.
- **Catatan teknis penting**: `$request->string('x') === 'y'` selalu false (pakai `->toString()`); `0.0 === 0` false (pakai `<= 0`); PhpSpreadsheet 5 tanpa `getCellByColumnAndRow`/`Worksheet::stringFromColumnIndex` (pakai `Cell\Coordinate::stringFromColumnIndex`); tes sqlite — jangan SQL MySQL-only; `canAccessAdmin()` butuh `dashboard.view` di helper test.
- **Belum ada/tidak dibuat**: ekspor per-pelanggan individual, PDF ber-logo/branding, scheduled email report (di luar brief; minta jika perlu).
