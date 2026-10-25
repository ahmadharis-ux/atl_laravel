<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Diskon Harga Reseller (persen)
    |--------------------------------------------------------------------------
    |
    | Potongan harga (dalam persen) dari harga jual publik yang dipakai saat
    | reseller membeli lewat API. Harga reseller tidak pernah turun di bawah
    | harga modal produk. Atur lewat env RESELLER_DISCOUNT_PERCENT.
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

];
