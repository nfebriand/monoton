@extends('layouts.app')
@section('title','Catat Log Operasional')
@section('page-title','Catat Log Operasional')

@section('content')
@php $user = auth()->user(); @endphp

@if($lokasiTidakDiset)
<div class="alert alert-danger d-flex align-items-start gap-2">
    <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0 mt-1"></i>
    <div>
        <strong>Lokasi dinas Anda belum diset!</strong><br>
        Anda tidak dapat mencatat log. Hubungi administrator.
    </div>
</div>
@elseif($pemancars->isEmpty())
<div class="alert alert-warning d-flex align-items-start gap-2">
    <i class="bi bi-broadcast fs-5 flex-shrink-0 mt-1"></i>
    <div>
        <strong>Tidak ada pemancar di lokasi dinas Anda ({{ $user->lokasi_dinas }}).</strong><br>
        Hubungi administrator untuk mendaftarkan pemancar di lokasi Anda.
    </div>
</div>
@else

<form action="{{ route('operasional.store') }}" method="POST" id="formLog">
@csrf

{{-- Header: waktu, suhu, kelembaban --}}
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-sliders me-2"></i>Parameter Umum — Berlaku Semua Pemancar</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12 col-sm-6 col-md-4">
                <label class="form-label">Waktu Pencatatan <span class="text-danger">*</span></label>
                <input type="datetime-local" name="dicatat_pada" class="form-control"
                       value="{{ old('dicatat_pada', now()->format('Y-m-d\TH:i')) }}" required>
            </div>
            <div class="col-6 col-sm-3 col-md-3">
                <label class="form-label">Suhu Ruangan <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="number" name="suhu_ruangan" class="form-control mono"
                           step="0.1" value="{{ old('suhu_ruangan', $suhuTerakhir) }}"
                           required>
                    <span class="input-group-text">°C</span>
                </div>
            </div>
            <div class="col-6 col-sm-3 col-md-3">
                <label class="form-label">Kelembaban</label>
                <div class="input-group">
                    <input type="number" name="kelembaban" class="form-control mono"
                           step="0.1" min="0" max="100"
                           value="{{ old('kelembaban', $kelembabanTerakhir) }}">
                    <span class="input-group-text">%</span>
                </div>
            </div>
            @if($shiftAktif)
            <div class="col-12 col-md-2 d-flex align-items-end">
                <div class="w-100 p-2 rounded text-center" style="background:#e8f4fd;border:1px solid #b3d7f0">
                    <div style="font-size:.6rem;color:#0a3d62;font-weight:600;text-transform:uppercase">Shift Aktif</div>
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
        @if($user->isAdmin())
            <span>Administrator — Semua pemancar</span>
        @else
            <span>Lokasi Dinas: <strong>{{ $user->lokasi_dinas }}</strong></span>
        @endif
    </div>
    <span class="badge bg-primary ms-auto">{{ $pemancars->count() }} Pemancar</span>
</div>

