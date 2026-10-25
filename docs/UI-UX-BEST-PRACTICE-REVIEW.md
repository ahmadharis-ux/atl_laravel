# Review UX/UI Best Practice – Reseller, Product Checkout, Payment, dan Notifikasi

## Ringkasan

Berdasarkan penelusuran pada halaman reseller, katalog/detail produk, checkout, API reseller, dan pusat notifikasi, fitur utama sudah tersedia. Dokumen ini membedakan:

- **Temuan**: perilaku yang dapat dilihat langsung dari implementasi.
- **Rekomendasi**: peningkatan yang berpotensi memperjelas pengalaman pengguna.
- **Perlu verifikasi lanjutan**: hal yang memerlukan audit alur/runtime lebih luas sebelum dinyatakan sebagai masalah.

Metode pembelian produk di checkout memang hanya dua:

- Saldo (saldo akun yang diisi melalui deposit/top up)
- QRIS

Metode transfer bank dan e-wallet yang terdapat pada enum atau flow top up tidak ditawarkan sebagai metode checkout produk. Rekomendasi di bawah tidak mengusulkan penambahan metode pembayaran.

### Kesimpulan singkat

**Prioritas utama bukan membangun ulang flow**, melainkan memperjelas informasi dan memperbaiki detail sebelum user melakukan aksi:

1. Tandai field wajib dan format input secara konsisten.
2. Beri nama yang tidak ambigu untuk pembayaran Saldo vs top up/deposit.
3. Buat konfirmasi checkout berisi detail produk, tujuan, nominal, metode, dan dampak pembayaran.
4. Tampilkan rule reseller dan konsekuensi API key dekat dengan aksi terkait.
5. Dokumentasikan kontrak API produk dengan tipe/nullability dan contoh detail/error yang lengkap.

Fitur seperti error per-field, pengecekan saldo di browser, konfirmasi checkout, toast, pusat notifikasi, response JSON dengan pagination, serta dokumentasi contoh API **sudah ada sebagian**. Catatan audit tidak boleh dibaca sebagai klaim bahwa fitur-fitur tersebut sama sekali belum tersedia.

## Referensi utama

- [resources/views/user/settings/reseller.blade.php](../resources/views/user/settings/reseller.blade.php)
- [resources/views/user/checkout/show.blade.php](../resources/views/user/checkout/show.blade.php)
- [app/Http/Controllers/User/CheckoutController.php](../app/Http/Controllers/User/CheckoutController.php)
- [app/Http/Controllers/User/ResellerController.php](../app/Http/Controllers/User/ResellerController.php)
- [app/Http/Controllers/User/ProductController.php](../app/Http/Controllers/User/ProductController.php)
- [app/Http/Controllers/Api/Reseller/ProductController.php](../app/Http/Controllers/Api/Reseller/ProductController.php)
- [app/Http/Controllers/Api/Reseller/OrderController.php](../app/Http/Controllers/Api/Reseller/OrderController.php)
- [routes/api.php](../routes/api.php)
- [resources/views/partials/notifications-list.blade.php](../resources/views/partials/notifications-list.blade.php)
- [app/Notifications/TransactionResult.php](../app/Notifications/TransactionResult.php)
- [public/assets/js/components.js](../public/assets/js/components.js)
- [resources/views/user/settings/reseller-docs.blade.php](../resources/views/user/settings/reseller-docs.blade.php)
- [config/reseller.php](../config/reseller.php)

---

## 1. Validasi Form: wajib vs opsional belum terlalu jelas

### Kondisi saat ini

Validasi server-side untuk target dan zone sudah ada di [app/Http/Controllers/User/CheckoutController.php](../app/Http/Controllers/User/CheckoutController.php). Form checkout ada di [resources/views/user/checkout/show.blade.php](../resources/views/user/checkout/show.blade.php), dan [resources/views/components/ui/input.blade.php](../resources/views/components/ui/input.blade.php) merender error server di dekat field.

### Kesenjangan yang teramati

- Pada checkout, target diwajibkan server dengan panjang 3–64 karakter; zone diwajibkan hanya untuk brand tertentu dan dibatasi maksimal 20 karakter.
- Label target/zone di checkout tidak secara umum menandai wajib dengan `*` atau teks `Wajib`. Zone hanya dirender untuk produk yang membutuhkannya.
- Component input menampilkan error server-side di bawah field. JavaScript checkout juga memeriksa target/zone kosong, memberi toast, memfokuskan field, dan menggulir ke input.
- Untuk alur nickname tertentu, halaman produk memiliki validasi nickname sebelum tombol beli aktif; namun pengalaman field produk lain tidak seragam dan tidak semua format divalidasi di sisi browser.
- Detail batas karakter dan contoh format tidak selalu terlihat di dekat field.

### Rekomendasi

