{{-- Form Catatan Dinas (dipakai create & edit). $c = model (edit) atau null (create) --}}
@php
    $c = $c ?? null;
    $kat = old('kategori', $c->kategori ?? 'biasa');
    if (!isset(\App\Models\CatatanDinas::KATEGORI[$kat])) $kat = 'biasa';
    $jm = old('jam_mulai', $c && $c->jam_mulai ? substr($c->jam_mulai, 0, 5) : '');
    $js = old('jam_selesai', $c && $c->jam_selesai ? substr($c->jam_selesai, 0, 5) : '');
@endphp

@if($errors->any())
<div class="alert alert-danger py-2 mb-3"><ul class="mb-0 ps-3">
    @foreach($errors->all() as $e)<li style="font-size:.82rem">{{ $e }}</li>@endforeach
</ul></div>
@endif

<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informasi Dinas</h6></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                <input type="date" name="tanggal" class="form-control" required
                       value="{{ old('tanggal', $c ? $c->tanggal->toDateString() : now()->toDateString()) }}">
            </div>
            <div class="col-3 col-md-2">
                <label class="form-label">Jam Mulai</label>
                <input type="time" name="jam_mulai" class="form-control" value="{{ $jm }}">
            </div>
            <div class="col-3 col-md-2">
                <label class="form-label">Jam Selesai</label>
                <input type="time" name="jam_selesai" class="form-control" value="{{ $js }}">
            </div>
            <div class="col-12 col-md-5">
                <label class="form-label">Lokasi / Tempat</label>
                <input type="text" name="lokasi" class="form-control" maxlength="255"
                       placeholder="mis. Site Way Kanan, Studio 1, Ruang Genset"
                       value="{{ old('lokasi', $c->lokasi ?? ($lokasiDefault ?? '')) }}">
            </div>
            <div class="col-12">
                <label class="form-label d-block">Jenis Catatan <span class="text-danger">*</span></label>
                <div class="d-flex gap-2 flex-wrap">
                    @foreach(\App\Models\CatatanDinas::KATEGORI as $val => $label)
                    <label class="btn btn-sm" style="border:2px solid {{ \App\Models\CatatanDinas::KATEGORI_WARNA[$val] }};cursor:pointer">
                        <input type="radio" name="kategori" value="{{ $val }}" class="form-check-input me-1" {{ $kat === $val ? 'checked' : '' }} required>
                        {{ $label }}
                    </label>
                    @endforeach
                </div>
            </div>
            <div class="col-12">
                <label class="form-label">Judul Singkat <span class="text-danger">*</span></label>
                <input type="text" name="judul" class="form-control" maxlength="255" required
                       placeholder="mis. Listrik PLN padam 40 menit, genset otomatis menyala"
                       value="{{ old('judul', $c->judul ?? '') }}">
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex align-items-center flex-wrap gap-2">
        <h6 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Narasi <span class="text-danger">*</span></h6>
        <span class="ms-auto d-flex align-items-center gap-2">
            <span id="cd-count" class="text-muted" style="font-size:.72rem"></span>
        </span>
    </div>
    <div class="card-body">
        <div class="d-flex gap-2 flex-wrap align-items-center mb-2">
            <button type="button" id="cd-mic" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-mic-fill me-1"></i><span>Mulai Dikte (Suara)</span>
            </button>
            <label class="form-check mb-0" style="font-size:.74rem" title="Ucapkan: titik, koma, tanda tanya, baris baru, paragraf baru">
                <input type="checkbox" id="cd-tanda" class="form-check-input" checked>
                <span class="form-check-label">Ucapan &quot;titik / koma / baris baru&quot; jadi tanda baca</span>
            </label>
            <span id="cd-stat" class="text-muted" style="font-size:.74rem"></span>
        </div>
        <textarea name="narasi" id="cd-narasi" class="form-control" rows="12" required
                  style="font-size:.92rem;line-height:1.55;min-height:220px"
                  placeholder="Ceritakan kejadian / kondisi / situasi selama dinas: apa yang terjadi, kapan, di mana, tindakan yang diambil, dan hasil akhirnya. Boleh sangat panjang. Anda juga bisa mendikte dengan tombol mikrofon.">{{ old('narasi', $c->narasi ?? '') }}</textarea>
        <div id="cd-interim" class="mt-1" style="font-size:.8rem;color:#888;font-style:italic;min-height:1.1rem"></div>
        <div id="cd-draft" class="alert alert-warning py-2 mt-2 mb-0" style="display:none;font-size:.78rem">
            Ada draf narasi yang belum tersimpan dari perangkat ini.
            <button type="button" class="btn btn-sm btn-warning ms-2" id="cd-draft-pakai">Pulihkan</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="cd-draft-buang">Buang</button>
        </div>
        <div class="form-text" id="cd-bantuan">
            Dikte memakai pengenalan suara peramban (disarankan Chrome/Edge di HP atau PC) dan membutuhkan internet. Izinkan akses mikrofon saat diminta.
            Hasil dikte bisa diperbaiki dengan mengetik.
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-camera me-2"></i>Foto Dokumentasi <small class="text-muted">(opsional, banyak foto + keterangan)</small></h6></div>
    <div class="card-body">
        @include('components.foto-multi-ket', [
            'capName'  => $c ? 'foto_keterangan_baru' : 'foto_keterangan',
            'existing' => $c ? $c->fotos : null,
        ])
    </div>
