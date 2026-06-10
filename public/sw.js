// MonOTOn Service Worker
const CACHE_NAME = 'monoton-v1.0.0';
const STATIC_CACHE = 'monoton-static-v1';

// Asset yang di-cache saat install
const STATIC_ASSETS = [
    '/',
    '/dashboard',
    '/offline',
    '/manifest.json',
    'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js',
];

// ── INSTALL ──
self.addEventListener('install', event => {
    console.log('[MonOTOn SW] Installing...');
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then(cache => {
                return Promise.allSettled(
                    STATIC_ASSETS.map(url =>
                        cache.add(url).catch(e => console.warn('[SW] Failed to cache:', url, e))
                    )
                );
            })
            .then(() => self.skipWaiting())
    );
});

// ── ACTIVATE ──
self.addEventListener('activate', event => {
    console.log('[MonOTOn SW] Activating...');
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys
                    .filter(key => key !== CACHE_NAME && key !== STATIC_CACHE)
                    .map(key => caches.delete(key))
            )
        ).then(() => self.clients.claim())
    );
});

// ── FETCH ──
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip: POST, non-GET, cross-origin non-CDN
    if (request.method !== 'GET') return;
    if (url.origin !== location.origin &&
        !url.hostname.includes('cdn.jsdelivr.net') &&
        !url.hostname.includes('fonts.googleapis.com') &&
        !url.hostname.includes('fonts.gstatic.com') &&
        !url.hostname.includes('unpkg.com')) {
        return;
    }

    // Strategi: Network first, fallback cache, fallback offline
    event.respondWith(
        fetch(request)
            .then(response => {
                // Cache response yang valid
                if (response && response.status === 200 && response.type === 'basic') {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(request, responseClone));
                }
                return response;
            })
            .catch(() => {
                // Offline fallback
                return caches.match(request).then(cached => {
                    if (cached) return cached;
                    // Jika HTML request dan tidak ada cache, tampilkan halaman offline
                    if (request.headers.get('accept')?.includes('text/html')) {
                        return caches.match('/offline');
                    }
                });
            })
    );
});

// ── PUSH NOTIFICATION (untuk fitur masa depan) ──
self.addEventListener('push', event => {
    const data = event.data?.json() || {};
    const options = {
        body: data.body || 'Ada notifikasi baru dari MonOTOn',
        icon: '/pwa/icon-192.png',
        badge: '/pwa/icon-96.png',
        vibrate: [200, 100, 200],
        data: { url: data.url || '/' },
        actions: [
            { action: 'open', title: 'Buka Aplikasi' },
            { action: 'close', title: 'Tutup' }
        ]
    };
    event.waitUntil(
        self.registration.showNotification(data.title || 'MonOTOn', options)
    );
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    if (event.action === 'open' || !event.action) {
        event.waitUntil(clients.openWindow(event.notification.data?.url || '/'));
    }
});
