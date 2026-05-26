@extends('layouts.app')
@section('title','Edit Log Operasional')
@section('page-title','Edit Log Operasional')

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-xl-9">

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('operasional.show',$operasional) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
    <span class="fw-bold">Edit Log #{{ $operasional->id }}</span>
    <span class="badge bg-secondary ms-auto">{{ $operasional->dicatat_pada->format('d/m/Y H:i') }}</span>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center gap-2 flex-wrap">
        <i class="bi bi-pencil-square text-warning"></i>
        <strong>{{ $operasional->pemancar->nama_stasiun }}</strong>
        <span class="badge {{ $operasional->pemancar->modulasi==='FM'?'bg-primary':'bg-warning text-dark' }}">
            {{ $operasional->pemancar->modulasi }}
        </span>
        @if($operasional->pemancar->lokasi)
        <span class="badge bg-secondary" style="font-size:.65rem">{{ $operasional->pemancar->lokasi }}</span>
        @endif
    </div>
    <div class="card-body">

    <form action="{{ route('operasional.update',$operasional) }}" method="POST">
    @csrf @method('PUT')

    {{-- Waktu --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <label class="form-label">Waktu Pencatatan <span class="text-danger">*</span></label>
            <input type="datetime-local" name="dicatat_pada" class="form-control"
                   value="{{ old('dicatat_pada', $operasional->dicatat_pada->format('Y-m-d\TH:i')) }}" required>
        </div>
        <div class="col-md-6">
            <div class="p-3 rounded h-100 d-flex flex-column justify-content-center"
                 style="background:#f8fafc;border:1px solid #eee;font-size:.8rem">
                <div class="text-muted">Operator</div>
                <div class="fw-bold">{{ $operasional->user->name }}</div>
                @if($operasional->jadwalShift)
                <div class="text-muted mt-1">Shift {{ $operasional->jadwalShift->shift }}
                    — {{ $operasional->jadwalShift->jam_mulai }}–{{ $operasional->jadwalShift->jam_selesai }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Output Power --}}
    <h6 class="section-title mb-3">⚡ Output Power</h6>
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <label class="form-label">
                Output Final PA
                @if($operasional->pemancar->kapasitas_output_final)
                <span class="text-muted fw-normal" style="font-size:.68rem">
                    (maks {{ number_format($operasional->pemancar->kapasitas_output_final,0) }} W)
                </span>
                @endif
            </label>
            <div class="input-group input-group-sm">
                <input type="number" name="output_final_pa" id="outFinal" class="form-control mono"
                       step="0.01" min="0" max="{{ $operasional->pemancar->kapasitas_output_final }}"
                       value="{{ old('output_final_pa', $operasional->output_final_pa) }}" placeholder="0.00">
                <span class="input-group-text">W</span>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Output Driver</label>
            <div class="input-group input-group-sm">
                <input type="number" name="output_driver" class="form-control mono"
                       step="0.01" min="0" value="{{ old('output_driver', $operasional->output_driver) }}" placeholder="0.00">
                <span class="input-group-text">W</span>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Output Exciter</label>
            <div class="input-group input-group-sm">
                <input type="number" name="output_exciter" class="form-control mono"
                       step="0.01" min="0" value="{{ old('output_exciter', $operasional->output_exciter) }}" placeholder="0.00">
                <span class="input-group-text">W</span>
            </div>
        </div>
    </div>

    {{-- Reflected & Reject --}}
    <h6 class="section-title mb-3">↩ Reflected & Reject Power</h6>
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-4">
            <label class="form-label">Reflect Final</label>
            <div class="input-group input-group-sm">
                <input type="number" name="reflect_final" id="refFinal" class="form-control mono"
                       step="0.01" min="0" value="{{ old('reflect_final', $operasional->reflect_final) }}" placeholder="0.00">
                <span class="input-group-text">W</span>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Reject Final</label>
            <div class="input-group input-group-sm">
                <input type="number" name="reject_final" class="form-control mono"
                       step="0.01" min="0" value="{{ old('reject_final', $operasional->reject_final) }}" placeholder="0.00">
                <span class="input-group-text">W</span>
            </div>
        </div>
        <div class="col-12 col-md-4">
            {{-- VSWR Preview --}}
            <label class="form-label">VSWR Preview</label>
            <div class="p-2 rounded text-center" style="background:#f0f4f8;border:1px solid #dfe6e9">
                <div style="font-size:.68rem;color:#636e72">VSWR FINAL</div>
                <div class="mono fw-bold" id="vswrPreview" style="font-size:1.3rem">
                    {{ $operasional->vswr_final ? number_format($operasional->vswr_final,3) : '—' }}
                </div>
                <div id="vswrStatus" style="font-size:.72rem"></div>
            </div>
        </div>
    </div>

    {{-- Suhu & Kelembaban --}}
    <h6 class="section-title mb-3">🌡️ Suhu & Kelembaban</h6>
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <label class="form-label">Suhu Pemancar</label>
            <div class="input-group input-group-sm">
                <input type="number" name="suhu_pemancar" class="form-control mono"
                       step="0.1" value="{{ old('suhu_pemancar', $operasional->suhu_pemancar) }}" placeholder="0.0">
                <span class="input-group-text">°C</span>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Suhu Ruangan</label>
            <div class="input-group input-group-sm">
                <input type="number" name="suhu_ruangan" class="form-control mono"
                       step="0.1" value="{{ old('suhu_ruangan', $operasional->suhu_ruangan) }}" placeholder="0.0">
                <span class="input-group-text">°C</span>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Kelembaban</label>
            <div class="input-group input-group-sm">
                <input type="number" name="kelembaban" class="form-control mono"
                       step="0.1" min="0" max="100" value="{{ old('kelembaban', $operasional->kelembaban) }}" placeholder="0.0">
                <span class="input-group-text">%</span>
            </div>
        </div>
    </div>

    {{-- Keterangan --}}
    <div class="mb-4">
        <label class="form-label">Keterangan / Kondisi</label>
        <textarea name="keterangan" class="form-control" rows="3"
                  placeholder="Kondisi operasional, gangguan, tindakan...">{{ old('keterangan', $operasional->keterangan) }}</textarea>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        <button type="submit" class="btn btn-warning fw-bold">
            <i class="bi bi-save me-1"></i> Simpan Perubahan
        </button>
        <a href="{{ route('operasional.show',$operasional) }}" class="btn btn-outline-secondary">Batal</a>
    </div>

    </form>
    </div>
</div>

</div>
</div>
@endsection

@push('styles')
<style>
.section-title{font-weight:700;color:#0a3d62;border-left:4px solid #0a3d62;padding-left:.65rem;font-size:.85rem;}
</style>
@endpush

@push('scripts')
<script>
const maxKapasitas = {{ $operasional->pemancar->kapasitas_output_final }};

function updateVswr(){
    const fwd = parseFloat(document.getElementById('outFinal')?.value) || 0;
    const ref = parseFloat(document.getElementById('refFinal')?.value) || 0;
    const el  = document.getElementById('vswrPreview');
    const es  = document.getElementById('vswrStatus');

    if (!fwd || fwd <= 0){ el.textContent='—'; el.style.color=''; es.textContent=''; return; }

    const ratio = Math.min(ref,fwd)/fwd;
    const gamma = Math.sqrt(ratio);
    if(gamma>=1){ el.textContent='∞'; return; }

    const vswr = (1+gamma)/(1-gamma);
    el.textContent = vswr.toFixed(3);

    if(vswr<=1.5){el.style.color='#10ac84';es.textContent='✅ Baik';}
    else if(vswr<=2.0){el.style.color='#ff9f43';es.textContent='⚠️ Sedang';}
    else if(vswr<=3.0){el.style.color='#ee5a24';es.textContent='❌ Buruk';}
    else{el.style.color='#d63031';es.textContent='🚨 Kritis';}
}

// Validasi output vs kapasitas
document.getElementById('outFinal')?.addEventListener('input', function(){
    updateVswr();
    if(maxKapasitas > 0 && parseFloat(this.value) > maxKapasitas){
        this.classList.add('is-invalid');
        this.setCustomValidity('Tidak boleh melebihi kapasitas '+maxKapasitas+' W');
    } else {
        this.classList.remove('is-invalid');
        this.setCustomValidity('');
    }
});
document.getElementById('refFinal')?.addEventListener('input', updateVswr);
updateVswr();
</script>
@endpush
