# 🚀 Matriks Prioritas Perbaikan

```
=============================================================================================
URGENSI / TINGKAT KESULITAN | TERMUDAH (Easy Fix) | SEDANG (Moderate) | SULIT / BUTUH WAKTU
=============================================================================================
HIGH                        | Throttle Login      | -                 | -
---------------------------------------------------------------------------------------------
MEDIUM                      | - TrustProxies / IP | - Force HTTPS     | - Cloudflare Access
                            | - User Enumeration  | - Konfirmasi Ganti|   (Staging Lock)
                            |                     |   Email           |
                            |                     | - Security Headers|
---------------------------------------------------------------------------------------------
LOW                         | - Off X-Powered-By  | - SRI Bootstrap   | - Restrukturisasi ID 
                            | - Minor UX & Copy   | - Optimization    |   (UUID / Uniform 404)
                            |                     |   CSS & Layout    |
=============================================================================================
```

---

# 📋 Laporan UAT & Security Review
**Lingkungan:** Staging — Laravel, PHP 8.4 (Cloudflare)  
**Tanggal:** 6 November 2026  
**Metode:** Black-box (Uji Aktif Disetujui)

---

## 🟢 KATEGORI 1: PERBAIKAN TERMUDAH (Quick Wins)
*Perbaikan yang memerlukan perubahan konfigurasi sederhana atau beberapa baris kode (Estimasi: < 30 menit per item).*

### 1. Urgensi High
* **✅ Selesai — Login Tanpa Pembatasan Percobaan (Brute-Force)** `/login`
  * **Masalah:** 19+ percobaan password salah dari IP yang sama tidak pernah diblokir/diperlambat.
  * **Solusi:** Rate limiter Laravel diterapkan pada route login, dibatasi 5 percobaan/menit per email dan 20 percobaan/menit per IP. CAPTCHA belum ditambahkan.

### 2. Urgensi Medium
* **✅ Selesai — IP Asli Tidak Terbaca (Sesi Tercatat 127.0.0.1)** `TrustProxies`
  * **Masalah:** Semua sesi login tercatat dari IP `127.0.0.1` karena aplikasi tidak membaca IP proxy Cloudflare. Akibatnya rate limit IP dan log keamanan tidak akurat.
  * **Solusi:** Middleware `TrustCloudflareProxies` menggunakan range IP Cloudflare dan memproses `CF-Connecting-IP` ke `X-Forwarded-For`.
* **✅ Selesai — User Enumeration di "Lupa Password"** `/forgot-password`
  * **Masalah:** Email tidak terdaftar dibalas "*Email tidak terdaftar di sistem*", memudahkan penyerang memetakan akun terdaftar.
  * **Solusi:** Validasi dan respon dibuat netral seragam: "*Jika email terdaftar, tautan reset kata sandi telah dikirim.*"

### 3. Urgensi Low
* **✅ Selesai sebagian — Bocor Versi Teknologi** `x-powered-by`
  * **Masalah:** Header `x-powered-by: PHP/8.4.27` membocorkan versi spesifik PHP.
  * **Solusi:** Header `X-Powered-By` dihapus pada Apache melalui `.htaccess`. Pengaturan `expose_php = Off` tetap perlu diterapkan pada runtime PHP/Cloud.

### 4. Urgensi UX / Content
* **✅ Selesai sebagian — Perbaikan Copywriting & Metadata**
  * Judul tab login diubah menjadi *"Masuk - Atlantic Pedia"*.
  * **⏳ Tertunda:** Ganti email support placeholder (`support@atlanticpedia.test`) dengan domain aktif.
  * **⏳ Tertunda:** Perbarui data statistik & testimoni placeholder dengan data aktual agar tidak menyesatkan.
  * Tag Open Graph dan Twitter Card ditambahkan pada layout landing.

---

## 🟡 KATEGORI 2: PERBAIKAN SEDANG (Moderate Effort)
*Perbaikan yang membutuhkan penyesuaian logika aplikasi, konfigurasi web server/Cloudflare, atau pengkodean ringan (Estimasi: 1 - 3 jam per item).*

### 1. Urgensi Medium
* **HTTPS Tidak Dipaksa & Cookie Sesi Tanpa Flag Secure** `http → https`
  * **Masalah:** Akses HTTP tidak di-redirect otomatis ke HTTPS, redirect internal mengarah ke HTTP, dan cookie sesi tidak menggunakan flag `Secure`.
  * **Solusi:** Set `SESSION_SECURE_COOKIE=true`, `APP_URL=https://...`, aktifkan `URL::forceScheme('https')`, serta aktifkan fitur *"Always Use HTTPS"* pada Cloudflare.
* **✅ Selesai — Ganti Email Tanpa Konfirmasi Password** `/pengaturan/profil`
  * **Masalah:** Mengubah email profil langsung mereset email login dan verifikasi tanpa meminta konfirmasi password saat ini.
  * **Solusi:** Konfirmasi kata sandi saat ini diwajibkan sebelum email diganti. Email baru tetap harus diverifikasi sebelum akses terverifikasi dipulihkan.
