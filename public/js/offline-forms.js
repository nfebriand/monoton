/**
 * MonOTOn OfflineForms - antrian offline umum untuk form tambah data.
 *
 * Form yang ber-atribut  data-offline-queue="<tipe>"  (+ data-offline-label="Nama tampil")
 * otomatis bisa diisi tanpa koneksi:
 *   - Saat Simpan ditekan dan server tidak terjangkau, isi form (termasuk foto) disimpan di
 *     IndexedDB perangkat, lalu form dikosongkan agar bisa lanjut mengisi data berikutnya.
 *   - Begitu koneksi kembali (event online, tiap 30 detik, saat halaman dibuka), data dikirim
 *     ulang berurutan ke alamat form aslinya. Tiap data membawa UUID (header X-Offline-Id) sehingga
 *     server menolak duplikat bila pengiriman diulang.
 *   - Penolakan validasi (422) ditandai "Gagal" beserta alasannya; gangguan jaringan/5xx dicoba lagi.
 *   - Sesi habis (401/419): data tetap aman di perangkat, kirim otomatis setelah login ulang.
 * Data hanya dikirim oleh akun yang membuatnya.
 */
(function () {
    'use strict';

    const STORE = 'pending_forms';
    const CIRI = ['tanggal', 'dicatat_pada', 'jam_mulai', 'shift', 'judul', 'jenis', 'alasan', 'uraian_pekerjaan'];
    const MAX_ULANG = 5;

    const uuid = () => (window.crypto && crypto.randomUUID)
        ? crypto.randomUUID()
        : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
            const r = Math.random() * 16 | 0;
            return (c === 'x' ? r : (r & 3 | 8)).toString(16);
        });

    const meta = n => document.querySelector('meta[name="' + n + '"]')?.content || '';
    const userId = () => meta('user-id');
    const toast = (m, t) => (window.SyncManager ? SyncManager.showToast(m, t) : null);

    /** Cek server terjangkau + sesi masih aktif. Return {kode, csrf, userId}; kode 0 = tak terjangkau. */
    async function cekServer() {
        const ctl = new AbortController();
        const timer = setTimeout(() => ctl.abort(), 4000);
        try {
            const r = await fetch('/api/sync/status', {
                cache: 'no-store', credentials: 'same-origin', signal: ctl.signal,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            let j = {};
            if (r.ok) { try { j = await r.json(); } catch (e) {} }
            return { kode: r.status, csrf: j.csrf || '', userId: j.user_id ? String(j.user_id) : '' };
        } catch (e) {
            return { kode: 0, csrf: '', userId: '' };
        } finally {
            clearTimeout(timer);
        }
    }

    function serialisasi(form, submitter) {
        const fd = new FormData(form);
        if (submitter && submitter.name) fd.append(submitter.name, submitter.value);
        const fields = [];
        let foto = 0;
        for (const [k, v] of fd.entries()) {
            if (k === '_token') continue;
            if (typeof File !== 'undefined' && v instanceof File) {
                if (!v.name && v.size === 0) continue;       // input file kosong
                fields.push([k, { blob: v, name: v.name || 'file' }]);
                foto++;
            } else {
                fields.push([k, String(v)]);
            }
        }
        const ringkas = [];
        for (const key of CIRI) {
            const f = fields.find(x => x[0] === key && typeof x[1] === 'string' && x[1] !== '');
            if (f) ringkas.push(key.replace(/_/g, ' ') + ': ' + f[1].slice(0, 60));
        }
        const banyakPemancar = new Set(fields.map(x => (x[0].match(/^pemancar\[(\d+)\]/) || [])[1]).filter(Boolean)).size;
        if (banyakPemancar) ringkas.push(banyakPemancar + ' pemancar');
        if (foto) ringkas.push(foto + ' foto');
        return { fields, ringkas: ringkas.join(' | ') };
    }

    function bangun(item, token) {
        const fd = new FormData();
        for (const [k, v] of item.fields) {
            if (k === '_token') continue;
            if (v && typeof v === 'object' && v.blob) fd.append(k, v.blob, v.name || 'file');
            else fd.append(k, v);
        }
        fd.append('_token', token);
        return fd;
    }

    function segarkanWaktu(form) {
        const d = new Date(), p = n => String(n).padStart(2, '0');
        const tgl = d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate());
        form.querySelectorAll('input[name="dicatat_pada"][type="datetime-local"]').forEach(el => { el.value = tgl + 'T' + p(d.getHours()) + ':' + p(d.getMinutes()); });
        form.querySelectorAll('input[name="tanggal"][type="date"]').forEach(el => { el.value = tgl; });
    }

    async function antrikan(form, submitter, alasan) {
        const { fields, ringkas } = serialisasi(form, submitter);
        const item = {
            id: uuid(),
            tipe: form.dataset.offlineQueue,
            label: form.dataset.offlineLabel || form.dataset.offlineQueue,
            action: form.getAttribute('action') || location.pathname,
            fields, ringkas,
            userId: userId(),
            alasan,
        };
        await OfflineDB.add(STORE, item);
        if (window.SyncManager) await SyncManager.updateBadge();
        if (form.dataset.offlineAfter === 'reload') {
            // Form kompleks (banyak komponen dinamis): muat ulang halaman agar kembali bersih
            setTimeout(() => location.reload(), 1200);
        } else {
            form.reset();
            segarkanWaktu(form);
        }
        form.dispatchEvent(new CustomEvent('offline:queued', { bubbles: true, detail: item }));
        toast('✅ ' + item.label + ' disimpan di perangkat. Dikirim otomatis saat koneksi tersambung.', 'success');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function pasang(form) {
        if (form._offlinePasang) return;
        form._offlinePasang = true;

        form.addEventListener('submit', async function (e) {
            if (e.defaultPrevented) return;                  // ditahan skrip lain (mis. kompres foto), nanti submit ulang
            if (form.dataset.offlineBypass === '1') { delete form.dataset.offlineBypass; return; }
            if (!window.indexedDB || !window.OfflineDB) return;   // tak didukung: kirim biasa
            if (form._offlineBusy) { e.preventDefault(); return; }

            e.preventDefault();
            if (!form.checkValidity()) { form.reportValidity(); return; }

            form._offlineBusy = true;
            const submitter = e.submitter || null;
            const tombol = Array.from(form.querySelectorAll('button[type="submit"],input[type="submit"]'));
            tombol.forEach(b => { b.disabled = true; });
            const pulih = () => { form._offlineBusy = false; tombol.forEach(b => { b.disabled = false; }); };

            try {
                let st = { kode: 0 };
                if (navigator.onLine) st = await cekServer();

                if (st.kode === 200) {                       // server terjangkau & login aktif: kirim biasa
                    form.dataset.offlineBypass = '1';
                    form._offlineBusy = false;
                    if (form.requestSubmit) form.requestSubmit(submitter || undefined);
                    else HTMLFormElement.prototype.submit.call(form);
                    return;
                }
                await antrikan(form, submitter, st.kode === 0 ? 'offline' : ('http-' + st.kode));
                if (st.kode === 401 || st.kode === 419) {
                    toast('⚠️ Sesi login berakhir. Data aman di perangkat; login ulang agar terkirim.', 'warning');
                }
            } catch (err) {
                console.error('[OfflineForms] gagal menyimpan antrian', err);
                alert('Gagal menyimpan data offline: ' + (err && err.message ? err.message : err) + '\nCoba lagi saat koneksi tersedia.');
            } finally {
                pulih();
            }
        });
    }

    /** Kirim semua antrian milik akun yang sedang login. Return jumlah berhasil. */
    async function syncSemua() {
        if (!window.OfflineDB || !navigator.onLine) return 0;
        const jalan = async () => {
            const st = await cekServer();
            if (st.kode !== 200) {
                if (st.kode === 401 || st.kode === 419) toast('⚠️ Login ulang untuk mengirim data offline.', 'warning');
                return 0;
            }
            const semua = (await OfflineDB.getAll(STORE, 'pending')).sort((a, b) => a.createdAt - b.createdAt);
            const milikku = semua.filter(x => !x.userId || !st.userId || String(x.userId) === st.userId);
            let berhasil = 0;
            for (const item of milikku) {
                let res;
                try {
                    res = await fetch(item.action, {
                        method: 'POST', body: bangun(item, st.csrf), credentials: 'same-origin', redirect: 'manual',
                        headers: {
                            'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': st.csrf, 'X-Offline-Id': item.id,
                        },
                    });
                } catch (e) {
                    break;                                    // jaringan putus: coba lagi nanti, urutan dijaga
                }
                let j = {};
                try { j = await res.clone().json(); } catch (e) {}

                if (res.ok && j.ok !== false) {
                    await OfflineDB.delete(STORE, item.id);
                    berhasil++;
                } else if (res.status === 401 || res.status === 419) {
                    toast('⚠️ Sesi berakhir. Login ulang agar sisa data terkirim.', 'warning');
                    break;
                } else if (res.status === 422 || res.status === 403 || res.status === 404 || res.status === 405) {
                    const pesan = j.message || (j.errors ? Object.values(j.errors).flat()[0] : '') || ('Ditolak server (HTTP ' + res.status + ')');
                    await OfflineDB.update(STORE, item.id, { status: 'failed', lastError: pesan, retries: (item.retries || 0) + 1 });
                } else {
                    const n = (item.retries || 0) + 1;
                    await OfflineDB.update(STORE, item.id, {
                        status: n >= MAX_ULANG ? 'failed' : 'pending', retries: n,
                        lastError: 'Server bermasalah (HTTP ' + (res.status || 'tanpa respons') + ')',
                    });
                    if (n < MAX_ULANG) break;                 // server sedang bermasalah: tunda semuanya
                }
            }
            return berhasil;
        };

        let hasil = 0;
        if (navigator.locks && navigator.locks.request) {
            await navigator.locks.request('monoton-offline-forms', { ifAvailable: true }, async lock => {
                if (lock) hasil = await jalan();
            });
        } else {
            hasil = await jalan();
        }
        if (hasil > 0) toast('✅ ' + hasil + ' data offline berhasil disinkronkan!', 'success');
        if (window.SyncManager) await SyncManager.updateBadge();
        return hasil;
    }

    async function ulangi(id) {
        await OfflineDB.update(STORE, id, { status: 'pending', retries: 0, lastError: '' });
        if (window.SyncManager) await SyncManager.updateBadge();
        return syncSemua();
    }

    function init() {
        document.querySelectorAll('form[data-offline-queue]').forEach(f => {
            pasang(f);
            // Halaman dibuka dari cache saat offline: isian waktu bawaan server sudah basi, segarkan
            if (!navigator.onLine) segarkanWaktu(f);
        });
    }
    document.addEventListener('DOMContentLoaded', init);

    window.OfflineForms = { STORE, init, pasang, syncSemua, ulangi, antrikan, cekServer };
})();
