/**
 * MonOTOn Offline Sync Manager
 * - Mendeteksi status online/offline
 * - Sync data pending ke server saat online
 * - Update badge counter di topbar
 */

const SyncManager = {
    SYNC_INTERVAL: 30000, // cek tiap 30 detik saat online
    _timer: null,
    _syncing: false,

    /** Inisialisasi — panggil saat DOMContentLoaded */
    async init() {
        await this.updateBadge();
        this.bindEvents();
        if (navigator.onLine) this.scheduleSync();
    },

    bindEvents() {
        window.addEventListener('online', async () => {
            console.log('[MonOTOn Sync] Kembali online — mulai sync...');
            this.showToast('🌐 Koneksi tersambung! Menyinkronkan data...', 'info');
            await this.syncAll();
            this.scheduleSync();
        });
        window.addEventListener('offline', () => {
            console.log('[MonOTOn Sync] Offline mode aktif');
            this.showToast('📵 Mode Offline — data disimpan lokal', 'warning');
            clearInterval(this._timer);
        });
    },

    scheduleSync() {
        clearInterval(this._timer);
        this._timer = setInterval(() => {
            if (navigator.onLine && !this._syncing) this.syncAll();
        }, this.SYNC_INTERVAL);
    },

    /** Sync semua data pending */
    async syncAll() {
        if (this._syncing) return;
        this._syncing = true;
        let totalSynced = 0;

        try {
            const logs    = await OfflineDB.getAll('pending_logs', 'pending');
            const evidens = await OfflineDB.getAll('pending_evidens', 'pending');

            for (const log of logs) {
                try {
                    await this.syncLog(log);
                    totalSynced++;
                } catch (e) {
                    console.error('[Sync] Gagal sync log:', e);
                    await OfflineDB.update('pending_logs', log.localId, {
                        status: log.retries >= 3 ? 'failed' : 'pending',
                        retries: (log.retries || 0) + 1,
                        lastError: e.message,
                    });
                }
            }

            for (const eviden of evidens) {
                try {
                    await this.syncEviden(eviden);
                    totalSynced++;
                } catch (e) {
                    console.error('[Sync] Gagal sync eviden:', e);
                    await OfflineDB.update('pending_evidens', eviden.localId, {
                        status: eviden.retries >= 3 ? 'failed' : 'pending',
                        retries: (eviden.retries || 0) + 1,
                        lastError: e.message,
                    });
                }
            }

            if (totalSynced > 0) {
                this.showToast(`✅ ${totalSynced} data berhasil disinkronkan!`, 'success');
            }
        } finally {
            this._syncing = false;
            await this.updateBadge();
        }
    },

    /** Sync 1 log operasional */
    async syncLog(log) {
        const formData = new FormData();
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        // Lampirkan semua field data
        const fields = log.data || {};
        Object.keys(fields).forEach(k => {
            const val = fields[k];
            if (Array.isArray(val)) {
                val.forEach((v, i) => {
                    if (typeof v === 'object') {
                        Object.keys(v).forEach(sk => formData.append(`${k}[${i}][${sk}]`, v[sk] ?? ''));
                    } else {
                        formData.append(`${k}[${i}]`, v ?? '');
                    }
                });
            } else {
                formData.append(k, val ?? '');
            }
        });

        formData.append('_token', csrfToken);
        formData.append('_offline_sync', '1');

        const res = await fetch('/api/sync/log', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        await OfflineDB.update('pending_logs', log.localId, { status: 'synced', syncedAt: Date.now() });
        // Hapus setelah berhasil
        setTimeout(() => OfflineDB.delete('pending_logs', log.localId), 3000);
    },

    /** Sync 1 eviden + foto */
    async syncEviden(eviden) {
        const formData  = new FormData();
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const fields    = eviden.data || {};

        Object.keys(fields).forEach(k => {
            if (k === 'operator_ids' && Array.isArray(fields[k])) {
                fields[k].forEach(v => formData.append('operator_ids[]', v));
            } else {
                formData.append(k, fields[k] ?? '');
            }
        });

        // Upload foto (Blob tersimpan di IndexedDB)
        if (eviden.fotos && eviden.fotos.length > 0) {
            for (let i = 0; i < eviden.fotos.length; i++) {
                const fotoData = eviden.fotos[i];
                let blob;
                if (fotoData instanceof Blob) {
                    blob = fotoData;
                } else if (fotoData.blob) {
                    blob = fotoData.blob;
                } else if (typeof fotoData === 'string' && fotoData.startsWith('data:')) {
                    blob = await fetch(fotoData).then(r => r.blob());
                }
                if (blob) {
                    formData.append('fotos[]', blob, `foto_${i}.jpg`);
                }
                if (fotoData.keterangan) {
                    formData.append(`foto_keterangan[${i}]`, fotoData.keterangan);
                }
            }
        }

        formData.append('_token', csrfToken);
        formData.append('_offline_sync', '1');

        const res = await fetch('/api/sync/eviden', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        await OfflineDB.update('pending_evidens', eviden.localId, { status: 'synced', syncedAt: Date.now() });
        setTimeout(() => OfflineDB.delete('pending_evidens', eviden.localId), 3000);
    },

    /** Update badge counter di topbar */
    async updateBadge() {
        const count   = await OfflineDB.countPending();
        const badge   = document.getElementById('sync-badge');
        const wrapper = document.getElementById('sync-badge-wrap');
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count;
            wrapper?.classList.remove('d-none');
        } else {
            wrapper?.classList.add('d-none');
        }
        // Update page title jika ada pending
        const baseTitle = document.title.replace(/^\(\d+\)\s/, '');
        document.title  = count > 0 ? `(${count}) ${baseTitle}` : baseTitle;
    },

    /** Toast notification */
    showToast(message, type = 'info') {
        const colors = {
            success: '#10ac84', warning: '#ff9f43',
            danger: '#ee5a24', info: '#0a3d62',
        };
        const toast = document.createElement('div');
        toast.style.cssText = `
            position:fixed;bottom:50px;left:50%;transform:translateX(-50%);
            background:${colors[type]||colors.info};color:#fff;
            padding:.6rem 1.2rem;border-radius:20px;font-size:.8rem;font-weight:600;
            z-index:99999;box-shadow:0 4px 15px rgba(0,0,0,.2);
            animation:fadeInUp .3s ease;white-space:nowrap;
        `;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    },
};

// CSS untuk animasi toast
const styleEl = document.createElement('style');
styleEl.textContent = `
    @keyframes fadeInUp {
        from { opacity:0; transform:translateX(-50%) translateY(20px); }
        to   { opacity:1; transform:translateX(-50%) translateY(0); }
    }
`;
document.head.appendChild(styleEl);

window.SyncManager = SyncManager;
