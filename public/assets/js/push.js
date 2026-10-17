/* Push notification (Firebase Cloud Messaging) untuk area user.
 *
 * Konfigurasi klien diisi oleh layout ke `window.ATL_PUSH`. Modul ini hanya
 * aktif bila Firebase sudah dikonfigurasi (ATL_PUSH.configured) dan browser
 * mendukung Service Worker + Push + Notification.
 *
 * - enable(): minta izin, daftar service worker, ambil token FCM, kirim ke server.
 * - disable(): hapus token di server + lokal.
 * - onMessage: tampilkan toast saat notifikasi tiba selagi aplikasi terbuka.
 * Notifikasi saat aplikasi tertutup ditangani oleh service worker (firebase-messaging-sw.js).
 */
(function () {
    'use strict';

    var cfg = window.ATL_PUSH || null;
    var supported = !!(cfg && 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window);
    var state = { registration: null, messaging: null, token: null };

    function log() {
        if (window.console && window.console.warn) {
            var args = Array.prototype.slice.call(arguments);
            args.unshift('[push]');
            window.console.warn.apply(window.console, args);
        }
    }

    function flag() {
        try { return localStorage.getItem('atl_push'); } catch (e) { return null; }
    }

    function setFlag(value) {
        try { localStorage.setItem('atl_push', value); } catch (e) { /* ignore */ }
    }

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function messaging() {
        if (!cfg || !cfg.configured) {
            return null;
        }
        if (typeof firebase === 'undefined') {
            log('Firebase SDK tidak termuat (script gstatic gagal dimuat?).');
            return null;
        }
        if (!firebase.apps.length) {
            firebase.initializeApp(cfg.config);
        }
        if (!state.messaging) {
            state.messaging = firebase.messaging();
        }
        return state.messaging;
    }

    function registration() {
        if (state.registration) {
            return Promise.resolve(state.registration);
        }
        return navigator.serviceWorker.register(cfg.swUrl, { scope: '/' }).then(function (reg) {
            return navigator.serviceWorker.ready.then(function () {
                state.registration = reg;
                return reg;
            });
        }).catch(function (err) {
            log('Gagal mendaftarkan service worker:', err && (err.name || err.message));
            throw err;
        });
    }

    function fetchToken() {
        var m = messaging();
        if (!m) {
            return Promise.resolve(null);
        }
        if (!cfg.vapidKey) {
            log('vapidKey kosong pada ATL_PUSH.');
            return Promise.resolve(null);
        }
        return registration().then(function (reg) {
            return m.getToken({ vapidKey: cfg.vapidKey, serviceWorkerRegistration: reg });
        }).catch(function (err) {
            log('getToken gagal:', err && (err.code || err.name), err && err.message);
            return null;
        });
    }

    function post(url, method, body) {
        return fetch(url, {
            method: method,
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body),
        }).then(function (res) {
            if (!res.ok) {
                var status = res.status;
                return res.text().then(function (text) {
                    log('Permintaan gagal', method, url, status, text.slice(0, 200));
                    throw new Error('HTTP ' + status);
                });
            }
            return res;
        });
    }

    function registerToken(token) {
        state.token = token;
        return post(cfg.tokenUrl, 'POST', { token: token, platform: 'web' }).then(function () {
            setFlag('1');
            return true;
        });
    }

    function enable() {
        if (!supported || !cfg.configured) {
            log('Push tidak didukung atau belum dikonfigurasi.');
            return Promise.resolve(false);
        }
        return Notification.requestPermission().then(function (permission) {
            if (permission !== 'granted') {
                log('Izin notifikasi tidak diberikan:', permission);
                return false;
            }
            return fetchToken().then(function (token) {
                if (!token) {
                    return false;
                }
                return registerToken(token).catch(function (err) {
                    log('Registrasi token ke server gagal:', err && err.message);
                    return false;
                });
            });
        });
    }

    function disable() {
        var tokenPromise = state.token ? Promise.resolve(state.token) : fetchToken().catch(function () { return null; });
        return tokenPromise.then(function (token) {
            var removal = token
                ? post(cfg.deleteUrl, 'DELETE', { token: token }).catch(function () { /* ignore */ })
                : Promise.resolve();

            return removal.then(function () {
                var m = state.messaging;
                setFlag('0');
                if (m) {
                    return m.deleteToken().catch(function () { /* ignore */ });
                }
            });
        }).then(function () {
            state.token = null;
            return true;
        });
    }

    function isEnabled() {
        return supported && !!cfg && cfg.configured && Notification.permission === 'granted' && flag() === '1';
    }

    function listen() {
        var m = messaging();
        if (!m) {
            return;
        }
        m.onMessage(function (payload) {
            var notification = (payload && payload.notification) || {};
            var data = (payload && payload.data) || {};
            var title = data.title || notification.title || 'Notifikasi baru';
            var body = data.body || notification.body || '';
            var url = data.url || (notification.click_action || '') || '/';

            if (window.AP && window.AP.showToast) {
                window.AP.showToast(data.body || title, data.level || 'info');
            }

            // Saat aplikasi terbuka, FCM tidak menampilkan notifikasi sistem
            // otomatis; tampilkan manual agar tetap sampai ke perangkat.
            if ('Notification' in window && Notification.permission === 'granted') {
                registration().then(function (reg) {
                    return reg.showNotification(title, {
                        body: body,
                        icon: '/assets/images/6.png',
                        badge: '/assets/images/6.png',
                        data: { url: url },
                        tag: data.order_number || data.reference || ('atl-' + Date.now()),
                    });
                }).catch(function (err) { log('showNotification gagal:', err && err.message); });
            }
        });
    }

    function bootstrap() {
        if (!supported || !cfg.configured) {
            return;
        }
        listen();

        // Sudah diizinkan sebelumnya -> pastikan token terdaftar (mis. setelah
        // re-login atau bila flag lokal hilang). Dibedakan dari pilihan "matikan"
        // eksplisit (flag '0') agar tidak mengaktifkan kembali tanpa diminta.
        if (Notification.permission === 'granted' && flag() !== '0') {
            fetchToken().then(function (token) {
                if (token) {
                    return registerToken(token);
                }
            }).catch(function (err) { log('Sinkronisasi token gagal:', err && err.message); });
        }
    }

    window.ATL_Push = {
        supported: supported,
        configured: !!(cfg && cfg.configured),
        enable: enable,
        disable: disable,
        isEnabled: isEnabled,
    };

    if (document.readyState !== 'loading') {
        bootstrap();
    } else {
        document.addEventListener('DOMContentLoaded', bootstrap);
    }
})();
