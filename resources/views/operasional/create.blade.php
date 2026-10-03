@extends('layouts.app')
@section('title','Catat Log')
@section('page-title','Catat Log Operasional')

@section('content')
@php $user = auth()->user(); @endphp

{{-- Offline notice --}}
<div id="offline-notice" class="alert alert-warning d-flex align-items-center gap-2 py-2 mb-3" style="display:none!important">
    <i class="bi bi-cloud-slash fs-5 flex-shrink-0"></i>
    <div>
        <strong>Mode Offline</strong> — Data akan disimpan lokal dan disinkronkan otomatis saat online.
    </div>
</div>

@if($lokasiTidakDiset)
<div class="alert alert-danger"><strong>Lokasi dinas belum diset!</strong> Hubungi administrator.</div>
@elseif($pemancars->isEmpty())
<div class="alert alert-warning"><strong>Tidak ada pemancar di lokasi {{ $user->lokasi_dinas }}.</strong></div>
@else

<form action="{{ route('operasional.store') }}" method="POST" id="formLog" enctype="multipart/form-data">
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
                <label class="form-label">Suhu Ruangan (°C) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="number" name="suhu_ruangan" id="suhu_ruangan" class="form-control mono"
                           step="0.1" value="{{ old('suhu_ruangan', $suhuTerakhir) }}" required>
                    <span class="input-group-text">°C</span>
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
                    <div class="mono" style="font-size:.65rem;color:#555">{{ $shiftAktif->jam_mulai }}–{{ $shiftAktif->jam_selesai }}</div>
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
        @if($user->isAdmin())<span>Administrator — Semua pemancar</span>
        @else<span>Lokasi: <strong>{{ $user->lokasi_dinas }}</strong></span>@endif
    </div>
    <span class="badge bg-primary ms-auto">{{ $pemancars->count() }} Pemancar</span>
</div>

{{-- Accordion Pemancar --}}
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-broadcast-pin me-2 text-primary"></i>Data Per Pemancar</span>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAll()">
            <i class="bi bi-arrows-expand me-1"></i><span id="toggleLabel">Tutup Semua</span>
        </button>
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

                <div class="form-check form-switch mb-3 p-2" style="background:#fff7e6;border:1px solid #ffe4a0;border-radius:6px">
                    <input class="form-check-input" type="checkbox" role="switch"
                           id="status-off-{{ $idx }}"
                           onchange="toggleStatusOff({{ $idx }}, this.checked)">
                    <label class="form-check-label" for="status-off-{{ $idx }}" style="font-size:.8rem">
                        Pemancar sedang <strong>OFF</strong> (rusak/bergantian dengan cadangan)
                    </label>
                    <input type="hidden" name="pemancar[{{ $idx }}][status]" id="status-val-{{ $idx }}" value="on">
                </div>

                <div class="param-fields" id="fields-{{ $idx }}">
                <div class="param-lbl mb-2">⚡ Output Power</div>
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
                <div class="param-lbl mb-2">↩ Reflected & Reject</div>
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
                            <input type="number" name="pemancar[{{ $idx }}][suhu_pemancar]" class="form-control mono" step="0.1">
                            <span class="input-group-text">°C</span>
                        </div>
                    </div>
                </div>
                <div class="vswr-bar mb-3">
                    <i class="bi bi-calculator text-primary me-1"></i>
                    <span style="font-size:.73rem">VSWR Final:</span>
                    <strong class="mono ms-1 vswr-val" id="vv-{{ $idx }}" style="font-size:.9rem">—</strong>
                    <span class="vswr-st ms-2" id="vs-{{ $idx }}" style="font-size:.73rem"></span>
                    <span class="text-muted ms-auto" style="font-size:.7rem">RL: <span id="rl-{{ $idx }}">—</span> dB</span>
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
        <button type="button" id="btnSimpanOffline" class="btn btn-warning fw-bold" style="display:none"
                onclick="simpanOffline()">
            <i class="bi bi-cloud-arrow-down me-1"></i>Simpan Offline
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
    if(!fwd){vEl.textContent='—';vEl.style.color='';sEl.textContent='';rEl.textContent='—';return;}
    const ratio=Math.min(ref,fwd)/fwd;const g=Math.sqrt(ratio);
    if(g>=1){vEl.textContent='∞';return;}
    const v=(1+g)/(1-g);
    vEl.textContent=v.toFixed(3);
    if(v<=1.5){vEl.style.color='#10ac84';sEl.textContent='✅ Baik';}
    else if(v<=2){vEl.style.color='#ff9f43';sEl.textContent='⚠️ Sedang';}
    else if(v<=3){vEl.style.color='#ee5a24';sEl.textContent='❌ Buruk';}
    else{vEl.style.color='#d63031';sEl.textContent='🚨 Kritis';}
    rEl.textContent=ratio>0?(-10*Math.log10(ratio)).toFixed(2):'∞';
}
document.querySelectorAll('.out-final').forEach(el=>{
    el.addEventListener('input',()=>{
        calcVswr(el.dataset.idx);
        const max=parseFloat(el.dataset.max)||0;
        el.classList.toggle('is-invalid',max>0&&parseFloat(el.value)>max);
    });
});
document.querySelectorAll('.ref-final').forEach(el=>el.addEventListener('input',()=>calcVswr(el.dataset.idx)));

// ── OFFLINE SUPPORT ──
function updateOfflineUI(){
    const isOnline = navigator.onLine;
    document.getElementById('offline-notice')?.style.setProperty('display', isOnline ? 'none' : 'flex', 'important');
    const btnOnline  = document.getElementById('btnSubmitOnline');
    const btnOffline = document.getElementById('btnSimpanOffline');
    if(btnOnline)  btnOnline.style.display  = isOnline ? '' : 'none';
    if(btnOffline) btnOffline.style.display = isOnline ? 'none' : '';
}
window.addEventListener('online',  updateOfflineUI);
window.addEventListener('offline', updateOfflineUI);
document.addEventListener('DOMContentLoaded', updateOfflineUI);

async function simpanOffline(){
    const form = document.getElementById('formLog');
    const fd   = new FormData(form);

    // Kumpulkan data dari form
    const data = {};
    for(const [k,v] of fd.entries()){
        if(k === '_token') continue;
        // Parse array fields: pemancar[0][id] dll
        const m = k.match(/^(\w+)\[(\d+)\]\[(\w+)\]$/);
        if(m){
            const [,store,idx,field] = m;
            if(!data[store]) data[store] = [];
            if(!data[store][idx]) data[store][idx] = {};
            data[store][idx][field] = v;
        } else {
            data[k] = v;
        }
    }

    const localId = await OfflineDB.add('pending_logs', { data });
    await SyncManager.updateBadge();
    SyncManager.showToast('✅ Log disimpan offline! Akan sync saat online.', 'success');

    // Reset form
    form.reset();
    document.getElementById('dicatat_pada').value = new Date().toISOString().slice(0,16);
    setTimeout(()=>{ window.location.href = '{{ route("operasional.index") }}'; }, 1500);
}
</script>
@endpush
