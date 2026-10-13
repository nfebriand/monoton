/**
 * MonOTOn - Kompres otomatis foto sebelum upload.
 *
 * Berlaku untuk semua <input type="file" name="fotos...">, kecuali:
 *  - input ber-atribut data-no-autocompress
 *  - form #formEviden (halaman tambah eviden sudah punya alur kompres sendiri)
 *
 * Cara kerja: saat user memilih foto (> 700 KB), foto di-resize (maks 1920px) &
 * di-encode JPEG lewat FotoCompress, lalu menggantikan isi input.files.
 * Hasil encode ulang tidak membawa EXIF, jadi orientasi sudah "dibakar" oleh
 * browser dan server tidak merotasi dua kali.
 *
 * Aman: bila browser tidak mendukung (DataTransfer/canvas) atau ada error,
 * foto asli tetap dikirim seperti biasa. Submit form ditahan sebentar sampai
 * kompresi selesai.
 */
(function () {
    'use strict';
    if (!window.FotoCompress || !window.DataTransfer || !window.File) return;

    var MIN_BYTES = 700 * 1024;
    var pending = new WeakMap();      // form -> jumlah kompresi berjalan
    var resubmit = new WeakMap();     // form -> submitter yang tertahan

    function isTarget(el) {
        if (!el || el.tagName !== 'INPUT' || el.type !== 'file') return false;
        if (!/^fotos/.test(el.name || '')) return false;
        if (el.hasAttribute('data-no-autocompress')) return false;
        if (el.form && el.form.id === 'formEviden') return false;
        return true;
    }

    function toast(msg) {
        var t = document.getElementById('fc-toast');
        if (!msg) { if (t) t.remove(); return; }
        if (!t) {
            t = document.createElement('div');
            t.id = 'fc-toast';
            t.style.cssText = 'position:fixed;left:50%;bottom:18px;transform:translateX(-50%);' +
                'background:rgba(0,0,0,.82);color:#fff;padding:.45rem .9rem;border-radius:20px;' +
                'font-size:.78rem;z-index:99999;pointer-events:none';
            document.body.appendChild(t);
        }
        t.textContent = msg;
    }

    function bump(form, d) {
        if (!form) return 0;
        var n = (pending.get(form) || 0) + d;
        pending.set(form, n);
        return n;
    }

    document.addEventListener('change', async function (e) {
        var input = e.target;
        if (!isTarget(input) || input._fcBusy) return;

        var files = Array.from(input.files || []);
        var perlu = files.some(function (f) { return /^image\/(jpeg|png|webp)$/.test(f.type) && f.size > MIN_BYTES; });
        if (!perlu) return;

        var form = input.form;
        input._fcBusy = true;
        bump(form, +1);
        toast('Mengompres foto…');
        try {
            var dt = new DataTransfer();
            for (var i = 0; i < files.length; i++) {
                var f = files[i];
                if (/^image\/(jpeg|png|webp)$/.test(f.type) && f.size > MIN_BYTES) {
                    try {
                        var nf = await FotoCompress.compressToFile(f);
                        dt.items.add(nf.size < f.size ? nf : f);
                    } catch (err) {
                        dt.items.add(f); // gagal decode (mis. HEIC) -> kirim asli
                    }
                } else {
                    dt.items.add(f);
                }
            }
            input.files = dt.files;
        } catch (err) {
            /* biarkan file asli */
        } finally {
            input._fcBusy = false;
            var sisa = bump(form, -1);
            if (sisa <= 0) {
                toast('');
                if (form && resubmit.has(form)) {
                    var sub = resubmit.get(form);
                    resubmit.delete(form);
                    if (form.requestSubmit) { try { form.requestSubmit(sub || undefined); return; } catch (x) {} }
                    form.submit();
                }
            }
        }
    }, true);

    // Tahan submit selama masih ada kompresi berjalan
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if ((pending.get(form) || 0) > 0) {
            e.preventDefault();
            e.stopImmediatePropagation();
            resubmit.set(form, e.submitter || null);
            toast('Menunggu kompresi foto selesai…');
        }
    }, true);
})();
