/* eslint-disable no-undef */
/* Service worker Firebase Cloud Messaging — dilayani oleh
   FirebaseServiceWorkerController dengan konfigurasi dari env. */

importScripts('https://www.gstatic.com/firebasejs/{{ $swVersion }}/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/{{ $swVersion }}/firebase-messaging-compat.js');

firebase.initializeApp({!! json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!});

firebase.messaging();