1. Gunakan satu pola label konsisten: `ID Tujuan *` untuk wajib, dan `Zone ID *` hanya ketika produk membutuhkannya. Jangan menambahkan label opsional pada field yang tidak ditampilkan.
2. Tambahkan helper text dengan contoh yang menyesuaikan kategori/brand, bukan contoh generik yang bisa membuat user memasukkan data ke format yang salah.
3. Beri tahu batas yang relevan secara singkat, seperti panjang minimum/maksimum, hanya jika constraint tersebut benar-benar diberlakukan backend.
4. Pertahankan error server-side tepat di bawah field sebagai sumber kebenaran. Validasi browser hanya untuk umpan balik cepat; jangan menjadikannya pengganti validasi server.
5. Untuk target yang sudah lolos pengecekan nickname, pertahankan state/hasilnya sampai user mengubah target atau zone, dan cegah submit bila hasil validasi memang wajib untuk produk itu.

### Kriteria penerimaan

- Pengguna dapat mengetahui field wajib sebelum menekan tombol submit.
- Jika server menolak nilai, error muncul di field yang benar dan nilai input lain tidak hilang.
- Pesan untuk target/zone menyebut tindakan yang perlu dilakukan dan tidak hanya menyebut “input tidak valid”.
- Untuk produk dengan zone, label dan helper menyatakan bahwa zone diperlukan; untuk produk tanpa zone, tidak ada field zone yang membingungkan.
- Validasi browser dan server tidak berbeda dalam hal wajib/tidak wajib dan batas yang ditampilkan.

---

## 2. Response produk / API perlu lebih konsisten untuk developer experience

### Kondisi saat ini

Endpoint daftar dan detail produk reseller tersedia di [app/Http/Controllers/Api/Reseller/ProductController.php](../app/Http/Controllers/Api/Reseller/ProductController.php). Daftar hanya memuat produk aktif dan memiliki filter serta pagination. Satu produk memuat field utama seperti:

- id
- code
- name
- category
- selling_price
- reseller_price
- discount
- stock
- requires_zone

### Yang sudah tersedia dan celah yang tersisa

API katalog produk sudah mengembalikan `data` dan `meta` pagination. Setiap produk memuat `selling_price`, `reseller_price`, `discount`, informasi stok, dan `requires_zone`. Dokumentasi juga sudah memiliki contoh list, contoh pembuatan order, tabel beberapa error HTTP, serta keterangan harga reseller.

Perlu dibedakan dua surface yang sama-sama berkaitan dengan produk:

- **Katalog web pengguna**: [app/Http/Controllers/User/ProductController.php](../app/Http/Controllers/User/ProductController.php) merender halaman/HTML fragment. List umum hanya mengambil produk aktif yang masuk katalog, default 40 per halaman, dan response AJAX berisi `rows`, `tabs`, serta `footer` HTML.
- **Reseller API**: [app/Http/Controllers/Api/Reseller/ProductController.php](../app/Http/Controllers/Api/Reseller/ProductController.php) mengembalikan JSON produk aktif dengan pagination, default 50 produk dan batas maksimal 100 per halaman.

Rekomendasi kontrak JSON di bawah ditujukan untuk API reseller, bukan response HTML internal katalog web. Jika response AJAX katalog dipelihara sebagai kontrak frontend, bentuk partial HTML dan state kosong/error-nya perlu dites terpisah.

Yang sudah diselaraskan pada kontrak produk:

- Contoh list dan detail produk aktif tersedia terpisah, dengan bentuk payload sesuai controller.
- Tipe, nullability, satuan Rupiah, dan makna field didokumentasikan; `payment_type` tidak nullable, sedangkan `brand`, `category`, dan `region` dapat null.
- Contoh validasi `422` menggunakan bentuk JSON aktual beserta penyebabnya.
- Perbedaan response list/detail, metadata pagination, dan arti `stock: null` untuk stok unlimited dinyatakan eksplisit.
- Filter produk dan batas `per_page` 1–100 dengan default 50 didokumentasikan.
- Produk tidak ditemukan atau tidak aktif pada endpoint detail dinyatakan menghasilkan `404`.

### Kontrak yang dipertahankan

- Bentuk response API tidak diubah; dokumentasi mengikuti kontrak yang sudah digunakan client.
- Tabel field menjelaskan tipe JSON, nullability, satuan, dan kapan field dipakai.
- `stock: null` berarti stok tidak terbatas; `discount` adalah selisih nominal harga, bukan persentase atau jaminan laba bersih.
- Contoh response mencakup list, detail, dan validasi `422`.
- Filter, batas/default `per_page`, metadata pagination, serta arti `total` dijelaskan.
- Nilai null pada relasi/region dibedakan dari `requires_zone`, yang selalu boolean.

### Kriteria penerimaan

- Integrator dapat membedakan harga bayar reseller, harga publik, potongan nominal, dan stok tanpa menebak.
- Dokumentasi memuat contoh sukses list, sukses detail, serta error validasi JSON yang sesuai dengan response aktual.
- Nama field dan bentuk JSON dalam dokumentasi cocok dengan implementasi yang diuji.
- Perilaku produk tidak aktif, stok habis, target/zone salah, saldo kurang, dan API key tidak valid memiliki keterangan status serta langkah tindak lanjut.

