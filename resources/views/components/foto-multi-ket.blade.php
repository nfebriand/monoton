{{--
    Foto Multi + KETERANGAN per foto (dipakai Eviden & Catatan Dinas).

    Cara pakai:
      @include('components.foto-multi-ket', [
          'capName'  => 'foto_keterangan',        <- nama array keterangan foto BARU
          'existing' => $model->fotos ?? null,    <- (edit) foto yang sudah tersimpan
          'maxMb'    => 8,
      ])

    Nama field terkirim:
      fotos[]                       file foto baru (sudah dikompres), urutannya SAMA dengan keterangan
      {capName}[i]                  keterangan foto baru ke-i
      foto_keterangan_lama[ID]      keterangan foto lama (edit)
      hapus_foto[]                  ID foto lama yang dibuang (edit)

    Foto bisa dipilih berkali-kali (ditambah, bukan diganti), dari galeri atau kamera.
    File dimasukkan ke <input type=file name="fotos[]"> lewat DataTransfer sehingga form
    tetap dikirim secara NORMAL (kompatibel dengan antrean offline). Bila browser tidak
    mendukung DataTransfer, otomatis kembali ke pilihan native (memilih ulang = mengganti).
--}}
@php
    $capName  = $capName ?? 'foto_keterangan';
    $existing = $existing ?? collect();
    $maxMb    = $maxMb ?? 8;
@endphp

<div class="fmk" data-cap="{{ $capName }}">
    @if($existing->count())
    <div class="fw-semibold mb-2" style="font-size:.82rem">Foto Saat Ini ({{ $existing->count() }}):</div>
    <div class="fmk-grid mb-3">
        @foreach($existing as $foto)
        <div class="fmk-card" id="fmk-lama-{{ $foto->id }}">
            <div class="fmk-img">
                <img src="{{ $foto->thumb_url ?? $foto->url }}" alt="" loading="lazy"
                     onclick="bukaLightbox('{{ $foto->url }}', {{ json_encode((string) $foto->keterangan) }})"
                     onerror="this.onerror=null;this.src='{{ $foto->url }}'">
                <label class="fmk-hapus">
                    <input type="checkbox" name="hapus_foto[]" value="{{ $foto->id }}" class="form-check-input mt-0"
                           onchange="document.getElementById('fmk-lama-{{ $foto->id }}').style.opacity=this.checked?'.35':'1'">
                    Hapus foto ini
                </label>
            </div>
            <textarea name="foto_keterangan_lama[{{ $foto->id }}]" class="form-control" rows="2" maxlength="255"
                      placeholder="Keterangan foto (opsional)">{{ old('foto_keterangan_lama.'.$foto->id, $foto->keterangan) }}</textarea>
        </div>
        @endforeach
    </div>
    @endif

    <div class="d-flex gap-2 flex-wrap mb-2">
        <label class="btn btn-primary btn-sm mb-0" style="cursor:pointer">
            <i class="bi bi-images me-1"></i>Pilih dari Galeri
            <input type="file" class="d-none fmk-gallery" multiple accept="image/jpeg,image/png,image/jpg,image/webp">
        </label>
        <label class="btn btn-outline-secondary btn-sm mb-0 fmk-cam-btn" style="cursor:pointer">
            <i class="bi bi-camera me-1"></i>Ambil Foto
            <input type="file" class="d-none fmk-camera" accept="image/jpeg,image/png,image/jpg,image/webp" capture="environment">
        </label>
        <span class="fmk-status text-muted align-self-center" style="font-size:.75rem"></span>
    </div>

    {{-- Input yang benar-benar dikirim bersama form --}}
    <input type="file" name="fotos[]" class="d-none fmk-real" multiple>

    <div class="fmk-grid fmk-baru"></div>
    <div class="fmk-kosong text-muted" style="font-size:.78rem">Belum ada foto baru dipilih.</div>
    <div style="font-size:.72rem;color:#aaa;margin-top:4px">
        <i class="bi bi-info-circle me-1"></i>Max {{ $maxMb }}MB per foto &bull; JPG, PNG, WEBP &bull; dikompres otomatis &bull; Anda bisa memilih foto beberapa kali.
    </div>
</div>

