// MonOTOn Service Worker — v2 (full page caching for offline)
const CACHE_VERSION = 'monoton-v7';
const STATIC_CACHE  = `${CACHE_VERSION}-static`;
const PAGES_CACHE   = `${CACHE_VERSION}-pages`;
const ASSET_CACHE   = `${CACHE_VERSION}-assets`;

// Asset statis (CSS/JS CDN + lokal)
const STATIC_ASSETS = [
    '/manifest.json',
    '/offline',
    'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js',
    'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
    '/js/foto-compress.js',
    '/js/offline-db.js',
    '/js/offline-sync.js',
];

// Halaman utama aplikasi yang di-precache agar bisa dibuka offline
const APP_PAGES = [
    '/',
    '/dashboard',
    '/pemancar',
    '/operasional',
    '/operasional/create',
    '/eviden',
    '/eviden/create',
    '/laporan',
    '/laporan/suhu',
    '/jadwal',
    '/users',
    '/setting',
    '/sync',
    '/password/change',
];

// ── INSTALL ──
self.addEventListener('install', event => {
    console.log('[MonOTOn SW] Installing v2...');
    event.waitUntil(
        Promise.all([
            caches.open(STATIC_CACHE).then(cache =>
                Promise.allSettled(STATIC_ASSETS.map(url =>
                    cache.add(url).catch(e => console.warn('[SW] static fail:', url))
                ))
            ),
            caches.open(PAGES_CACHE).then(cache =>
                Promise.allSettled(APP_PAGES.map(url =>
                    fetch(url, { credentials: 'same-origin' })
                        .then(res => res.ok ? cache.put(url, res) : null)
                        .catch(() => null)
                ))
            ),
        ]).then(() => self.skipWaiting())
    );
});

// ── ACTIVATE ──
self.addEventListener('activate', event => {
    console.log('[MonOTOn SW] Activating v2...');
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys.filter(k => !k.startsWith(CACHE_VERSION)).map(k => caches.delete(k))
            )
        ).then(() => self.clients.claim())
    );
});

// ── FETCH ──
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    if (request.method !== 'GET') return;

    // CDN assets — cache-first
    if (url.hostname.includes('cdn.jsdelivr.net') ||
        url.hostname.includes('fonts.googleapis.com') ||
        url.hostname.includes('fonts.gstatic.com')) {
        event.respondWith(
            caches.match(request).then(cached => cached || fetch(request).then(res => {
                const clone = res.clone();
                caches.open(ASSET_CACHE).then(c => c.put(request, clone));
                return res;
            }))
        );
        return;
    }

    if (url.origin !== location.origin) return;

    // Halaman navigasi (HTML) — network-first, fallback ke cache, lalu offline page
    if (request.mode === 'navigate' || request.headers.get('accept')?.includes('text/html')) {
        event.respondWith(
            fetch(request)
                .then(res => {
                    if (res.ok) {
                        const clone = res.clone();
                        caches.open(PAGES_CACHE).then(c => c.put(request, clone));
                    }
                    return res;
                })
                .catch(() =>
                    caches.match(request).then(cached => cached || caches.match('/offline'))
                )
        );
        return;
    }

    // Asset lokal lain (JS/CSS/gambar) — cache-first dengan update background
    event.respondWith(
        caches.match(request).then(cached => {
            const fetchPromise = fetch(request).then(res => {
                if (res.ok) {
                    const clone = res.clone();
                    caches.open(ASSET_CACHE).then(c => c.put(request, clone));
                }
                return res;
            }).catch(() => cached);
            return cached || fetchPromise;
        })
    );
});

// ── MESSAGE: trigger precache manual dari halaman ──
self.addEventListener('message', event => {
    if (event.data?.type === 'PRECACHE_PAGES') {
        const pages = event.data.pages || [];
        caches.open(PAGES_CACHE).then(cache => {
            pages.forEach(url => {
                fetch(url, { credentials: 'same-origin' })
                    .then(res => res.ok && cache.put(url, res))
                    .catch(() => {});
            });
        });
    }
});

// Push notification (tetap dipertahankan)
self.addEventListener('push', event => {
    const data = event.data?.json() || {};
    const options = {
        body: data.body || 'Ada notifikasi baru dari MonOTOn',
        icon: '/pwa/icon-192.png',
        badge: '/pwa/icon-96.png',
        vibrate: [200,100,200],
        data: { url: data.url || '/' },
    };
    event.waitUntil(self.registration.showNotification(data.title || 'MonOTOn', options));
});
self.addEventListener('notificationclick', event => {
    event.notification.close();
    event.waitUntil(clients.openWindow(event.notification.data?.url || '/'));
});