### Kontrak response produk

Kontrak field dan contoh response list/detail/error tersedia di [docs/RESELLER-API.md](./RESELLER-API.md) dan dokumentasi API pada halaman pengaturan reseller. Tipe mengacu pada JSON yang dikirimkan, bukan tipe database.

| Field | Bentuk JSON | Makna |
|---|---|---|
| `id` | integer | ID produk untuk `product_id` pada order |
| `code`, `name` | string | Kode dan nama produk |
| `brand`, `category` | string atau null | Nama relasi, dapat null |
| `payment_type` | string | `prabayar` atau `pascabayar`; tidak null |
| `region` | string atau null | Wilayah atau null untuk produk umum |
| Harga dan `discount` | integer | Nominal Rupiah; discount adalah selisih harga |
| `stock` | integer atau null | null saat unlimited |
| `unlimited_stock`, `requires_zone` | boolean | Stok tanpa batas dan kebutuhan zone |
| `target_label` | string | Label tujuan |

Feature tests memeriksa struktur list/detail, harga, nullability, stok unlimited, kebutuhan zone, pagination, dan validasi batas `per_page`. Perubahan kontrak API tetap perlu dijaga kompatibel dengan client yang sudah ada.

---

## 3. Rule Reseller belum ditampilkan secara kuat dan informatif

### Kondisi saat ini

Halaman reseller di [resources/views/user/settings/reseller.blade.php](../resources/views/user/settings/reseller.blade.php) sudah menjelaskan manfaat menjadi reseller, dan config di [config/reseller.php](../config/reseller.php) menjelaskan diskon dan aturan dasar. Namun rule tersebut belum terlihat dengan cara yang cukup kuat untuk keputusan penting seperti aktivasi akun.

### Masalah UX

- Halaman sudah menjelaskan diskon dan beberapa akibat tindakan: API key asli hanya tampil saat dibuat, regenerate mencabut key lama, dan deaktivasi mencabut akses API.
- Informasi manfaat dan konsekuensi tersebar pada blok yang berbeda; belum dirangkum sebagai aturan yang bisa dibaca sebelum aktivasi.
- Halaman menyebut keanggotaan gratis dan bisa dinonaktifkan, tetapi tidak secara eksplisit menjawab apakah saldo, riwayat transaksi, atau order yang sedang berjalan berubah saat deaktivasi.
- Tombol deaktivasi dan regenerate sudah meminta konfirmasi. Yang dapat diperbaiki adalah kekhususan pesan konfirmasi dan konteksnya, bukan menambahkan konfirmasi dari nol.

### Rekomendasi

1. Sebelum tombol aktivasi, rangkum aturan yang dapat dipastikan: persentase diskon yang berlaku, harga reseller dibeli dari saldo lewat API, API key adalah rahasia, dan key hanya terlihat saat dibuat.
2. Pastikan pernyataan tentang dampak deaktivasi terhadap saldo, order berjalan, dan histori transaksi telah dikonfirmasi dari aturan bisnis; jangan menjanjikan dampak yang belum dipastikan.
3. Ubah pesan konfirmasi regenerate agar menyatakan secara eksplisit bahwa key lama langsung tidak berlaku dan integrasi perlu diperbarui dengan key baru.
4. Ubah pesan konfirmasi deaktivasi agar menyebut akses API akan berhenti dan jelaskan dampak transaksi/saldo hanya setelah rule bisnis dipastikan.
5. Checkbox persetujuan eksplisit adalah opsi produk/legal, bukan kewajiban UX universal. Terapkan hanya jika memang diperlukan sebagai bukti persetujuan, dan jangan menambah friction tanpa kebutuhan.

### Kriteria penerimaan

- Sebelum aktivasi, user dapat menemukan diskon, cara pembayaran order API, serta perlakuan terhadap API key.
- Sebelum regenerate/deaktivasi, user memahami konsekuensi langsung dan apa yang perlu dilakukan sesudahnya.
- Key asli tidak tampil kembali setelah flash session berakhir; layar aktif menjelaskan bahwa hanya hint yang akan terlihat.
- Copy aturan tidak membuat janji tentang saldo atau order yang tidak dijamin sistem.

---

## 4. Payment flow: fokus pada Saldo dan QRIS

### Kondisi saat ini

Di checkout, sistem saat ini memang hanya mendukung dua metode pembayaran untuk pembelian produk:

- Saldo
- QRIS

Saldo dapat diisi melalui alur deposit/top up, tetapi deposit bukan metode pembayaran checkout. Transfer bank/e-wallet dari enum atau alur top up tidak boleh ditampilkan sebagai pilihan pembelian produk tanpa perubahan kebutuhan bisnis.

### Yang sudah tersedia

