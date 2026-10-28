@extends('layouts.app')
@section('title','Catat Log')
@section('page-title','Catat Log Operasional')

@section('content')
@php $user = auth()->user(); @endphp

{{-- Offline notice --}}
<div id="offline-notice" class="alert alert-warning d-flex align-items-center gap-2 py-2 mb-3" style="display:none!important">
    <i class="bi bi-cloud-slash fs-5 flex-shrink-0"></i>
    <div>
        <strong>Mode Offline</strong> &#x2014; Data akan disimpan lokal dan disinkronkan otomatis saat online.
    </div>
</div>

@if($lokasiTidakDiset)
<div class="alert alert-danger"><strong>Lokasi dinas belum diset!</strong> Hubungi administrator.</div>
@elseif($pemancars->isEmpty())
<div class="alert alert-warning"><strong>Tidak ada pemancar di lokasi {{ $user->lokasi_dinas }}.</strong></div>
@else

<form action="{{ route('operasional.store') }}" method="POST" id="formLog" enctype="multipart/form-data" data-offline-queue="operasional" data-offline-label="Log Operasional" data-offline-after="reload">
@csrf

{{-- Parameter Umum --}}
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-sliders me-2"></i>Parameter Umum</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12 col-sm-6 col-md-4">
                <label class="form-label">Waktu Pencatatan <span class="text-danger">*</span></label>
                <input type="datetime-local" name="dicatat_pada" id="dicatat_pada" class="form-control"
                       value="{{ old('dicatat_pada', now()->format('Y-m-d\TH:i')) }}" required>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">Suhu Ruangan (&#xB0;C) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="number" name="suhu_ruangan" id="suhu_ruangan" class="form-control mono"
                           min="0" max="60" step="0.1" value="{{ old('suhu_ruangan', $suhuTerakhir) }}" required>
                    <span class="input-group-text">&#xB0;C</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">Kelembaban (%)</label>
                <div class="input-group">
                    <input type="number" name="kelembaban" id="kelembaban" class="form-control mono"
                           step="0.1" min="0" max="100" value="{{ old('kelembaban', $kelembabanTerakhir) }}">
                    <span class="input-group-text">%</span>
                </div>
            </div>
            @if($shiftAktif)
            <div class="col-12 col-md-2 d-flex align-items-end">
                <div class="w-100 p-2 rounded text-center" style="background:#e8f4fd;border:1px solid #b3d7f0">
                    <div style="font-size:.6rem;color:#0a3d62;font-weight:600">SHIFT AKTIF</div>
                    <div class="fw-bold" style="color:#0a3d62">Shift {{ $shiftAktif->shift }}</div>
                    <div class="mono" style="font-size:.65rem;color:#555">{{ $shiftAktif->jam_mulai }}&#x2013;{{ $shiftAktif->jam_selesai }}</div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Info lokasi --}}
<div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
    <div class="px-3 py-2 rounded d-flex align-items-center gap-2"
         style="background:#f0fff4;border:1px solid #c8e6c9;font-size:.8rem">
        <i class="bi bi-geo-alt-fill text-success"></i>
        @if($user->isAdmin())<span>Administrator &#x2014; Semua pemancar</span>
        @else<span>Lokasi: <strong>{{ $user->lokasi_dinas }}</strong></span>@endif
    </div>
    <span class="badge bg-primary ms-auto">{{ $pemancars->count() }} Pemancar</span>
</div>

