<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Diskon Harga Reseller (persen)
    |--------------------------------------------------------------------------
    |
    | Potongan harga (dalam persen) dari harga jual publik yang dipakai saat
    | reseller membeli lewat API. Harga reseller tidak pernah turun di bawah
     | harga modal produk. Nilai utama disimpan di FeatureSetting; env menjadi
     | fallback bila admin belum menetapkan nilai di database.
    |
    */
    'discount_percent' => (float) env('RESELLER_DISCOUNT_PERCENT', 5),

    /*
    |--------------------------------------------------------------------------
    | Panjang Random API Key
    |--------------------------------------------------------------------------
    |
    | Jumlah karakter random setelah prefiks "ap_". Key disimpan sebagai
    | hash SHA-256 di database sehingga key asli hanya tampil sekali.
    |
    */
    'key_random_length' => 40,

    /*
    |--------------------------------------------------------------------------
    | Versi ketentuan reseller
    |--------------------------------------------------------------------------
    |
    | Naikkan versi ini setiap kali ketentuan pendaftaran reseller berubah.
    |
    */
    'terms_version' => '1.0',

    /*
    |--------------------------------------------------------------------------
    | Webhook Status Pesanan
    |--------------------------------------------------------------------------
    |
    | Konfigurasi pengiriman notifikasi status pesanan API ke URL milik
    | reseller. Setiap payload ditandatangani HMAC-SHA256 memakai secret
    | reseller (header X-ATL-Signature berformat "sha256=<hex>").
    |
    */
    'webhook' => [
        'secret_prefix' => 'whsec_',
        'connect_timeout' => 5,
        'timeout' => 10,
        'tries' => 3,
        'backoff' => [30, 120],
    ],

];