- Checkout membatasi pilihan aktual ke **Saldo** dan **QRIS**.
- Ringkasan menampilkan harga produk, fee QRIS (0,7%), total, saldo, dan sisa saldo saat Saldo dipilih.
- JavaScript memperbarui tampilan sisa saldo ketika metode pembayaran dipilih, memberi umpan balik saldo tidak cukup, dan meminta konfirmasi sebelum pengiriman.
- Server mengunci user/product saat membuat transaksi, memeriksa status dan stok, serta mendebit saldo dalam transaksi database untuk metode Saldo.
- Aplikasi menetapkan `expires_at` order QRIS tiga jam setelah order dibuat; masa berlaku yang ditampilkan ke user perlu sesuai dengan aturan provider/gateway yang berlaku.

### Kesenjangan yang teramati

- Label metode di UI menggunakan `Saldo`; halaman tambah saldo, navigasi, riwayat, konfirmasi, dan notifikasi pengguna menggunakan `Isi Saldo` untuk tindakannya. Istilah `Deposit` tetap istilah internal/teknis dan tidak digunakan sebagai nama metode pembayaran produk. Audit ini mencakup alur pengguna saldo/checkout; istilah di materi marketing dan halaman admin tidak diubah.
- Konfirmasi checkout saat ini generik (`Apakah pesanan dilanjutkan?`) dan tidak merangkum produk, tujuan, total, serta metode pembayaran yang dipilih.
- Masa berlaku tiga jam dan apa yang terjadi setelahnya tidak terlihat di ringkasan pilihan QRIS sebelum order dibuat.
- Nominal yang tampil di checkout adalah harga produk dan fee QRIS; audit ini tidak memverifikasi seluruh variasi fee/top up atau semua kondisi provider.
- Validasi saldo di browser membantu UX, tetapi saldo dapat berubah bersamaan atau dari sesi lain. Keputusan akhir harus tetap berasal dari server dan ditampilkan sebagai hasil otoritatif.

### Rekomendasi

1. Pertahankan dua opsi saja dan beri deskripsi ringkas:
   - `Saldo`: dipotong saat order dibuat; jika saldo kurang, arahkan untuk isi saldo.
   - `QRIS`: buat tagihan QRIS; bayar sebelum waktu kedaluwarsa.
2. Pertahankan ringkasan saldo dan sisa saldo yang dinamis. Pastikan status cukup/kurang terbaca sebagai teks, bukan hanya warna atau tanda minus.
3. Ubah dialog konfirmasi yang saat ini generik menjadi ringkasan singkat berisi produk, target/zone, total, metode, dan konsekuensi pembayaran. Untuk QRIS, sebutkan bahwa instruksi pembayaran muncul setelah order dibuat dan masa berlaku tagihan.
4. Tampilkan masa berlaku QRIS pada halaman instruksi setelah order dibuat; cocokkan hitung mundur dengan expiry dari provider dan order (`expires_at`). Sediakan status pending/paid/expired dan CTA cek status yang mudah ditemukan.
5. Jika saldo tidak cukup, sediakan CTA yang jelas menuju halaman isi saldo. Jangan menyebut metode pembayaran sebagai “Deposit” jika yang didebit adalah saldo akun.
6. Tampilkan biaya sebagai baris terpisah dan konsisten dengan total final; nyatakan bahwa fee QRIS adalah 0,7% bila itu merupakan kebijakan aktual yang berlaku.
7. Tangani konflik state secara eksplisit: stok habis atau saldo berubah setelah halaman dibuka harus menghasilkan pesan yang menjelaskan masalah dan langkah selanjutnya, bukan hanya error generik.

### Kriteria penerimaan

- User dapat mengidentifikasi dua pilihan yang memang tersedia: Saldo dan QRIS.
- Sebelum mengonfirmasi, user melihat produk, tujuan, harga, fee jika ada, total, metode, dan dampak pembayaran.
- Saldo kurang menghasilkan status yang jelas dan CTA isi saldo; server tetap menolak debit yang tidak memenuhi saldo.
- QRIS menampilkan instruksi, batas waktu, status terkini, dan jalur bantuan jika pembayaran belum terdeteksi.
- Saldo tidak terpotong untuk order QRIS yang belum dibayar; transaksi Saldo hanya berhasil jika server mengonfirmasi dan mendebit dalam transaksi.

### Top up manual melalui transfer bank / e-wallet

- [x] Bukti transfer hanya dapat dikirim oleh pemilik top up manual dan tersimpan di disk privat.
- [x] Saldo baru ditambahkan setelah admin menyetujui bukti; QRIS tetap mengikuti verifikasi Midtrans.
- [x] Admin dapat meminta perbaikan dengan alasan atau menolak secara final dengan alasan yang diteruskan ke pengguna.
- [x] Bukti lama tetap tersimpan selama masa retensi dan dihapus otomatis 90 hari setelah top up final.
- [x] Riwayat bukti dan hasil pemeriksaan terlihat oleh pengguna pemilik dan admin berizin.

---

