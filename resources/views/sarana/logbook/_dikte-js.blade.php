@push('scripts')
<script>
(function(){
    const ta = document.getElementById('sl-catatan');
    const form = ta.closest('form');
    const mic = document.getElementById('sl-mic');
    const stat = document.getElementById('sl-stat');
    const interim = document.getElementById('sl-interim');
    const tanda = document.getElementById('sl-tanda');
    const cnt = document.getElementById('sl-count');
    function hitung(){
        const k = (ta.value.trim().match(/\S+/g) || []).length;
        cnt.textContent = ta.value.length.toLocaleString('id-ID') + ' karakter | ' + k.toLocaleString('id-ID') + ' kata';
        ta.style.height = 'auto';
        ta.style.height = Math.max(150, Math.min(ta.scrollHeight + 4, 600)) + 'px';
    }
    function simpanDraft(){}
    ta.addEventListener('input', hitung);
    hitung();
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
