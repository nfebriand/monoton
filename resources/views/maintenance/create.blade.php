@extends('layouts.app')
@section('title','Catat Maintenance')
@section('page-title','Catat Maintenance Aset')

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-8">
<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-tools text-success"></i>Form Maintenance Aset
        <a href="{{ route('maintenance.index') }}" class="btn btn-sm btn-outline-secondary ms-auto"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>
    <div class="card-body">
    <form action="{{ route('maintenance.store') }}" method="POST" enctype="multipart/form-data" id="formMaintenance">
    @csrf

    <div class="row g-3 mb-4">
        <div class="col-12">
            <label class="form-label">Aset <span class="text-danger">*</span></label>
            <select name="aset_id" class="form-select" required>
                <option value="">— Pilih Aset —</option>
                @foreach($asets as $a)
                <option value="{{ $a->id }}" {{ (old('aset_id', $asetTerpilih->id ?? null))==$a->id?'selected':'' }}>
                    {{ $a->kode_aset }} — {{ $a->nama }} {{ $a->lokasi?'('.$a->lokasi.')':'' }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Tanggal <span class="text-danger">*</span></label>
            <input type="date" name="tanggal" class="form-control" value="{{ old('tanggal', now()->toDateString()) }}" required>
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Jenis <span class="text-danger">*</span></label>
            <select name="jenis" class="form-select" required>
                @foreach(\App\Models\MaintenanceLog::JENIS_LABEL as $val=>$label)
                <option value="{{ $val }}" {{ old('jenis')==$val?'selected':'' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label">Hasil <span class="text-danger">*</span></label>
            <select name="hasil" class="form-select" required>
                @foreach(\App\Models\MaintenanceLog::HASIL_LABEL as $val=>$label)
                <option value="{{ $val }}" {{ old('hasil','selesai')==$val?'selected':'' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label">Uraian Pekerjaan <span class="text-danger">*</span></label>
        <textarea name="uraian_pekerjaan" class="form-control" rows="4" required>{{ old('uraian_pekerjaan') }}</textarea>
    </div>

    <h6 class="section-title mb-3">⚙️ Sparepart Terpakai <span class="text-muted" style="font-size:.72rem">(opsional)</span></h6>
    <div id="sparepartList" class="mb-2">
        {{-- Baris sparepart akan ditambah via JS --}}
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary mb-4" onclick="tambahSparepart()">
        <i class="bi bi-plus-circle me-1"></i>Tambah Item Sparepart
    </button>

    <div class="row g-3 mb-4">
        <div class="col-6">
            <label class="form-label">Biaya (Rp)</label>
            <input type="number" step="1000" name="biaya" class="form-control mono" value="{{ old('biaya') }}">
        </div>
        <div class="col-6">
            <label class="form-label">Rencana Maintenance Berikutnya</label>
            <input type="date" name="rencana_maintenance_berikutnya" class="form-control" value="{{ old('rencana_maintenance_berikutnya') }}">
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label">Keterangan</label>
        <textarea name="keterangan" class="form-control" rows="2">{{ old('keterangan') }}</textarea>
    </div>

    <h6 class="section-title mb-3">📷 Foto Dokumentasi</h6>
    <div class="row g-3 mb-4">
        <div class="col-6">
            <label class="form-label">Foto Sebelum</label>
            <input type="file" name="fotos_sebelum[]" class="form-control" multiple accept="image/jpeg,image/png,image/jpg,image/webp">
        </div>
        <div class="col-6">
            <label class="form-label">Foto Sesudah</label>
            <input type="file" name="fotos_sesudah[]" class="form-control" multiple accept="image/jpeg,image/png,image/jpg,image/webp">
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-success fw-bold"><i class="bi bi-save me-1"></i>Simpan</button>
        <a href="{{ route('maintenance.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
    </form>
    </div>
</div>
</div>
</div>
@endsection

@push('styles')
<style>
.sparepart-row{display:flex;gap:.5rem;margin-bottom:.5rem;align-items:center}
.sparepart-row input{font-size:.82rem}
</style>
@endpush

@push('scripts')
<script>
let sparepartIdx = 0;
function tambahSparepart(){
    const div = document.createElement('div');
    div.className = 'sparepart-row';
    div.innerHTML = `
        <input type="text" name="sparepart_nama[]" class="form-control" placeholder="Nama sparepart" style="flex:2">
        <input type="number" name="sparepart_jumlah[]" class="form-control mono" placeholder="Qty" value="1" min="1" style="flex:.6">
        <input type="text" name="sparepart_satuan[]" class="form-control" placeholder="satuan" value="pcs" style="flex:.7">
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.sparepart-row').remove()"><i class="bi bi-trash"></i></button>
    `;
    document.getElementById('sparepartList').appendChild(div);
    sparepartIdx++;
}
</script>
@endpush