## 5. Notifikasi masih belum konsisten sebagai feedback utama

### Kondisi saat ini

Notifikasi database dan notification center sudah ada. Topbar memeriksa notifikasi berkala, serta JavaScript menyediakan toast untuk notifikasi baru ketika aplikasi sedang terbuka. Halaman penuh mendukung pencarian/filter dan notifikasi dapat memiliki tautan tindakan.

### Kesenjangan yang teramati

- Belum semua action memiliki pola feedback yang sama; sebagian aksi memakai flash message di halaman terkait, sementara notification center digunakan untuk sebagian hasil transaksi.
- Toast notifikasi baru bergantung pada notifikasi database yang dibuat untuk event tersebut; toast bukan jaminan setiap validasi/form action akan mengirim notifikasi.
- Tingkat `warning` belum memiliki ikon khusus pada fallback icon `TransactionResult` (fallback yang ada untuk success, error, dan lainnya).
- Audit ini belum memetakan setiap event transaksi dari dibuat, menunggu, sukses, gagal, hingga expired ke notifikasi dan CTA yang cocok.

### Rekomendasi

1. Jangan membangun ulang toast atau notification center yang sudah ada. Petakan event apa yang menggunakan inline/flash feedback, toast, dan notification center.
2. Gunakan inline feedback dekat aksi untuk error validasi dan hasil yang harus segera ditindaklanjuti; gunakan notification center sebagai riwayat untuk event penting/asinkron.
3. Buat notifikasi transaksi dapat ditindaklanjuti dengan tujuan yang benar: buka detail order, buka halaman instruksi pembayaran, atau buka halaman saldo.
4. Bedakan pesan informasi, sukses, peringatan, dan gagal dengan teks/ikon yang konsisten. Pastikan aksesibilitas tidak hanya mengandalkan warna.
5. Cegah duplikasi atau spam notifikasi pada perubahan status berulang; satu event status penting sebaiknya punya satu notifikasi yang jelas.
6. Pastikan pesan expired/gagal memberi langkah berikutnya, seperti cek saldo, cek status, atau buat order baru sesuai kebijakan.

### Kriteria penerimaan

- Setiap aksi penting memberi respons langsung yang dapat dipahami tanpa mengandalkan badge notifikasi.
- Event transaksi asinkron muncul di notification center dengan status, waktu, dan CTA relevan.
- Toast dan notification center tidak saling menduplikasi secara membingungkan.
- Screen reader dapat mengumumkan feedback; tombol notifikasi punya nama aksesibel dan status unread dapat diketahui.

---

## 6. Onboarding reseller dan edukasi penggunaan API

### Kondisi saat ini

Halaman reseller di [resources/views/user/settings/reseller.blade.php](../resources/views/user/settings/reseller.blade.php) sudah memiliki manfaat, instruksi API key, tautan dokumentasi, serta ringkasan penggunaan. Dokumentasi API memuat langkah mengambil produk, membuat order, dan memantau status.

Peningkatan yang masih berguna adalah membantu pengguna baru memahami langkah berikutnya tanpa harus membaca seluruh dokumentasi sekaligus. Ini merupakan peningkatan discoverability, bukan kekurangan fungsional kritis.

### Rekomendasi

Tampilkan checklist ringkas dan kontekstual:

1. `Aktifkan reseller` — tautkan ke aksi aktivasi bila belum aktif.
2. `Simpan API key` — tampilkan peringatan rahasia dan status bahwa key asli hanya muncul sekali.
3. `Baca dokumentasi dan ambil produk` — beri tautan ke bagian produk.
4. `Isi saldo dan buat order percobaan` — jelaskan order API selalu didebit dari saldo reseller.
5. `Pantau order` — tautkan ke riwayat/API endpoint yang sesuai.

Checklist sebaiknya menyembunyikan atau menandai langkah yang sudah selesai berdasarkan data yang memang tersedia. Jangan melacak “key sudah disalin” jika aplikasi tidak menyimpan status tersebut.

### Kriteria penerimaan

- User baru memiliki satu langkah berikutnya yang jelas setelah aktivasi.
- Setiap CTA menuju tujuan yang benar dan label tombol menjelaskan hasilnya.
- Checklist tidak mengungkap API key dan tidak mengklaim bahwa key tersimpan di perangkat user.
- Pengguna lama tidak dipaksa melewati onboarding berulang setiap membuka halaman.

---

## 7. Prioritas implementasi

Prioritas di bawah adalah rekomendasi berdasarkan dampak UX dan risiko salah paham, bukan indikasi bahwa fungsionalitas dasarnya tidak ada.

### Selesai pada implementasi awal

Perbaikan mudah berikut sudah diterapkan:

