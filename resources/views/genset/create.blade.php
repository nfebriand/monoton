@extends('layouts.app')
@section('title','Catat Operasional Genset')
@section('page-title','Catat Operasional Genset')

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-8">
<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-battery-charging text-primary"></i>Form Operasional Genset
        <a href="{{ route('genset.index') }}" class="btn btn-sm btn-outline-secondary ms-auto">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
    <div class="card-body">
    <form action="{{ route('genset.store') }}" method="POST" id="formGenset">
    @csrf

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <label class="form-label">Unit Genset <span class="text-danger">*</span></label>
            <select name="genset_unit_id" id="genset_unit_id" class="form-select" required onchange="autoFillLast()">
                <option value="">— Pilih Unit —</option>
                @foreach($units as $u)
                <option value="{{ $u->id }}" {{ old('genset_unit_id')==$u->id?'selected':'' }}
                    data-hm-akhir="{{ $lastLogs[$u->id]->hm_akhir ?? '' }}"
                    data-bbm-akhir="{{ $lastLogs[$u->id]->bbm_akhir ?? '' }}"
                    data-tangki="{{ $u->kapasitas_tangki_liter ?? '' }}">
                    {{ $u->nama_unit }}{{ $u->lokasi ? ' — '.$u->lokasi : '' }}
                </option>
                @endforeach
            </select>
            <div id="info-unit" class="form-text" style="display:none"></div>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Alasan Penyalaan <span class="text-danger">*</span></label>
            <select name="alasan" class="form-select" required>
                <option value="pln_mati" {{ old('alasan')=='pln_mati'?'selected':'' }}>PLN Padam</option>
                <option value="maintenance" {{ old('alasan')=='maintenance'?'selected':'' }}>Maintenance / Servis</option>
                <option value="test_rutin" {{ old('alasan')=='test_rutin'?'selected':'' }}>Test Rutin</option>
                <option value="lainnya" {{ old('alasan')=='lainnya'?'selected':'' }}>Lainnya</option>
            </select>
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label">Tanggal <span class="text-danger">*</span></label>
            <input type="date" name="tanggal" class="form-control" value="{{ old('tanggal',now()->toDateString()) }}" required>
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Jam Mulai <span class="text-danger">*</span></label>
            <input type="time" name="jam_mulai" class="form-control mono" value="{{ old('jam_mulai',now()->format('H:i')) }}" required>
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Jam Selesai</label>
            <input type="time" name="jam_selesai" class="form-control mono" value="{{ old('jam_selesai') }}">
            <div class="form-text">Kosongkan jika genset masih berjalan</div>
        </div>
    </div>

    <h6 class="section-title mb-3">⏱️ Hour Meter (HM)</h6>
    <div class="row g-3 mb-4">
        <div class="col-6">
            <label class="form-label">HM Awal (jam)</label>
            <input type="number" step="0.1" name="hm_awal" id="hm_awal" class="form-control mono" value="{{ old('hm_awal') }}">
        </div>
        <div class="col-6">
            <label class="form-label">HM Akhir (jam)</label>
            <input type="number" step="0.1" name="hm_akhir" id="hm_akhir" class="form-control mono" value="{{ old('hm_akhir') }}">
        </div>
        <div class="col-12">
            <div class="form-text">Otomatis terisi dari HM akhir log sebelumnya jika tersedia.</div>
        </div>
    </div>

    <h6 class="section-title mb-3">⛽ Bahan Bakar (Liter)</h6>
    <div class="row g-3 mb-4">
        <div class="col-4">
            <label class="form-label">BBM Awal</label>
            <input type="number" step="0.1" name="bbm_awal" id="bbm_awal" class="form-control mono" value="{{ old('bbm_awal') }}">
        </div>
        <div class="col-4">
            <label class="form-label">Pengisian BBM</label>
            <input type="number" step="0.1" name="bbm_isi" class="form-control mono" value="{{ old('bbm_isi') }}" placeholder="0">
            <div class="form-text">Isi jika ada pengisian BBM saat ini</div>
        </div>
        <div class="col-4">
            <label class="form-label">BBM Akhir</label>
            <input type="number" step="0.1" name="bbm_akhir" class="form-control mono" value="{{ old('bbm_akhir') }}">
        </div>
    </div>

    <h6 class="section-title mb-3">📋 Kondisi & Keterangan</h6>
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <label class="form-label">Kondisi Mesin <span class="text-danger">*</span></label>
            <select name="kondisi" class="form-select" required>
                <option value="normal" {{ old('kondisi')=='normal'?'selected':'' }}>Normal</option>
                <option value="gangguan" {{ old('kondisi')=='gangguan'?'selected':'' }}>Ada Gangguan</option>
            </select>
        </div>
        <div class="col-12 col-md-8">
            <label class="form-label">Keterangan</label>
            <input type="text" name="keterangan" class="form-control" value="{{ old('keterangan') }}"
                   placeholder="Catatan tambahan, kondisi gangguan, dll">
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Simpan</button>
        <a href="{{ route('genset.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
    </form>
    </div>
</div>
</div>
</div>
@endsection

@push('scripts')
<script>
function autoFillLast(){
    const sel = document.getElementById('genset_unit_id');
    const opt = sel.options[sel.selectedIndex];
    const hmAkhir  = opt.dataset.hmAkhir;
    const bbmAkhir = opt.dataset.bbmAkhir;
    const tangki   = opt.dataset.tangki;

    if(hmAkhir)  document.getElementById('hm_awal').value  = hmAkhir;
    if(bbmAkhir) document.getElementById('bbm_awal').value = bbmAkhir;

    const info = document.getElementById('info-unit');
    let msgs = [];
    if(hmAkhir)  msgs.push('HM akhir terakhir: '+hmAkhir+' jam');
    if(bbmAkhir) msgs.push('BBM akhir terakhir: '+bbmAkhir+' L');
    if(tangki)   msgs.push('Kapasitas tangki: '+tangki+' L');
    if(msgs.length){
        info.style.display='block';
        info.innerHTML = '<i class="bi bi-info-circle me-1"></i>'+msgs.join(' | ');
    } else {
        info.style.display='none';
    }
}
document.addEventListener('DOMContentLoaded', autoFillLast);
</script>
@endpush
