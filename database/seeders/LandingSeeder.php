<?php

namespace Database\Seeders;

use App\Models\LandingFaq;
use App\Models\LandingService;
use App\Models\LandingSetting;
use App\Models\LandingTestimonial;
use Illuminate\Database\Seeder;

/**
 * Konten dummy landing page. Aman dijalankan berulang (idempotent).
 */
class LandingSeeder extends Seeder
{
    public function run(): void
    {
        LandingSetting::query()->updateOrCreate(['id' => 1], LandingSetting::defaults() + [
            'whatsapp_number' => '6281234567890',
        ]);

        $services = [
            ['icon' => 'bolt', 'title' => 'Pulsa & Paket Data', 'description' => 'Isi pulsa semua operator dan paket data dengan proses otomatis dalam hitungan detik.'],
            ['icon' => 'electric_bolt', 'title' => 'Token & Tagihan Listrik', 'description' => 'Beli token listrik prabayar serta bayar tagihan listrik dan air tanpa antre.'],
            ['icon' => 'sports_esports', 'title' => 'Voucher Game', 'description' => 'Top up diamond, UC, dan voucher game favorit dengan harga bersaing.'],
            ['icon' => 'account_balance_wallet', 'title' => 'Deposit Saldo', 'description' => 'Top up saldo lewat QRIS, transfer bank, atau e-wallet dengan verifikasi cepat.'],
            ['icon' => 'verified_user', 'title' => 'Pembayaran Aman', 'description' => 'Setiap transaksi tercatat rapi dan diproses lewat gateway pembayaran terpercaya.'],
            ['icon' => 'support_agent', 'title' => 'CS Siaga 24/7', 'description' => 'Tim dukungan siap membantu kendala transaksi kapan pun kamu butuhkan.'],
        ];

        foreach ($services as $index => $service) {
            LandingService::query()->updateOrCreate(
                ['title' => $service['title']],
                $service + ['sort_order' => $index, 'is_active' => true],
            );
        }

        $testimonials = [
            ['name' => 'Rizky Pratama', 'role' => 'Pemilik Konter Pulsa', 'message' => 'Transaksi pulsa selalu cepat dan stoknya lengkap. Deposit QRIS langsung masuk, jadi konter saya nggak pernah kosong.', 'rating' => 5],
            ['name' => 'Sari Wulandari', 'role' => 'Reseller Online', 'message' => 'Harga resellernya masuk akal dan laporan transaksi rapi. Cocok banget buat yang jualan voucher game.', 'rating' => 5],
            ['name' => 'Dimas Saputra', 'role' => 'Pengguna Harian', 'message' => 'Beli token listrik tengah malam tetap diproses instan. CS-nya juga responsif saat saya tanya soal tagihan.', 'rating' => 5],
            ['name' => 'Nadia Putri', 'role' => 'Admin Toko Digital', 'message' => 'Fitur riwayat dan invoice membantu pembukuan toko. Suka karena semua bukti transaksi tersimpan otomatis.', 'rating' => 4],
            ['name' => 'Agus Setiawan', 'role' => 'Mitra Supplier', 'message' => 'Proses pengajuan supplier jelas dan ditindaklanjuti cepat. Komunikasinya enak, timnya terbuka dengan masukan.', 'rating' => 5],
            ['name' => 'Lena Marlina', 'role' => 'Reseller Paket Data', 'message' => 'Dashboard-nya simpel, saldo dan mutasi mudah dicek. Order lewat API juga stabil untuk kebutuhan harian.', 'rating' => 5],
        ];

        foreach ($testimonials as $index => $testimonial) {
            LandingTestimonial::query()->updateOrCreate(
                ['name' => $testimonial['name']],
                $testimonial + ['sort_order' => $index, 'is_active' => true],
            );
        }

        $faqs = [
            ['question' => 'Bagaimana cara mulai bertransaksi?', 'answer' => 'Daftar akun, verifikasi email, lalu top up saldo melalui menu Deposit. Setelah saldo masuk, kamu bisa langsung membeli produk digital apa pun.'],
            ['question' => 'Metode pembayaran apa saja yang tersedia?', 'answer' => 'Kami menerima QRIS, transfer bank, dan e-wallet. Untuk transaksi tertentu kamu juga bisa membayar langsung menggunakan saldo akun.'],
            ['question' => 'Apakah transaksi diproses otomatis?', 'answer' => 'Ya. Sebagian besar produk diproses otomatis oleh provider. Jika ada gangguan, status transaksi akan diperbarui dan tim CS siap membantu.'],
            ['question' => 'Bagaimana cara menjadi reseller?', 'answer' => 'Buka Pengaturan → Reseller, aktifkan keanggotaan, lalu salin API key yang tampil satu kali. Reseller mendapat harga khusus dan dapat bertransaksi lewat API.'],
            ['question' => 'Bagaimana jika transaksi bermasalah?', 'answer' => 'Gunakan tombol bantuan WhatsApp pada detail transaksi. Pesan otomatis berisi nomor pesanan, produk, tujuan, nominal, dan status agar admin bisa langsung mengecek.'],
        ];

        foreach ($faqs as $index => $faq) {
            LandingFaq::query()->updateOrCreate(
                ['question' => $faq['question']],
                $faq + ['sort_order' => $index, 'is_active' => true],
            );
        }
    }
}
