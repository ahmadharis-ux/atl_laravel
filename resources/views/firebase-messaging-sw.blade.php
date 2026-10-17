/* eslint-disable no-undef */
/* Service worker Firebase Cloud Messaging — dilayani oleh
   FirebaseServiceWorkerController dengan konfigurasi dari env. */

importScripts('https://www.gstatic.com/firebasejs/{{ $swVersion }}/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/{{ $swVersion }}/firebase-messaging-compat.js');

firebase.initializeApp({!! json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!});

firebase.messaging().onBackgroundMessage(function (payload) {
    var notification = (payload && payload.notification) || {};
    var data = (payload && payload.data) || {};
    var title = notification.title || data.title || 'Notifikasi baru';
    var body = notification.body || data.body || '';

    self.registration.showNotification(title, {
        body: body,
        icon: '/assets/images/6.png',
        badge: '/assets/images/6.png',
        data: { url: data.url || notification.click_action || '/' },
        tag: data.order_number || data.reference || ('atl-' + Date.now()),
    });
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    var target = (event.notification.data && event.notification.data.url) || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (windowClients) {
            for (var i = 0; i < windowClients.length; i++) {
                var client = windowClients[i];
                if ('focus' in client) {
                    client.navigate(target);
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(target);
            }
        })
    );
});
