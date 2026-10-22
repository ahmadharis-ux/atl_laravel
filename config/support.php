<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nomor WhatsApp Customer Service
    |--------------------------------------------------------------------------
    |
    | Nomor tujuan tombol bantuan WhatsApp (format bebas: 08xx, 628xx, atau
    | +62 8xx). Bila kosong, sistem memakai nomor dari konten landing page.
    |
    */
    'whatsapp_number' => env('SUPPORT_WHATSAPP_NUMBER', ''),

];
