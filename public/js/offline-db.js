/**
 * MonOTOn OfflineDB
 * IndexedDB wrapper untuk antrian data offline
 * Stores: pending_logs, pending_evidens
 */

const OfflineDB = {
    DB_NAME:    'monoton_offline',
    DB_VERSION: 1,
    db:         null,

    async open() {
        if (this.db) return this.db;
        return new Promise((resolve, reject) => {
            const req = indexedDB.open(this.DB_NAME, this.DB_VERSION);
            req.onupgradeneeded = e => {
                const db = e.target.result;
                // Store untuk log operasional pending
                if (!db.objectStoreNames.contains('pending_logs')) {
                    const ls = db.createObjectStore('pending_logs', { keyPath: 'localId', autoIncrement: true });
                    ls.createIndex('status', 'status', { unique: false });
                    ls.createIndex('createdAt', 'createdAt', { unique: false });
                }
                // Store untuk eviden pending
                if (!db.objectStoreNames.contains('pending_evidens')) {
                    const es = db.createObjectStore('pending_evidens', { keyPath: 'localId', autoIncrement: true });
                    es.createIndex('status', 'status', { unique: false });
                    es.createIndex('createdAt', 'createdAt', { unique: false });
                }
            };
            req.onsuccess = e => { this.db = e.target.result; resolve(this.db); };
            req.onerror   = e => reject(e.target.error);
        });
    },

    async add(store, data) {
        const db = await this.open();
        return new Promise((resolve, reject) => {
            const tx  = db.transaction(store, 'readwrite');
            const req = tx.objectStore(store).add({
                ...data,
                status:    'pending',
                createdAt: Date.now(),
                retries:   0,
            });
            req.onsuccess = e => resolve(e.target.result);
            req.onerror   = e => reject(e.target.error);
        });
    },

    async getAll(store, status = null) {
        const db = await this.open();
        return new Promise((resolve, reject) => {
            const tx      = db.transaction(store, 'readonly');
            const objStore = tx.objectStore(store);
            const req     = status
                ? objStore.index('status').getAll(status)
                : objStore.getAll();
            req.onsuccess = e => resolve(e.target.result);
            req.onerror   = e => reject(e.target.error);
        });
    },

    async update(store, localId, changes) {
        const db = await this.open();
        return new Promise((resolve, reject) => {
            const tx       = db.transaction(store, 'readwrite');
            const objStore = tx.objectStore(store);
            const getReq   = objStore.get(localId);
            getReq.onsuccess = e => {
                const item   = { ...e.target.result, ...changes };
                const putReq = objStore.put(item);
                putReq.onsuccess = () => resolve(item);
                putReq.onerror   = ev => reject(ev.target.error);
            };
            getReq.onerror = e => reject(e.target.error);
        });
    },

    async delete(store, localId) {
        const db = await this.open();
        return new Promise((resolve, reject) => {
            const tx  = db.transaction(store, 'readwrite');
            const req = tx.objectStore(store).delete(localId);
            req.onsuccess = () => resolve();
            req.onerror   = e => reject(e.target.error);
        });
    },

    async countPending() {
        const [logs, evidens] = await Promise.all([
            this.getAll('pending_logs', 'pending'),
            this.getAll('pending_evidens', 'pending'),
        ]);
        return logs.length + evidens.length;
    },

    async clear(store) {
        const db = await this.open();
        return new Promise((resolve, reject) => {
            const tx  = db.transaction(store, 'readwrite');
            const req = tx.objectStore(store).clear();
            req.onsuccess = () => resolve();
            req.onerror   = e => reject(e.target.error);
        });
    },
};

window.OfflineDB = OfflineDB;
