/**
 * MonOTOn Foto Compressor
 * - Resize proporsional hanya jika melebihi maxDim
 * - Kualitas adaptif: makin besar file → quality lebih ketat
 * - Format output: JPEG (kompresi terbaik untuk foto)
 * - Digunakan saat upload online MAUPUN sync offline
 */

const FotoCompress = {
    // Konfigurasi
    MAX_DIM:     1920,   // px — sisi terpanjang tidak melebihi ini
    MAX_SIZE_KB: 800,    // KB — target ukuran output
    MIN_QUALITY: 0.65,   // kualitas minimum (jangan terlalu rendah)
    MAX_QUALITY: 0.92,   // kualitas maksimum

    /**
     * Kompres satu File/Blob
     * @param {File|Blob} file
     * @param {Object} opts — override config
     * @returns {Promise<{blob: Blob, dataUrl: string, originalKB: number, compressedKB: number}>}
     */
    async compress(file, opts = {}) {
        const maxDim    = opts.maxDim     || this.MAX_DIM;
        const maxSizeKB = opts.maxSizeKB  || this.MAX_SIZE_KB;
        const minQ      = opts.minQuality || this.MIN_QUALITY;
        const maxQ      = opts.maxQuality || this.MAX_QUALITY;

        return new Promise((resolve, reject) => {
            const img = new Image();
            const url = URL.createObjectURL(file);

            img.onload = async () => {
                URL.revokeObjectURL(url);
                const originalKB = Math.round(file.size / 1024);

                // Hitung dimensi output (proporsional)
                let { width, height } = img;
                if (width > maxDim || height > maxDim) {
                    const ratio = Math.min(maxDim / width, maxDim / height);
                    width  = Math.round(width  * ratio);
                    height = Math.round(height * ratio);
                }

                const canvas = document.createElement('canvas');
                canvas.width  = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');

                // Smoothing untuk hasil visual terbaik
                ctx.imageSmoothingEnabled  = true;
                ctx.imageSmoothingQuality  = 'high';
                ctx.drawImage(img, 0, 0, width, height);

                // Tentukan quality awal berdasar ukuran asli
                let quality = originalKB > 3000 ? 0.75
                            : originalKB > 1500 ? 0.82
                            : originalKB > 800  ? 0.88
                            : maxQ;

                // Iterasi quality sampai target ukuran tercapai
                let blob = null;
                let iter = 0;
                do {
                    blob = await new Promise(res =>
                        canvas.toBlob(res, 'image/jpeg', quality)
                    );
                    const sizeKB = blob.size / 1024;
                    if (sizeKB <= maxSizeKB) break;
                    quality = Math.max(minQ, quality - 0.05);
                    iter++;
                } while (iter < 6);

                // Jika kompresi tidak membantu, kembalikan original
                if (blob.size >= file.size && width === img.width) {
                    const origBlob = new Blob([await file.arrayBuffer()], { type: file.type });
                    const origUrl  = await this._blobToDataUrl(origBlob);
                    return resolve({
                        blob: origBlob, dataUrl: origUrl,
                        originalKB, compressedKB: originalKB,
                        width: img.width, height: img.height,
                        quality: 1.0, skipped: true,
                    });
                }

                const dataUrl = await this._blobToDataUrl(blob);
                resolve({
                    blob, dataUrl,
                    originalKB,
                    compressedKB: Math.round(blob.size / 1024),
                    width, height, quality: Math.round(quality * 100),
                    skipped: false,
                });
            };

            img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('Gagal membaca gambar')); };
            img.src = url;
        });
    },

    /**
     * Kompres banyak file sekaligus
     * @param {FileList|File[]} files
     * @returns {Promise<Array>}
     */
    async compressAll(files, opts = {}, onProgress = null) {
        const results = [];
        const arr = Array.from(files);
        for (let i = 0; i < arr.length; i++) {
            const r = await this.compress(arr[i], opts);
            results.push({ ...r, name: arr[i].name, type: 'image/jpeg' });
            if (onProgress) onProgress(i + 1, arr.length, r);
        }
        return results;
    },

    /**
     * Kompres dan langsung kembalikan sebagai File object
     */
    async compressToFile(file, opts = {}) {
        const r = await this.compress(file, opts);
        const name = file.name.replace(/\.[^/.]+$/, '') + '.jpg';
        return new File([r.blob], name, { type: 'image/jpeg', lastModified: Date.now() });
    },

    _blobToDataUrl(blob) {
        return new Promise((res, rej) => {
            const r = new FileReader();
            r.onload  = e => res(e.target.result);
            r.onerror = rej;
            r.readAsDataURL(blob);
        });
    },

    /** Format info hasil kompresi untuk debug */
    info(result) {
        if (result.skipped) return `Tidak dikompres (sudah optimal) — ${result.originalKB} KB`;
        const saved = result.originalKB - result.compressedKB;
        const pct   = Math.round((saved / result.originalKB) * 100);
        return `${result.originalKB}KB → ${result.compressedKB}KB (-${pct}%) | ${result.width}×${result.height}px | Q${result.quality}%`;
    },
};

// Export untuk module atau global
if (typeof module !== 'undefined') module.exports = FotoCompress;
else window.FotoCompress = FotoCompress;
