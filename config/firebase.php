<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Kredensial Server (FCM HTTP v1)
    |--------------------------------------------------------------------------
    |
    | FIREBASE_CREDENTIALS bisa berupa path file JSON service account, atau
    | isi JSON-nya langsung. Ambil dari Firebase Console → Project Settings →
    | Service Accounts → Generate new private key. Jangan commit file ini.
    |
    */
    'credentials' => env('FIREBASE_CREDENTIALS'),

    'project_id' => env('FIREBASE_PROJECT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Klien (Web SDK)
    |--------------------------------------------------------------------------
    |
    | Nilai-nilai ini dipakai di sisi browser (bukan rahasia). Ambil dari
    | Firebase Console → Project Settings → General → Your apps (Web).
    | VAPID key dari Cloud Messaging → Web Push certificates.
    |
    */
    'client' => [
        'api_key' => env('FIREBASE_API_KEY'),
        'auth_domain' => env('FIREBASE_AUTH_DOMAIN'),
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'storage_bucket' => env('FIREBASE_STORAGE_BUCKET'),
        'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID'),
        'app_id' => env('FIREBASE_APP_ID'),
        'vapid_key' => env('FIREBASE_VAPID_KEY'),
    ],
];
