<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kredensial Midtrans
    |--------------------------------------------------------------------------
    |
    | Ambil dari dashboard Midtrans (Settings > Access Keys). Selama server_key
    | dan client_key kosong, integrasi Midtrans dianggap non-aktif dan aplikasi
    | kembali ke mode simulasi pembayaran.
    |
    */
    'merchant_id' => env('MIDTRANS_MERCHANT_ID'),

    'client_key' => env('MIDTRANS_CLIENT_KEY'),

    'server_key' => env('MIDTRANS_SERVER_KEY'),

    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),

    'is_sanitized' => env('MIDTRANS_IS_SANITIZED', true),

    'is_3ds' => env('MIDTRANS_IS_3DS', true),

    /*
    |--------------------------------------------------------------------------
    | Override Notification URL
    |--------------------------------------------------------------------------
    |
    | Bila diisi, Midtrans akan mengirim notifikasi status transaksi ke URL ini
    | (via header X-Override-Notification), menimpa pengaturan di dashboard.
    | Berguna untuk pengembangan lokal dengan tunnel (ngrok/cloudflared).
    |
    */
    'notification_url' => env('MIDTRANS_NOTIFICATION_URL'),

    /*
    |--------------------------------------------------------------------------
    | E-Wallet Tokenization / Direct Debit
    |--------------------------------------------------------------------------
    |
    | Fitur ini masih berupa scaffolding. Aktifkan setelah akun Midtrans
    | disetujui untuk Direct Debit dan deprecation token sudah didaftarkan.
    |
    */
    'tokenization_enabled' => env('MIDTRANS_TOKENIZATION_ENABLED', false),

];