{{-- Accordion Pemancar --}}
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-broadcast-pin me-2 text-primary"></i>Data Per Pemancar</span>
        <span class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-primary" id="btnIsiSemuaTerakhir" style="display:none"
                title="Salin nilai log ON terakhir ke semua kolom yang masih kosong, lalu periksa/ubah sesuai pembacaan sekarang">
            <i class="bi bi-clock-history me-1"></i>Isi Semua Dari Log Terakhir
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAll()">
            <i class="bi bi-arrows-expand me-1"></i><span id="toggleLabel">Tutup Semua</span>
        </button>
        </span>
    </div>
    <div class="card-body p-0">
        @foreach($pemancars as $idx => $pemancar)
        @php $sudah = in_array($pemancar->id, $sudahDicatat); $maxPower = $pemancar->kapasitas_output_final; @endphp
        <div class="pemancar-block {{ $sudah?'sudah':'belum' }}">
            <div class="pemancar-hdr" onclick="toggleBlk({{ $pemancar->id }})">
                <div class="d-flex align-items-center gap-2 flex-wrap" style="min-width:0">
                    <i class="bi bi-chevron-down ti" id="ti-{{ $pemancar->id }}"></i>
                    <span class="badge {{ $pemancar->modulasi==='FM'?'bg-primary':'bg-warning text-dark' }}">{{ $pemancar->modulasi }}</span>
                    <strong class="text-truncate" style="font-size:.88rem">{{ $pemancar->nama_stasiun }}</strong>
                    <small class="text-muted d-none d-sm-inline">{{ $pemancar->merk }} {{ $pemancar->tipe_unit }}</small>
                    @if($pemancar->lokasi)<span class="badge bg-secondary" style="font-size:.62rem">{{ $pemancar->lokasi }}</span>@endif
                    <span class="mono text-muted" style="font-size:.72rem">{{ number_format($maxPower,0) }} W</span>
                </div>
                <span class="badge flex-shrink-0 {{ $sudah?'bg-success':'bg-warning text-dark' }}">
                    <i class="bi bi-{{ $sudah?'check-circle':'exclamation-circle' }} me-1"></i>{{ $sudah?'Sudah':'Belum' }}
                </span>
            </div>
            <div class="pemancar-bdy open" id="bdy-{{ $pemancar->id }}">
                <input type="hidden" name="pemancar[{{ $idx }}][id]" value="{{ $pemancar->id }}">

                <div class="d-flex align-items-center gap-2 mb-3 px-2 py-1" style="background:#fff7e6;border:1px solid #ffe4a0;border-radius:6px">
                    <div class="form-check form-switch m-0 flex-shrink-0" style="min-height:0">
                        <input class="form-check-input" type="checkbox" role="switch"
                               id="status-off-{{ $idx }}" style="cursor:pointer"
                               onchange="toggleStatusOff({{ $idx }}, this.checked)">
                    </div>
                    <label for="status-off-{{ $idx }}" class="mb-0" style="font-size:.72rem;line-height:1.25;cursor:pointer">
                        Pemancar sedang <strong>OFF</strong> <span class="text-muted">(rusak/bergantian dengan cadangan)</span>
                    </label>
                    <input type="hidden" name="pemancar[{{ $idx }}][status]" id="status-val-{{ $idx }}" value="on">
                </div>

                <div class="param-fields" id="fields-{{ $idx }}">
                <div class="param-lbl mb-2">&#x26A1; Output Power</div>
                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <label class="form-label">Final PA</label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="pemancar[{{ $idx }}][output_final_pa]"
                                   class="form-control mono out-final" data-idx="{{ $idx }}" data-max="{{ $maxPower }}"
                                   step="0.01" min="0">
                            <span class="input-group-text">W</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <label class="form-label">Driver</label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="pemancar[{{ $idx }}][output_driver]" class="form-control mono" step="0.01" min="0">
                            <span class="input-group-text">W</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <label class="form-label">Exciter</label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="pemancar[{{ $idx }}][output_exciter]" class="form-control mono" step="0.01" min="0">
                            <span class="input-group-text">W</span>
                        </div>
                    </div>
                </div>
                <div class="param-lbl mb-2">&#x21A9; Reflected & Reject</div>
                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <label class="form-label">Reflect Final</label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="pemancar[{{ $idx }}][reflect_final]"
                                   class="form-control mono ref-final" data-idx="{{ $idx }}" step="0.01" min="0">
                            <span class="input-group-text">W</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <label class="form-label">Reject Final</label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="pemancar[{{ $idx }}][reject_final]" class="form-control mono" step="0.01" min="0">
                            <span class="input-group-text">W</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <label class="form-label">Suhu Pemancar</label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="pemancar[{{ $idx }}][suhu_pemancar]" class="form-control mono" min="0" max="150" step="0.1">
                            <span class="input-group-text">&#xB0;C</span>
                        </div>
                    </div>
                </div>
                <div class="vswr-bar mb-3">
                    <i class="bi bi-calculator text-primary me-1"></i>
                    <span style="font-size:.73rem">VSWR Final:</span>
                    <strong class="mono ms-1 vswr-val" id="vv-{{ $idx }}" style="font-size:.9rem">&#x2014;</strong>
                    <span class="vswr-st ms-2" id="vs-{{ $idx }}" style="font-size:.73rem"></span>
                    <span class="text-muted ms-auto" style="font-size:.7rem">RL: <span id="rl-{{ $idx }}">&#x2014;</span> dB</span>
                </div>
                </div> {{-- /param-fields --}}
                <textarea name="pemancar[{{ $idx }}][keterangan]" class="form-control form-control-sm" rows="2"
                          placeholder="Keterangan (wajib diisi kalau status OFF, misal: digantikan unit cadangan)"></textarea>
            </div>
        </div>
        @if(!$loop->last)<div style="height:1px;background:#edf2f7"></div>@endif
        @endforeach
    </div>
    <div class="card-footer d-flex gap-2 flex-wrap">
        <button type="submit" id="btnSubmitOnline" class="btn btn-primary-custom">
            <i class="bi bi-save me-1"></i>Simpan Log
        </button>
        <a href="{{ route('operasional.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