- [x] Field target/zone di checkout ditandai wajib secara visual dan lewat atribut HTML, dengan helper text yang relevan.
- [x] Opsi Saldo menjelaskan kapan saldo dipotong; opsi QRIS menjelaskan batas pembayaran tiga jam.
- [x] Saat Saldo dipilih, checkout menampilkan status saldo cukup/kurang; saat kurang tersedia CTA Isi Saldo.
- [x] Halaman aktivasi reseller merangkum aturan harga, pembayaran order API, kerahasiaan API key, regenerate, dan deaktivasi.
- [x] Persentase manfaat reseller pada kartu menggunakan nilai konfigurasi, bukan angka `5%` yang di-hardcode.
- [x] Feature test checkout mencakup status saldo cukup dan CTA Isi Saldo saat saldo kurang; feature test reseller memeriksa aturan aktivasi.

Masih terbuka: sinkronisasi expiry QRIS dengan provider, pemetaan event notifikasi, dan kontrak API produk lengkap.

| Prioritas | Perbaikan | Dampak jika tidak dilakukan | Kriteria siap |
|---|---|---|---|
| P1 | Ringkasan konfirmasi checkout yang kontekstual | User tidak memastikan target, biaya, atau metode sebelum order dibuat | ✅ Dialog merangkum produk, tujuan/Zone ID, total, metode, dan konsekuensi pembayaran |
| P1 | Penamaan Saldo vs Deposit/top up konsisten | User mengira deposit adalah metode checkout atau saldo tidak langsung dipotong | ✅ Checkout, navigasi, pengisian saldo, riwayat, notifikasi pengguna, dan pesan status membedakan `Saldo` (metode bayar) dari `Isi Saldo` (menambah saldo); audit marketing/admin di luar cakupan |
| P1 | Wajib/helper text untuk target dan zone | Salah input tujuan dapat menyebabkan transaksi gagal atau salah sasaran | ✅ Field wajib, helper text, dan petunjuk kategori sudah ditambahkan |
| P1 | Penjelasan rule aktivasi dan API key | User tidak memahami kredensial dan dampak regenerate/deaktivasi | ✅ Ringkasan aturan aktivasi sudah ditampilkan sebelum tombol aktivasi |
| P1 | Status saldo dan CTA Isi Saldo | User tidak tahu apakah saldo cukup atau cara mengatasinya | ✅ Status cukup/kurang dan tautan Isi Saldo ditampilkan saat Saldo dipilih |
| P2 | Dokumen kontrak response produk | Integrator harus menebak tipe/nullability/arti nilai | ✅ Field table, contoh list/detail/error, pagination, dan filter dicocokkan dengan output serta feature tests |
| P2 | Pemetaan feedback transaksi ke notifikasi | User tidak tahu langkah berikutnya saat pending/gagal/expired | Event matrix menentukan inline/toast/notification center dan CTA |
| P3 | Onboarding reseller kontekstual | User baru perlu mencari sendiri urutan mulai | Checklist langkah dan tautan relevan tanpa mengulang untuk user lama |
| P3 | Aksesibilitas feedback | User screen reader/keyboard dapat kehilangan status atau error | Focus, label, live announcement, dan status tidak hanya dibedakan dengan warna |

### Rencana pengerjaan yang disarankan

1. **Microcopy dan label** — rapikan istilah Saldo/deposit, required marker, helper text, dan aturan reseller. Risiko rendah dan tidak mengubah business logic.
2. **Ringkasan konfirmasi** — gunakan data form yang sudah ada; jangan mengubah perhitungan harga/fee di browser menjadi sumber kebenaran.
3. **Status pembayaran** — verifikasi lifecycle QRIS dengan provider dahulu, lalu pastikan expiry, polling/cek status, expired state, dan CTA selaras.
4. **Dokumentasi API** — bandingkan contoh dan tabel kontrak dengan endpoint serta test aktual.
5. **Notifikasi dan onboarding** — petakan event dan penerima, hindari notifikasi duplikat, lalu tambah CTA/checklist yang memang bisa ditentukan dari state sistem.

Perbaikan copy terminologi pada alur Saldo/checkout sudah diterapkan. Istilah marketing dan halaman admin tidak termasuk dalam audit terbatas ini.

---

## 8. Checklist verifikasi implementasi

### Form dan validasi

- [x] Label checkout menyatakan field target/zone wajib tanpa mengandalkan placeholder.
- [x] Helper text checkout menjelaskan kewajiban field sebelum submit.
- [x] Helper/contoh input menyebut format spesifik yang sesuai produk dan tidak memberi contoh menyesatkan.
- [x] Pesan error server-side muncul di field yang sesuai, input lain tetap terisi.
- [x] Validasi client-side membantu pengguna; backend tetap menolak target/zone invalid atau saldo tidak mencukupi.
- [x] Fokus keyboard berpindah ke field invalid pertama dan error terhubung melalui atribut aksesibilitas.

### Produk dan API reseller

- [x] Response daftar produk hanya berisi produk aktif dan pagination terdokumentasi.
- [x] Response detail produk aktif sesuai kontrak yang didokumentasikan.
- [x] Harga, stok unlimited, zone, nullability, dan status error diuji.
- [x] Rule aktivasi, diskon, penggunaan saldo, regenerate, dan deaktivasi dijelaskan sebelum aktivasi.
- [ ] Key plaintext hanya ditampilkan saat dibuat dan tidak masuk notifikasi/log/UI yang tidak aman.