{{-- Accordion semua pemancar --}}
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-broadcast-pin me-2 text-primary"></i>Data Per Pemancar — Wajib diisi semua</span>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAll()">
            <i class="bi bi-arrows-expand me-1"></i><span id="toggleLabel">Tutup Semua</span>
        </button>
    </div>
    <div class="card-body p-0">

        @foreach($pemancars as $idx => $pemancar)
        @php
            $sudah    = in_array($pemancar->id, $sudahDicatat);
            $maxPower = $pemancar->kapasitas_output_final;
        @endphp

        <div class="pemancar-block {{ $sudah ? 'sudah':'belum' }}" id="blk-{{ $pemancar->id }}">
            {{-- Header accordion --}}
            <div class="pemancar-hdr" onclick="toggleBlk({{ $pemancar->id }})">
                <div class="d-flex align-items-center gap-2 flex-wrap" style="min-width:0">
                    <i class="bi bi-chevron-down ti" id="ti-{{ $pemancar->id }}"></i>
                    <span class="badge {{ $pemancar->modulasi==='FM'?'bg-primary':'bg-warning text-dark' }}">
                        {{ $pemancar->modulasi }}
                    </span>
                    <strong class="text-truncate" style="font-size:.88rem">{{ $pemancar->nama_stasiun }}</strong>
                    <small class="text-muted d-none d-sm-inline">{{ $pemancar->merk }} {{ $pemancar->tipe_unit }}</small>
                    @if($pemancar->lokasi)
                    <span class="badge bg-secondary" style="font-size:.62rem">
                        <i class="bi bi-geo-alt me-1"></i>{{ $pemancar->lokasi }}
                    </span>
                    @endif
                    <span class="mono text-muted" style="font-size:.72rem">{{ number_format($maxPower,0) }} W maks</span>
                </div>
                <span class="badge flex-shrink-0 {{ $sudah?'bg-success':'bg-warning text-dark' }}">
                    <i class="bi bi-{{ $sudah?'check-circle':'exclamation-circle' }} me-1"></i>
                    {{ $sudah?'Sudah':'Belum' }}
                </span>
            </div>

            {{-- Body --}}
            <div class="pemancar-bdy open" id="bdy-{{ $pemancar->id }}">
                <input type="hidden" name="pemancar[{{ $idx }}][id]" value="{{ $pemancar->id }}">

                {{-- Output Power --}}
                <div class="param-lbl mb-2">⚡ Output Power</div>
                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <label class="form-label">Final PA
                            <span class="text-danger" style="font-size:.65rem">(maks {{ number_format($maxPower,0) }}W)</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="pemancar[{{ $idx }}][output_final_pa]"
                                   class="form-control mono out-final" data-idx="{{ $idx }}"
                                   data-max="{{ $maxPower }}"
                                   step="0.01" min="0" max="{{ $maxPower }}" placeholder="0.00">
                            <span class="input-group-text">W</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <label class="form-label">Driver</label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="pemancar[{{ $idx }}][output_driver]"
                                   class="form-control mono" step="0.01" min="0" placeholder="0.00">
                            <span class="input-group-text">W</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <label class="form-label">Exciter</label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="pemancar[{{ $idx }}][output_exciter]"
                                   class="form-control mono" step="0.01" min="0" placeholder="0.00">
                            <span class="input-group-text">W</span>
                        </div>
                    </div>
                </div>

                {{-- Reflected & Reject --}}
                <div class="param-lbl mb-2">↩ Reflected & Reject Power</div>
                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <label class="form-label">Reflect Final</label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="pemancar[{{ $idx }}][reflect_final]"
                                   class="form-control mono ref-final" data-idx="{{ $idx }}"
                                   step="0.01" min="0" placeholder="0.00">
                            <span class="input-group-text">W</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <label class="form-label">Reject Final</label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="pemancar[{{ $idx }}][reject_final]"
                                   class="form-control mono" step="0.01" min="0" placeholder="0.00">
                            <span class="input-group-text">W</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <label class="form-label">Suhu Pemancar</label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="pemancar[{{ $idx }}][suhu_pemancar]"
                                   class="form-control mono" step="0.1" placeholder="0.0">
                            <span class="input-group-text">°C</span>
                        </div>
                    </div>
                </div>

                {{-- VSWR Preview --}}
                <div class="vswr-bar mb-3">
                    <i class="bi bi-calculator text-primary me-1"></i>
                    <span style="font-size:.73rem">VSWR Final:</span>
                    <strong class="mono ms-1 vswr-val" id="vv-{{ $idx }}" style="font-size:.9rem">—</strong>
                    <span class="vswr-st ms-2" id="vs-{{ $idx }}" style="font-size:.73rem"></span>
                    <span class="text-muted ms-auto" style="font-size:.7rem">RL: <span id="rl-{{ $idx }}">—</span> dB</span>
                </div>

                {{-- Keterangan --}}
                <textarea name="pemancar[{{ $idx }}][keterangan]"
                          class="form-control form-control-sm" rows="2"
                          ></textarea>
            </div>
        </div>
        @if(!$loop->last)<div style="height:1px;background:#edf2f7"></div>@endif
        @endforeach

    </div>
    <div class="card-footer d-flex gap-2 flex-wrap">
        <button type="submit" class="btn btn-primary-custom">
            <i class="bi bi-save me-1"></i>Simpan Semua Log
        </button>
        <a href="{{ route('operasional.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