</div>

</form>
@endif
@endsection

@push('styles')
<style>
.pemancar-block{border-bottom:1px solid #edf2f7;}.pemancar-block:last-child{border-bottom:none;}
.pemancar-hdr{display:flex;align-items:center;justify-content:space-between;padding:.8rem 1rem;cursor:pointer;transition:background .15s;gap:.5rem;}
.pemancar-hdr:hover{background:#f8fafc;}.pemancar-bdy{padding:.85rem 1rem 1rem;background:#fafbfc;border-top:1px solid #edf2f7;display:none;}
.pemancar-bdy.open{display:block;}.sudah .pemancar-hdr{background:#f0fff4;}
.param-lbl{font-size:.68rem;font-weight:700;color:#636e72;text-transform:uppercase;letter-spacing:.8px;border-left:3px solid var(--primary);padding-left:.45rem;}
.vswr-bar{background:#f0f4f8;border-radius:6px;padding:.38rem .7rem;display:flex;align-items:center;flex-wrap:wrap;gap:.25rem;}
.ti{font-size:.82rem;color:#636e72;transition:transform .2s;flex-shrink:0;}.ti.rot{transform:rotate(-90deg);}
</style>
@endpush

@push('scripts')
<script>
// Toggle accordion
function toggleBlk(id){const b=document.getElementById('bdy-'+id);const t=document.getElementById('ti-'+id);b.classList.toggle('open');t.classList.toggle('rot');}

function toggleStatusOff(idx, isOff) {
    document.getElementById('status-val-' + idx).value = isOff ? 'off' : 'on';
    const fields = document.getElementById('fields-' + idx);
    if (!fields) return;
    fields.style.opacity = isOff ? '.4' : '1';
    fields.querySelectorAll('input[type=number]').forEach(inp => {
        inp.disabled = isOff;
        if (isOff) inp.value = '';
    });
}
let allOpen=true;
function toggleAll(){allOpen=!allOpen;document.querySelectorAll('.pemancar-bdy').forEach(b=>b.classList.toggle('open',allOpen));document.querySelectorAll('.ti').forEach(t=>t.classList.toggle('rot',!allOpen));document.getElementById('toggleLabel').textContent=allOpen?'Tutup Semua':'Buka Semua';}

// VSWR calc
function calcVswr(idx){
    const fwd=parseFloat(document.querySelector(`.out-final[data-idx="${idx}"]`)?.value)||0;
    const ref=parseFloat(document.querySelector(`.ref-final[data-idx="${idx}"]`)?.value)||0;
    const vEl=document.getElementById('vv-'+idx),sEl=document.getElementById('vs-'+idx),rEl=document.getElementById('rl-'+idx);
    if(!fwd){vEl.textContent='\u2014';vEl.style.color='';sEl.textContent='';rEl.textContent='\u2014';return;}
    const ratio=Math.min(ref,fwd)/fwd;const g=Math.sqrt(ratio);
    if(g>=1){vEl.textContent='\u221E';return;}
    const v=(1+g)/(1-g);
    vEl.textContent=v.toFixed(3);
    if(v<=1.5){vEl.style.color='#10ac84';sEl.textContent='\u2705 Baik';}
    else if(v<=2){vEl.style.color='#ff9f43';sEl.textContent='\u26A0\uFE0F Sedang';}
    else if(v<=3){vEl.style.color='#ee5a24';sEl.textContent='\u274C Buruk';}
    else{vEl.style.color='#d63031';sEl.textContent='\uD83D\uDEA8 Kritis';}
    rEl.textContent=ratio>0?(-10*Math.log10(ratio)).toFixed(2):'\u221E';
}
document.querySelectorAll('.out-final').forEach(el=>{
    el.addEventListener('input',()=>{
        calcVswr(el.dataset.idx);
        const max=parseFloat(el.dataset.max)||0;
        el.classList.toggle('is-invalid',max>0&&parseFloat(el.value)>max);
    });
});
document.querySelectorAll('.ref-final').forEach(el=>el.addEventListener('input',()=>calcVswr(el.dataset.idx)));

// Nilai terakhir tiap pemancar: petunjuk (placeholder), tombol isi, dan peringatan selisih/ketidakwajaran
const TERAKHIR = @json($terakhir);
(function(){
    const FIELDS = {output_final_pa:'PA', output_driver:'Driver', output_exciter:'Exciter', reflect_final:'Refl', reject_final:'Rej', suhu_pemancar:'Suhu'};
    const ids = {};
    document.querySelectorAll('input[type=hidden]').forEach(h => {
        const m = h.name.match(/^pemancar\[(\d+)\]\[id\]$/); if (m) ids[m[1]] = h.value;
    });
    // Angka bersih: 16.00 / 16,00 -> 16 ; 25,50 -> 25.5 (titik sebagai desimal, tanpa nol di belakang)
    const fmt = v => { const n = parseFloat(String(v).replace(',', '.')); return isNaN(n) ? '' : String(Number(n.toFixed(4))); };
    const inp = (idx, f) => document.querySelector('input[name="pemancar[' + idx + '][' + f + ']"]');
    function isi(idx) {
        const t = TERAKHIR[ids[idx]]; if (!t) return;
        Object.keys(FIELDS).forEach(f => {
            const el = inp(idx, f);
            if (el && !el.disabled && el.value === '' && t[f] !== null && t[f] !== undefined) {
                el.value = fmt(t[f]); el.dispatchEvent(new Event('input', {bubbles:true}));
            }
        });
    }
    function cek(idx, f, el, warn) {
        const t = TERAKHIR[ids[idx]]; const v = parseFloat(el.value); let msg = '';
        if (!isNaN(v) && t && t[f] !== null && t[f] !== undefined && parseFloat(t[f]) > 0) {
            const d = Math.abs(v - parseFloat(t[f])) / parseFloat(t[f]) * 100;
            if (d >= 30) msg = 'Beda ' + Math.round(d) + '% dari terakhir (' + fmt(t[f]) + ') \u2014 periksa';
        }
        if (f === 'reflect_final' || f === 'reject_final') {
            const pa = parseFloat((inp(idx, 'output_final_pa') || {}).value);
            if (!isNaN(v) && !isNaN(pa) && v > pa) msg = 'Tidak mungkin lebih besar dari Final PA';
        }
        warn.textContent = msg; warn.classList.toggle('d-none', !msg); el.classList.toggle('border-warning', !!msg);
    }
    let ada = false;
    Object.keys(ids).forEach(idx => {
        const t = TERAKHIR[ids[idx]]; const box = document.getElementById('fields-' + idx); if (!t || !box) return;
        ada = true;
        const ring = Object.keys(FIELDS).filter(k => t[k] !== null && t[k] !== undefined).map(k => FIELDS[k] + ' ' + fmt(t[k])).join(' \u00B7 ');
        const bar = document.createElement('div');
        bar.className = 'd-flex align-items-center flex-wrap gap-2 mb-2';
        bar.innerHTML = '<span class="text-muted" style="font-size:.7rem"><i class="bi bi-clock-history me-1"></i>Terakhir (' + t.waktu + '): ' + ring + '</span>';
        const btn = document.createElement('button');
        btn.type = 'button'; btn.className = 'btn btn-sm btn-outline-primary py-0 px-2'; btn.style.fontSize = '.7rem';
        btn.textContent = 'Ambil Log Terakhir'; btn.onclick = () => isi(idx);
        bar.appendChild(btn); box.insertBefore(bar, box.firstChild);
        Object.keys(FIELDS).forEach(f => {
            const el = inp(idx, f); if (!el) return;
            const warn = document.createElement('div'); warn.className = 'form-text text-warning d-none'; warn.style.fontSize = '.65rem';
            const col = el.closest('.col-4'); if (col) col.appendChild(warn);
            el.addEventListener('input', () => {
                cek(idx, f, el, warn);
                if (f === 'output_final_pa') ['reflect_final', 'reject_final'].forEach(g => { const e2 = inp(idx, g); if (e2 && e2.value !== '') e2.dispatchEvent(new Event('input')); });
            });
        });
    });
    const all = document.getElementById('btnIsiSemuaTerakhir');
    if (all && ada) { all.style.display = ''; all.onclick = () => Object.keys(ids).forEach(isi); }
})();

// \u2500\u2500 OFFLINE SUPPORT \u2500\u2500
// Penyimpanan offline & sinkronisasi otomatis ditangani offline-forms.js (form ber-atribut data-offline-queue).
function updateOfflineUI(){
    document.getElementById('offline-notice')?.style.setProperty('display', navigator.onLine ? 'none' : 'flex', 'important');
}
window.addEventListener('online',  updateOfflineUI);
window.addEventListener('offline', updateOfflineUI);
document.addEventListener('DOMContentLoaded', updateOfflineUI);
</script>
@endpush