@once
@push('styles')
<style>
.fmk-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:.75rem;}
.fmk-card{border:1px solid var(--border,#dee2e6);border-radius:8px;overflow:hidden;background:#fff;}
.fmk-img{position:relative;aspect-ratio:4/3;background:#f4f6f8;}
.fmk-img img{width:100%;height:100%;object-fit:cover;display:block;cursor:zoom-in;}
.fmk-kb{position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,.6);color:#fff;font-size:.58rem;padding:.15rem .3rem;text-align:center;}
.fmk-rm{position:absolute;top:4px;right:4px;background:rgba(220,53,69,.92);color:#fff;border:none;border-radius:50%;width:24px;height:24px;font-size:.8rem;cursor:pointer;line-height:1;}
.fmk-hapus{position:absolute;top:0;left:0;right:0;background:rgba(0,0,0,.65);color:#fff;font-size:.65rem;padding:.3rem;display:flex;align-items:center;gap:.4rem;cursor:pointer;margin:0;}
.fmk-card textarea{border:0;border-top:1px solid var(--border,#dee2e6);border-radius:0;font-size:.78rem;resize:vertical;}
</style>
@endpush

@push('scripts')
<script>
(function(){
    document.querySelectorAll('.fmk').forEach(function(root){
        const form   = root.closest('form');
        const real   = root.querySelector('.fmk-real');
        const gal    = root.querySelector('.fmk-gallery');
        const cam    = root.querySelector('.fmk-camera');
        const grid   = root.querySelector('.fmk-baru');
        const kosong = root.querySelector('.fmk-kosong');
        const status = root.querySelector('.fmk-status');
        const CAP    = root.dataset.cap;
        let items = [];     // {file, dataUrl, kb, ket}
        let busy = 0;
        let dtOk = true;
        try { new DataTransfer(); } catch (e) { dtOk = false; }

        if (!dtOk) {
            // Fallback: input galeri menjadi input yang dikirim (pilih ulang = ganti)
            real.removeAttribute('name');
            gal.setAttribute('name', 'fotos[]');
            cam.closest('label').style.display = 'none';
        }

        function syncReal(){
            if (!dtOk) return;
            const dt = new DataTransfer();
            items.forEach(it => dt.items.add(it.file));
            real.files = dt.files;
        }

        function render(){
            grid.innerHTML = '';
            items.forEach(function(it, i){
                const d = document.createElement('div');
                d.className = 'fmk-card';
                d.innerHTML = '<div class="fmk-img"><img alt=""><div class="fmk-kb"></div>' +
                    (dtOk ? '<button type="button" class="fmk-rm" title="Buang foto">&times;</button>' : '') + '</div>' +
                    '<textarea class="form-control" rows="2" maxlength="255" placeholder="Keterangan foto (opsional)"></textarea>';
                const img = d.querySelector('img');
                img.src = it.dataUrl;
                img.addEventListener('click', function(){ if (window.bukaLightbox) bukaLightbox(it.dataUrl, it.ket || ''); });
                d.querySelector('.fmk-kb').textContent = it.kb + ' KB';
                const rm = d.querySelector('.fmk-rm');
                if (rm) rm.addEventListener('click', function(){ items.splice(i, 1); syncReal(); render(); });
                const ta = d.querySelector('textarea');
                ta.name = CAP + '[' + i + ']';
                ta.value = it.ket || '';
                ta.addEventListener('input', function(){ it.ket = ta.value; });
                grid.appendChild(d);
            });
            kosong.style.display = items.length ? 'none' : '';
        }

        function baca(file){
            return new Promise(function(res){
                const fr = new FileReader();
                fr.onload = function(e){ res(e.target.result); };
                fr.onerror = function(){ res(''); };
                fr.readAsDataURL(file);
            });
        }

        async function tambah(fileList, dariFallback){
            const files = Array.from(fileList || []);
            if (!files.length) return;
            busy++; status.textContent = 'Memproses ' + files.length + ' foto...';
            try {
                if (!dtOk) items = [];
                for (const f of files) {
                    let file = f, dataUrl = '', kb = Math.round(f.size / 1024);
                    if (dtOk && typeof FotoCompress !== 'undefined') {
                        try {
                            const r = await FotoCompress.compress(f);
                            if (r && r.blob) {
                                file = new File([r.blob], f.name || ('foto_' + Date.now() + '.jpg'),
                                    {type: r.blob.type || 'image/jpeg'});
                                dataUrl = r.dataUrl || '';
                                kb = r.compressedKB || Math.round(file.size / 1024);
                            }
                        } catch (e) { /* pakai file asli */ }
                    }
                    if (!dataUrl) dataUrl = await baca(file);
                    items.push({file: file, dataUrl: dataUrl, kb: kb, ket: ''});
                }
                syncReal();
                render();
            } finally {
                busy--; status.textContent = busy ? status.textContent : '';
            }
        }

        gal.addEventListener('change', function(){ const l = Array.from(this.files); if (dtOk) this.value = ''; tambah(l); });
        cam.addEventListener('change', function(){ const l = Array.from(this.files); this.value = ''; tambah(l); });

        if (form) {
            form.addEventListener('submit', function(e){
                if (busy > 0) {
                    e.preventDefault(); e.stopImmediatePropagation();
                    alert('Foto masih diproses. Tunggu sebentar lalu tekan Simpan lagi.');
                }
            }, true);
        }
        render();
    });
})();
</script>
@endpush
@endonce
