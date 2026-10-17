<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Menyajikan service worker Firebase Messaging.
 *
 * Ditulis sebagai route (bukan file statis di public/) supaya konfigurasi
 * klien diambil dari env yang sama dengan config/firebase.php. Nilai-nilai ini
 * memang publik (dipakai di browser), bukan rahasia.
 */
class FirebaseServiceWorkerController extends Controller
{
    public function __invoke(): Response
    {
        $config = config('firebase.client');

        return response()
            ->view('firebase-messaging-sw', [
                'swVersion' => '11.6.0',
                'config' => [
                    'apiKey' => $config['api_key'],
                    'authDomain' => $config['auth_domain'],
                    'projectId' => $config['project_id'],
                    'storageBucket' => $config['storage_bucket'],
                    'messagingSenderId' => $config['messaging_sender_id'],
                    'appId' => $config['app_id'],
                ],
            ])
            ->header('Content-Type', 'application/javascript')
            ->header('Service-Worker-Allowed', '/');
    }
}