</div>

</form>
@endif
@endsection

@push('styles')
<style>
.btn-primary-custom{background:#0a3d62;color:#fff;border:none;border-radius:7px;padding:.45rem 1.1rem;font-weight:600;font-size:.84rem;}
.btn-primary-custom:hover{background:#1e5f8a;color:#fff;}
.pemancar-block{transition:background .15s;}
.pemancar-hdr{display:flex;align-items:center;justify-content:space-between;padding:.8rem 1rem;cursor:pointer;gap:.5rem;}
.pemancar-hdr:hover{background:#f8fafc;}
.sudah .pemancar-hdr{background:#f0fff4;}
.pemancar-bdy{padding:.85rem 1rem 1rem;background:#fafbfc;border-top:1px solid #edf2f7;display:none;}
.pemancar-bdy.open{display:block;}
.ti{font-size:.82rem;color:#636e72;transition:transform .2s;flex-shrink:0;}
.ti.rot{transform:rotate(-90deg);}
.param-lbl{font-size:.68rem;font-weight:700;color:#636e72;text-transform:uppercase;letter-spacing:.8px;border-left:3px solid #0a3d62;padding-left:.45rem;}
.vswr-bar{background:#f0f4f8;border-radius:6px;padding:.38rem .7rem;display:flex;align-items:center;flex-wrap:wrap;gap:.25rem;}
@media(max-width:575px){
    .pemancar-hdr{padding:.65rem .75rem;}
    .pemancar-bdy{padding:.7rem .75rem .85rem;}
}
</style>
@endpush

@push('scripts')
<script>
// Toggle accordion
function toggleBlk(id){
    const b=document.getElementById('bdy-'+id);
    const t=document.getElementById('ti-'+id);
    b.classList.toggle('open');
    t.classList.toggle('rot');
}
// Toggle semua
let allOpen=true;
function toggleAll(){
    allOpen=!allOpen;
    document.querySelectorAll('.pemancar-bdy').forEach(b=>b.classList.toggle('open',allOpen));
    document.querySelectorAll('.ti').forEach(t=>t.classList.toggle('rot',!allOpen));
    document.getElementById('toggleLabel').textContent=allOpen?'Tutup Semua':'Buka Semua';
}

// VSWR real-time
function calcVswr(idx){
    const fwd=parseFloat(document.querySelector(`.out-final[data-idx="${idx}"]`)?.value)||0;
    const ref=parseFloat(document.querySelector(`.ref-final[data-idx="${idx}"]`)?.value)||0;
    const vEl=document.getElementById('vv-'+idx);
    const sEl=document.getElementById('vs-'+idx);
    const rEl=document.getElementById('rl-'+idx);
    if(!fwd){vEl.textContent='—';vEl.style.color='';sEl.textContent='';rEl.textContent='—';return;}
    const ratio=Math.min(ref,fwd)/fwd;
    const g=Math.sqrt(ratio);
    if(g>=1){vEl.textContent='∞';return;}
    const v=(1+g)/(1-g);
    vEl.textContent=v.toFixed(3);
    if(v<=1.5){vEl.style.color='#10ac84';sEl.textContent='✅ Baik';}
    else if(v<=2){vEl.style.color='#ff9f43';sEl.textContent='⚠️ Sedang';}
    else if(v<=3){vEl.style.color='#ee5a24';sEl.textContent='❌ Buruk';}
    else{vEl.style.color='#d63031';sEl.textContent='🚨 Kritis';}
    rEl.textContent=ratio>0?(-10*Math.log10(ratio)).toFixed(2):'∞';
}

// Validasi max output
document.querySelectorAll('.out-final').forEach(el=>{
    el.addEventListener('input',()=>{
        calcVswr(el.dataset.idx);
        const max=parseFloat(el.dataset.max)||0;
        if(max>0 && parseFloat(el.value)>max){
            el.classList.add('is-invalid');
        } else {
            el.classList.remove('is-invalid');
        }
    });
});
document.querySelectorAll('.ref-final').forEach(el=>{
    el.addEventListener('input',()=>calcVswr(el.dataset.idx));
});
</script>
@endpush
