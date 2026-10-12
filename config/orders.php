<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Batas waktu pembayaran pesanan (menit)
    |--------------------------------------------------------------------------
    |
    | Pesanan yang masih berstatus Pending (menunggu pembayaran VA/e-wallet)
    | lebih lama dari nilai ini akan otomatis kedaluwarsa dan stoknya
    | dikembalikan oleh perintah `orders:expire-pending`.
    |
    */
    'pending_ttl_minutes' => (int) env('ORDERS_PENDING_TTL_MINUTES', 180),
];
