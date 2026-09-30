/**
 * MonOTOn Precache Manager
 * Saat user membuka aplikasi (online), kirim daftar halaman ke Service Worker
 * untuk dicache supaya bisa diakses saat offline.
 */
const PrecacheManager = {
    // Daftar halaman utama berdasarkan menu sidebar
    PAGES: [
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
    ],

    async run() {
        if (!navigator.onLine) return;
        if (!('serviceWorker' in navigator)) return;

        const reg = await navigator.serviceWorker.ready;
        if (!reg.active) return;

        // Filter halaman admin-only jika user adalah operator
        const isAdmin = document.body.dataset.role === 'admin';
        const pages = this.PAGES.filter(p => {
            if (!isAdmin && ['/jadwal','/users','/setting'].includes(p)) return false;
            return true;
        });

        reg.active.postMessage({ type: 'PRECACHE_PAGES', pages });
        console.log('[MonOTOn] Precaching', pages.length, 'halaman untuk akses offline...');
    },
};

// Jalankan saat halaman dimuat dan saat online kembali
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => PrecacheManager.run(), 1500);
});
window.addEventListener('online', () => {
    setTimeout(() => PrecacheManager.run(), 1000);
});

window.PrecacheManager = PrecacheManager;
