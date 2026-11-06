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
* **IP Asli Tidak Terbaca (Sesi Tercatat 127.0.0.1)** `TrustProxies`
  * **Masalah:** Semua sesi login tercatat dari IP `127.0.0.1` karena aplikasi tidak membaca IP proxy Cloudflare. Akibatnya rate limit IP dan log keamanan tidak akurat.
  * **Solusi:** Konfigurasi `TrustProxies` di Laravel dengan `$proxies = '*'` (atau range IP Cloudflare) dan pastikan header `X-Forwarded-For` diproses.
* **✅ Selesai — User Enumeration di "Lupa Password"** `/forgot-password`
  * **Masalah:** Email tidak terdaftar dibalas "*Email tidak terdaftar di sistem*", memudahkan penyerang memetakan akun terdaftar.
  * **Solusi:** Validasi dan respon dibuat netral seragam: "*Jika email terdaftar, tautan reset kata sandi telah dikirim.*"

### 3. Urgensi Low
* **Bocor Versi Teknologi** `x-powered-by`
  * **Masalah:** Header `x-powered-by: PHP/8.4.27` membocorkan versi spesifik PHP.
  * **Solusi:** Matikan `expose_php = Off` pada file `php.ini`.

### 4. Urgensi UX / Content
* **Perbaikan Copywriting & Metadata**
  * Ubah judul tab yang janggal (*"Masuk - Atlantic Pedia Tab"*).
  * Ganti email support placeholder (`support@atlanticpedia.test`) dengan domain aktif.
  * Perbarui data statistik & testimoni placeholder dengan data aktual agar tidak menyesatkan.
  * Tambahkan tag Open Graph agar link preview di WhatsApp/Sosmed tampil rapi.

---

## 🟡 KATEGORI 2: PERBAIKAN SEDANG (Moderate Effort)
*Perbaikan yang membutuhkan penyesuaian logika aplikasi, konfigurasi web server/Cloudflare, atau pengkodean ringan (Estimasi: 1 - 3 jam per item).*

### 1. Urgensi Medium
* **HTTPS Tidak Dipaksa & Cookie Sesi Tanpa Flag Secure** `http → https`
  * **Masalah:** Akses HTTP tidak di-redirect otomatis ke HTTPS, redirect internal mengarah ke HTTP, dan cookie sesi tidak menggunakan flag `Secure`.
  * **Solusi:** Set `SESSION_SECURE_COOKIE=true`, `APP_URL=https://...`, aktifkan `URL::forceScheme('https')`, serta aktifkan fitur *"Always Use HTTPS"* pada Cloudflare.
* **Ganti Email Tanpa Konfirmasi Password** `/pengaturan/profil`
  * **Masalah:** Mengubah email profil langsung mereset email login dan verifikasi tanpa meminta konfirmasi password saat ini.
  * **Solusi:** Wajibkan konfirmasi password/2FA sebelum ganti email. Kirim verifikasi ke email baru dengan tetap mempertahankan email lama aktif sampai proses verifikasi selesai, dan beri notifikasi peringatan ke email lama.
* **Security Headers Belum Diterapkan** `Response Headers`
  * **Masalah:** Tidak ada header keamanan standar seperti HSTS, CSP, `X-Frame-Options`, `X-Content-Type-Options`, dan `Referrer-Policy`.
  * **Solusi:** Tambahkan middleware global pada Laravel atau atur Transform Rules di Cloudflare (Minimal: HSTS, `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`).

### 2. Urgensi Low
* **CDN Tanpa Subresource Integrity (SRI)** `Bootstrap CDN`
  * **Masalah:** Script Bootstrap dari jsDelivr dimuat tanpa atribut `integrity`.
  * **Solusi:** Tambahkan hash SRI pada tag `<script>` untuk mencegah potensi kompromi CDN.

### 3. Urgensi UX / Layout
* **Perbaikan Layout & Alur Pengguna**
  * **Scroll Horizontal Mobile:** Pada layar 375px muncul scroll samping akibat `.row.g-5`. Ubah class grid menjadi `g-3` atau `g-4`.
  * **Akses Katalog Produk:** Tombol *"Lihat Produk"* di beranda mengarah ke `/login`. Buat halaman produk/katalog publik agar pengunjung belum login bisa melihat harga.
  * **Navigasi Footer:** Link footer hanya mengarah ke `#layanan` alih-alih halaman produk. Perbesar target sentuh tombol (minimal height ~44px untuk mobile).
  * **Legalitas:** Buat halaman *Kebijakan Privasi* dan *Syarat & Ketentuan* yang valid (diperlukan untuk aplikasi transaksi/pembayaran).

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
* **Optimasi Asset & CSS Inlining**
  * **Masalah:** CSS Bootstrap (~230KB) di-inline langsung pada HTML halaman login/register sehingga total ukuran halaman ~250KB dan tidak bisa di-cache browser. Favicon logo tampak gepeng pada 16/32px.
  * **Solusi:** Pisahkan file CSS menjadi asset eksternal agar memanfaatkan *browser caching*. Buat varian favicon khusus berukuran kecil (icon-only 16x16 / 32x32).

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