</div>

@push('scripts')
<script>
(function(){
    const ta   = document.getElementById('cd-narasi');
    const form = ta.closest('form');
    const mic  = document.getElementById('cd-mic');
    const stat = document.getElementById('cd-stat');
    const interim = document.getElementById('cd-interim');
    const tanda = document.getElementById('cd-tanda');
    const cnt  = document.getElementById('cd-count');
    const draftKey = 'monoton-cd-draft-' + (document.querySelector('meta[name="user-id"]')?.content || '0') + '-{{ $c ? "edit-".$c->id : "baru" }}';
    const adalahBaru = {{ $c ? 'false' : 'true' }};

    // ---------- penghitung & auto-tinggi ----------
    function hitung(){
        const t = ta.value;
        const kata = (t.trim().match(/\S+/g) || []).length;
        cnt.textContent = t.length.toLocaleString('id-ID') + ' karakter | ' + kata.toLocaleString('id-ID') + ' kata';
        ta.style.height = 'auto';
        ta.style.height = Math.max(220, Math.min(ta.scrollHeight + 4, 700)) + 'px';
    }
    ta.addEventListener('input', function(){ hitung(); simpanDraft(); });
    hitung();

    // ---------- draf lokal (hanya catatan baru) ----------
    let tDraft = null;
    function simpanDraft(){
        if (!adalahBaru) return;
        clearTimeout(tDraft);
        tDraft = setTimeout(function(){
            try { if (ta.value.trim()) localStorage.setItem(draftKey, ta.value); else localStorage.removeItem(draftKey); } catch(e){}
        }, 600);
    }
    if (adalahBaru && !ta.value.trim()) {
        try {
            const d = localStorage.getItem(draftKey);
            if (d && d.trim()) {
                const box = document.getElementById('cd-draft');
                box.style.display = '';
                document.getElementById('cd-draft-pakai').onclick = function(){ ta.value = d; hitung(); box.style.display = 'none'; };
                document.getElementById('cd-draft-buang').onclick = function(){ try{localStorage.removeItem(draftKey);}catch(e){} box.style.display = 'none'; };
            }
        } catch(e){}
    }
    form.addEventListener('submit', function(){ try { localStorage.removeItem(draftKey); } catch(e){} });

    // ---------- dikte suara (Web Speech API) ----------
    const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SR) {
        mic.disabled = true;
        mic.title = 'Peramban ini tidak mendukung dikte';
        stat.textContent = 'Dikte tidak didukung peramban ini. Gunakan Chrome/Edge, atau tombol mikrofon pada keyboard HP.';
        return;
    }

    let rec = null, buf = '', aktif = false, kursor = ta.value.length, fatal = false;
    const lbl = mic.querySelector('span');

    ['keyup','click','input','focus','blur'].forEach(function(ev){
        ta.addEventListener(ev, function(){ if (typeof ta.selectionStart === 'number') kursor = ta.selectionStart; });
    });

    function tandaBaca(t){
        if (!tanda.checked) return t;
        return t
            .replace(/\s*\b(paragraf baru)\b\s*/gi, '\n\n')
            .replace(/\s*\b(baris baru|enter)\b\s*/gi, '\n')
            .replace(/\s*\b(tanda tanya)\b/gi, '?')
            .replace(/\s*\b(tanda seru)\b/gi, '!')
            .replace(/\s*\b(titik dua)\b/gi, ':')
            .replace(/\s*\b(titik koma)\b/gi, ';')
            .replace(/\s*\b(titik)\b/gi, '.')
            .replace(/\s*\b(koma)\b/gi, ',');
    }

    function sisip(teks){
        teks = tandaBaca(teks.trim());
        if (!teks) return;
        const v = ta.value;
        const pos = Math.min(kursor, v.length);
        const sebelum = v.slice(0, pos), sesudah = v.slice(pos);
        let awal = '';
        if (sebelum && !/[\s]$/.test(sebelum) && !/^[\.,;:!?\n]/.test(teks)) awal = ' ';
        // huruf kapital di awal kalimat
        const mulaiKalimat = !sebelum.trim() || /[\.\!\?]\s*$/.test(sebelum) || /\n\s*$/.test(sebelum);
        if (mulaiKalimat) teks = teks.charAt(0).toUpperCase() + teks.slice(1);
        const tambah = awal + teks;
        ta.value = sebelum + tambah + sesudah;
        kursor = pos + tambah.length;
        ta.setSelectionRange(kursor, kursor);
        hitung(); simpanDraft();
        ta.scrollTop = ta.scrollHeight;
    }

    function komit(){ const t = buf; buf = ''; interim.textContent = ''; if (t) sisip(t); }

    function setTombol(on){
        mic.classList.toggle('btn-danger', on);
        mic.classList.toggle('btn-outline-danger', !on);
        lbl.textContent = on ? 'Berhenti Dikte' : 'Mulai Dikte (Suara)';
    }

    function mulai(){
        if (!navigator.onLine) { stat.textContent = 'Tidak ada internet: dikte butuh koneksi. Ketik manual, atau coba lagi saat online.'; return; }
        fatal = false;
        rec = new SR();
        rec.lang = 'id-ID';
        // Satu ucapan per sesi (continuous=false) + dimulai ulang otomatis: menghindari bug Chrome Android
        // yang mengulang-ulang hasil sementara saat continuous=true. Teks ditulis SEKALI per ucapan.
        rec.continuous = false;
        rec.interimResults = true;
        rec.maxAlternatives = 1;
        rec.onstart = function(){ stat.textContent = 'Mendengarkan... silakan bicara.'; };
        rec.onresult = function(e){
            let t = '';
            for (let i = 0; i < e.results.length; i++) t += e.results[i][0].transcript + ' ';
            buf = t.trim();
            interim.textContent = buf ? ('... ' + buf) : '';
            if (e.results[e.results.length - 1].isFinal) komit();
        };
        rec.onerror = function(e){
            if (e.error === 'not-allowed' || e.error === 'service-not-allowed') {
                fatal = true; stat.textContent = 'Akses mikrofon ditolak. Izinkan mikrofon pada pengaturan situs peramban.';
            } else if (e.error === 'network') {
                fatal = true; stat.textContent = 'Layanan suara tidak terjangkau (periksa internet).';
            } else if (e.error === 'audio-capture') {
                fatal = true; stat.textContent = 'Mikrofon tidak ditemukan.';
            } else if (e.error === 'language-not-supported') {
                fatal = true; stat.textContent = 'Bahasa Indonesia tidak didukung perangkat ini.';
            }
            // no-speech / aborted: dibiarkan, akan dimulai ulang di onend
        };
        rec.onend = function(){
            komit();
            if (aktif && !fatal) { setTimeout(function(){ if (aktif) { try { rec.start(); } catch(e){} } }, 250); return; }
            aktif = false; setTombol(false);
            if (!fatal) stat.textContent = 'Dikte berhenti.';
        };
        try { rec.start(); aktif = true; setTombol(true); } catch(e){ stat.textContent = 'Gagal memulai dikte.'; }
    }

    function henti(){ aktif = false; try { rec && rec.stop(); } catch(e){} setTombol(false); }

    mic.addEventListener('click', function(){
        if (aktif) { henti(); return; }
        if (typeof ta.selectionStart === 'number' && document.activeElement === ta) kursor = ta.selectionStart;
        else kursor = ta.value.length;
        mulai();
    });
    window.addEventListener('pagehide', henti);
    form.addEventListener('submit', henti);
})();
</script>
@endpush