### Payment checkout

- [x] Checkout menawarkan Saldo dan QRIS saja.
- [x] Opsi Saldo menjelaskan pemotongan saat order dibuat; QRIS menjelaskan batas pembayaran tiga jam.
- [x] Status cukup/kurang tampil saat metode Saldo dipilih; CTA Isi Saldo tampil hanya saat jumlah saldo kurang.
- [x] Harga produk + fee QRIS sama dengan total QRIS; saat memilih Saldo, fee disembunyikan dan total kembali ke harga produk, sementara saldo dan sisa saldo ditampilkan.
- [x] Saldo kurang memiliki CTA Isi Saldo; penolakan backend tetap menjadi sumber kebenaran.
- [x] Jika saldo tidak cukup saat submit, error tampil dekat metode pembayaran, menjelaskan bahwa order tidak dibuat, dan memberi CTA Isi Saldo.
- [x] Konfirmasi menyertakan detail produk, tujuan/Zone ID, total, metode, dan dampak pembayaran.
- [x] Status QRIS pending menampilkan instruksi pembayaran, batas waktu pesanan Atlantic, dan CTA "Cek Status Pembayaran".
- [ ] Batas waktu pesanan yang ditampilkan sudah diverifikasi selaras dengan expiry transaksi provider.
- [ ] Pending, paid, failed, expired, stok habis, dan saldo berubah punya pesan/CTA yang benar.

### Poin reward

Kondisi saat ini: poin diberikan untuk top up dan transaksi produk sukses dengan nilai tetap 1 poin. Rekomendasi perubahan kebijakan: top up tidak memberi poin; poin hanya diberikan setelah transaksi produk benar-benar berhasil diproses.

- [ ] Ringkasan transaksi menjelaskan estimasi poin yang akan didapat, dan detail/riwayat transaksi mencatat poin aktual setelah transaksi sukses.
- [ ] Rumus poin memakai margin transaksi: harga jual snapshot dikurangi modal supplier snapshot dan biaya/diskon yang benar-benar ditanggung perusahaan. Nilai Rupiah disimpan dan dihitung presisi sebagai integer rupiah; hindari floating-point.
- [ ] Poin dihitung dari bagian margin yang dikembalikan (usulan awal 30%), dikalikan tier dan dibagi nilai Rupiah per poin (usulan awal Rp10 per poin); pembulatan ke bawah hanya dilakukan pada hasil poin.
- [ ] Margin nol/negatif menghasilkan 0 poin. Transaksi gagal/refund serta transaksi yang menggunakan poin atau promo yang ditanggung perusahaan tidak menghasilkan poin atau membalik poin sesuai aturan yang diinformasikan.
- [ ] Biaya QRIS hanya mengurangi margin bila benar-benar ditanggung perusahaan; fee yang dibayar pelanggan tidak dikurangkan dua kali.
- [ ] Saldo poin, poin tertunda/kedaluwarsa, nilai minimum penukaran, dan kebutuhan poin merchandise dijelaskan sebelum user menukar.
- [ ] Tingkatan tidak turun karena poin ditukar (rekomendasi: tier berdasarkan total poin yang pernah diperoleh); dasar tier masih perlu keputusan bisnis.
- [ ] Sebelum kebijakan poin baru aktif, tampilkan aturan earn/redeem dan masa berlaku dengan jelas; migrasi saldo/riwayat poin lama harus direkonsiliasi dan dikomunikasikan.

### Top up manual

- [x] Transfer bank/e-wallet meminta bukti sebelum masuk antrean verifikasi admin.
- [x] Bukti salah dapat diperbaiki tanpa menghapus riwayat bukti sebelumnya.
- [x] Transfer yang tidak ditemukan dapat ditolak dengan alasan; saldo tidak dikreditkan.
- [x] File bukti tidak tersedia melalui URL publik dan retensinya berakhir 90 hari setelah status final.

### Notifikasi

- [ ] Aksi memberi feedback langsung walaupun tidak menghasilkan notifikasi tersimpan.
- [ ] Event transaksi penting memiliki notification center entry dan CTA bila diperlukan.
- [x] Pengiriman bukti memberi notifikasi admin; permintaan perbaikan/penolakan dan persetujuan memberi notifikasi pengguna.
- [x] Ikon default notifikasi `warning` sudah dibedakan dari ikon sukses, gagal, dan umum.
- [x] Notifikasi tetap tersimpan di pusat sebagai riwayat, sementara ID yang sama mencegah toast ganda dari polling dan FCM; saat halaman aktif hanya toast yang tampil, saat di latar belakang hanya notifikasi sistem yang tampil.
- [x] Status/hasil disampaikan lewat judul dan isi notifikasi; ikon dan warna hanya menjadi penanda tambahan.

