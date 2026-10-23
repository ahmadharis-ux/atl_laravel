<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan konten landing page (singleton). Baris pertama dipakai sebagai
 * konfigurasi aktif; dibuat otomatis dengan nilai default bila belum ada.
 */
class LandingSetting extends Model
{
    protected $fillable = [
        'hero_badge',
        'hero_title',
        'hero_subtitle',
        'hero_image',
        'hero_cta_label',
        'hero_cta_url',
        'hero_secondary_label',
        'hero_secondary_url',
        'about_title',
        'about_body',
        'whatsapp_number',
        'stat_customers',
        'stat_transactions',
        'stat_products',
        'stat_support',
        'meta_description',
    ];

    public static function current(): self
    {
        return static::query()->orderBy('id')->first()
            ?? static::query()->create(static::defaults());
    }

    /**
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        return [
            'hero_badge' => 'PPOB & Top Up Terpercaya',
            'hero_title' => 'Semua Kebutuhan Digital, Satu Aplikasi',
            'hero_subtitle' => 'Beli pulsa, paket data, token listrik, dan produk digital lain dengan harga terbaik. Proses instan, aman, dan didukung tim CS 24 jam.',
            'hero_cta_label' => 'Mulai Transaksi',
            'hero_cta_url' => '/register',
            'hero_secondary_label' => 'Lihat Produk',
            'hero_secondary_url' => '/produk',
            'about_title' => 'Kenapa Atlantic Pedia?',
            'about_body' => 'Atlantic Pedia hadir sebagai mitra transaksi digital untuk kebutuhan sehari-hari. Harga bersaing, pembayaran fleksibel, dan dukungan tim yang sigap.',
            'whatsapp_number' => null,
            'stat_customers' => '25.000+',
            'stat_transactions' => '1,2 Jt+',
            'stat_products' => '500+',
            'stat_support' => '24/7',
            'meta_description' => 'Atlantic Pedia — layanan PPOB, pulsa, paket data, token listrik, dan produk digital dengan proses instan.',
        ];
    }
}