* **✅ Selesai sebagian — Security Headers Belum Diterapkan** `Response Headers`
  * **Masalah:** Tidak ada header keamanan standar seperti HSTS, CSP, `X-Frame-Options`, `X-Content-Type-Options`, dan `Referrer-Policy`.
  * **Solusi:** Middleware global menambahkan `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, dan HSTS pada request HTTPS. CSP masih perlu disusun setelah audit seluruh asset inline.

### 2. Urgensi Low
* **✅ Selesai — CDN Tanpa Subresource Integrity (SRI)** `Bootstrap CDN`
  * **Masalah:** Script Bootstrap dari jsDelivr dimuat tanpa atribut `integrity`.
  * **Solusi:** Hash SRI dan `crossorigin="anonymous"` ditambahkan pada seluruh Bootstrap CDN CSS/JS yang digunakan aplikasi.

### 3. Urgensi UX / Layout
* **Perbaikan Layout & Alur Pengguna**
   * **✅ Selesai — Scroll Horizontal Mobile:** Grid landing diubah dari `.row.g-5` menjadi `.row.g-3`.
   * **⏳ Akses Katalog Produk:** Tombol *"Lihat Produk"* di beranda mengarah ke `/login`. Buat halaman produk/katalog publik agar pengunjung belum login bisa melihat harga.
   * **✅ Selesai sebagian — Navigasi Footer:** Link legal ditambahkan dan target sentuh link footer diperbesar minimal 44px pada mobile. Link produk masih berbasis section karena katalog publik belum dikerjakan.
   * **✅ Selesai sebagian — Legalitas:** Halaman *Kebijakan Privasi* dan *Syarat & Ketentuan* sudah tersedia dan ditautkan dari footer. Review legal formal dan data badan usaha masih diperlukan.

---

## 🔴 KATEGORI 3: PERBAIKAN SULIT / BUTUH WAKTU (High Effort / Strategic)
*Perbaikan yang membutuhkan arsitektur ulang, proteksi akses staging, atau refactoring kode.*

### 1. Urgensi Medium
* **Staging Terbuka Publik & Bisa Diindeks Search Engine** `robots.txt`
  * **Masalah:** `robots.txt` diset `Allow: /` tanpa otentikasi pintu masuk staging.
  * **Solusi:** Kunci akses staging menggunakan **Cloudflare Access** atau **Basic Authentication** (`htpasswd`), dan tambahkan header HTTP `X-Robots-Tag: noindex`.

### 2. Urgensi Low
* **Bocor Keberadaan ID Data (403 vs 404)** `IDOR-info`
  * **Masalah:** Data milik user lain mengembalikan status `403` sedangkan ID tidak ada mengembalikan `404`, memungkinkan penyerang mendeteksi rentang ID valid.
  * **Solusi:** Seragamkan respon menjadi `404 Not Found` untuk otorisasi gagal, atau migrasikan ID incremental (1, 2, 3) menggunakan **UUID** untuk transaksi & topup.

### 3. Urgensi UX / Performa Frontend
* **✅ Selesai sebagian — Optimasi Asset & CSS Inlining**
  * **Masalah:** CSS Bootstrap (~230KB) di-inline langsung pada HTML halaman login/register sehingga total ukuran halaman ~250KB dan tidak bisa di-cache browser. Favicon logo tampak gepeng pada 16/32px.
   * **Solusi:** Varian favicon icon-only 16x16/32x32 sudah digunakan pada layout utama dan halaman auth. Pemisahan Bootstrap inline pada halaman login/register masih tertunda.

---

## 📊 Progres Penanganan

**58%** berdasarkan 13 item utama: 5 selesai penuh, 5 selesai sebagian (dihitung 50%), dan 3 masih tertunda. Persentase ini akan diperbarui setiap kali item berikutnya ditangani. Poin akses katalog publik sengaja tetap tertunda sesuai arahan.

---

## ✅ Status Modul Aman (Lolos UAT)
Sistem telah memiliki fondasi keamanan dan validasi bisnis yang solid pada area berikut:
* **CSRF Protection:** POST tanpa token valid ditolak (HTTP 419).
* **Proteksi File Sensitif:** File `.env`, `.git`, log, dan backup terkunci (HTTP 404).
* **Environment Production Ready:** Debug mode mati (tidak ada kebocoran stack trace).
* **OAuth Security:** Login Google menggunakan parameter `state` (anti-CSRF).
* **Autentikasi & Otorisasi:** Error login netral, area privat terkunci, serta IDOR dicegah (akses data user lain ditolak).
* **Keamanan Transaksi & Saldo:** Validasi nominal server-side (Min 10rb, Max 10jt), kalkulasi harga server-side, PIN wajib sebelum pemotongan saldo, serta proteksi PIN brute-force (lockout 15 menit setelah 5x gagal).
* **Proteksi Injeksi:** Input di-escape dengan baik (Anti-XSS) dan 2FA via authenticator app berfungsi baik.

---

## 💡 Catatan Pemulihan Akun Test
Akun test yang terkunci akibat pengujian verifikasi email dapat dipulihkan melalui Artisan Tinker:

```bash
php artisan tinker
```
```php
$u = App\Models\User::where('email','user.probe@atlantic.test')->first();
$u->email = 'user@atlantic.test';
$u->email_verified_at = now();
$u->save();
```