## 9. Skenario uji UX yang direkomendasikan

### Checkout produk

1. Produk tanpa zone, target kosong → blokir submit, fokus ke target, jelaskan bahwa target wajib.
2. Produk dengan zone, target terisi dan zone kosong → blokir submit dan fokus ke zone.
3. Nilai target di bawah batas panjang / melampaui batas → server memberi error pada target dan data tidak hilang.
4. Saldo cukup → tampilkan pengurangan dan sisa saldo; setelah konfirmasi, order terbuat satu kali.
5. Saldo kurang → pesan menjelaskan kekurangan dan tautan isi saldo; saldo tidak berubah.
6. Saldo berubah setelah checkout dibuka → backend adalah otoritas; tampilkan error dekat metode pembayaran, pastikan order tidak dibuat, lalu tawarkan isi saldo atau pilih QRIS.
7. QRIS dipilih → fee dan total terlihat; setelah order, instruksi dan batas waktu tersedia.
8. QRIS dibayar, belum terdeteksi, dan expired → setiap state memiliki label, waktu/status yang diperbarui, serta CTA berikutnya.
9. Klik konfirmasi ganda / koneksi lambat → cegah order duplikat atau jelaskan hasil order yang sudah terbentuk.

### Reseller dan API

1. User belum aktif → bisa memahami benefit, diskon, syarat API key, dan batas penggunaan sebelum mengaktifkan.
2. Aktivasi sukses → key asli hanya muncul pada kesempatan pertama; user dapat menyalin dan mendapat peringatan menyimpan aman.
3. Regenerate → konfirmasi menyebut key lama dicabut; setelah aksi key lama gagal dan key baru dapat dipakai.
4. Deaktivasi → API key dicabut; pesan menyebut dampak yang telah dipastikan, bukan asumsi.
5. Daftar produk kosong/filter tidak cocok → response valid dengan `data: []`, metadata konsisten, dan dokumentasi mengartikan hasilnya.
6. Detail produk tidak aktif/tidak ada → 404 sesuai dokumentasi.
7. Order reseller dengan saldo cukup/kurang, stok tersedia/habis, zone wajib/tidak wajib → status dan `errors` dapat ditindaklanjuti.

### Notifikasi

1. Notifikasi baru muncul ketika tab aplikasi terbuka → badge dan toast sinkron dengan jumlah unread.
2. Klik notifikasi → ditandai dibaca dan diarahkan ke path yang sesuai.
3. Notifikasi tanpa URL valid → tetap menuju pusat notifikasi, bukan gagal atau mengarah ke host asing.
4. Mark all as read → badge dropdown dan halaman penuh berubah konsisten.
5. Notifikasi sukses/gagal/expired → teks, ikon, level, dan CTA cocok dengan event.

## 10. Batas audit dan hal yang perlu dipastikan

Dokumen ini merupakan review UX terarah, bukan audit seluruh aplikasi. Beberapa hal berikut perlu diverifikasi sebelum dianggap sebagai bug atau komitmen produk:

- Apakah semua form selain checkout memiliki pola wajib/opsional dan error per-field yang sama.
- Apakah expiry tiga jam yang disimpan aplikasi sama dengan expiry aktual dari Midtrans untuk semua transaksi QRIS.
- Kebijakan poin yang berlaku: persentase margin kembali, nilai Rupiah per poin, multiplier tier, masa tunggu/kedaluwarsa, dan apakah tier memakai total poin seumur hidup atau saldo berjalan.
- Perlakuan terhadap poin dan riwayat lama saat kebijakan poin berubah, termasuk saldo awal dan top up yang sebelumnya pernah menghasilkan poin.
- Apakah order QRIS yang belum dibayar dapat dibatalkan/dibuat ulang dan bagaimana aturan stoknya.
- Dampak deaktivasi reseller terhadap saldo, riwayat, order berjalan, dan order API baru.
- Event transaksi apa saja yang mengirim `TransactionResult`, untuk status apa, dan kepada siapa.
- Apakah payload JSON error yang dilihat client selalu sama dengan contoh dokumentasi ketika header `Accept: application/json` dikirim.
- Perilaku aksesibilitas pada perangkat keyboard/screen reader dan tampilan responsif di ukuran layar yang didukung.

---

## Kesimpulan

Secara fungsional, fitur reseller, katalog/API produk, checkout Saldo/QRIS, dan notifikasi sudah memiliki fondasi yang baik. Perbaikan dengan dampak terbesar adalah memperjelas apa yang user setujui dan bayar, memastikan state pembayaran mudah dipahami, serta mendokumentasikan kontrak response API secara presisi. Bagian yang memerlukan konfirmasi bisnis atau provider ditandai untuk verifikasi, bukan diasumsikan sebagai kekurangan sistem.

Perubahan implementasi awal ditandai `[x]` di bagian prioritas dan checklist. Item yang belum dicentang belum diimplementasikan atau masih memerlukan verifikasi tersendiri.
