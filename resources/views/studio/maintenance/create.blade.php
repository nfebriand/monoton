@extends('layouts.app')
@section('title','Catat Maintenance Studio')
@section('page-title','Catat Maintenance Perangkat Studio')

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-9">
<div class="card">
<div class="card-header"><h6 class="mb-0"><i class="bi bi-tools me-2"></i>Form Maintenance Perangkat Studio</h6></div>
<div class="card-body">
@if($errors->any())
<div class="alert alert-danger py-2"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li style="font-size:.82rem">{{ $e }}</li>@endforeach</ul></div>
@endif
<form method="POST" action="{{ route('studio.maintenance.store') }}">
@csrf
<div class="row g-3">
    <div class="col-md-12">
        <label class="form-label">Perangkat <span class="text-danger">*</span></label>
        <select name="studio_perangkat_id" class="form-select" required>
            <option value="">-- Pilih Perangkat --</option>
            @foreach($perangkats as $p)
            <option value="{{ $p->id }}" {{ (old('studio_perangkat_id', $perangkatTerpilih?->id)==$p->id)?'selected':'' }}>
                {{ $p->nama }} {{ $p->kode_inventaris ? '['.$p->kode_inventaris.']' : '' }}
            </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
        <input type="date" name="tanggal" class="form-control" value="{{ old('tanggal', now()->toDateString()) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Jenis Maintenance <span class="text-danger">*</span></label>
        <select name="jenis" class="form-select" required>
            <option value="">-- Pilih --</option>
            @foreach(\App\Models\StudioMaintenance::JENIS_LABEL as $val=>$label)
            <option value="{{ $val }}" {{ old('jenis')==$val?'selected':'' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Hasil <span class="text-danger">*</span></label>
        <select name="hasil" class="form-select" required>
            <option value="">-- Pilih --</option>
            @foreach(\App\Models\StudioMaintenance::HASIL_LABEL as $val=>$label)
            <option value="{{ $val }}" {{ old('hasil')==$val?'selected':'' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label class="form-label">Uraian Pekerjaan <span class="text-danger">*</span></label>
        <textarea name="uraian_pekerjaan" class="form-control" rows="4" required placeholder="Jelaskan pekerjaan maintenance yang dilakukan...">{{ old('uraian_pekerjaan') }}</textarea>
    </div>
    <div class="col-md-4">
        <label class="form-label">Biaya (Rp)</label>
        <input type="number" name="biaya" class="form-control" value="{{ old('biaya') }}" min="0" step="1000">
    </div>
    <div class="col-md-4">
        <label class="form-label">Rencana Maintenance Berikutnya</label>
        <input type="date" name="rencana_maintenance_berikutnya" class="form-control" value="{{ old('rencana_maintenance_berikutnya') }}">
    </div>

    {{-- Sparepart --}}
    <div class="col-12">
        <label class="form-label">Sparepart Terpakai</label>
        <div id="sparepart-container">
            <div class="row g-2 mb-2 sparepart-row">
                <div class="col-6"><input type="text" name="sparepart_nama[]" class="form-control form-control-sm" placeholder="Nama sparepart"></div>
                <div class="col-2"><input type="number" name="sparepart_jumlah[]" class="form-control form-control-sm" placeholder="Qty" min="1" value="1"></div>
                <div class="col-3"><input type="text" name="sparepart_satuan[]" class="form-control form-control-sm" placeholder="pcs/set/m"></div>
                <div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger btn-hapus-sp"><i class="bi bi-x"></i></button></div>
            </div>
        </div>
        <button type="button" id="btn-tambah-sp" class="btn btn-sm btn-outline-secondary mt-1"><i class="bi bi-plus me-1"></i>Tambah Sparepart</button>
    </div>

    <div class="col-12">
        <label class="form-label">Keterangan</label>
        <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan tambahan...">{{ old('keterangan') }}</textarea>
    </div>
</div>
<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Simpan</button>
    <a href="{{ route('studio.maintenance.index') }}" class="btn btn-outline-secondary">Batal</a>
</div>
</form>
</div>
</div>
</div>
</div>

<script>
document.getElementById('btn-tambah-sp').addEventListener('click', function(){
    const row = `<div class="row g-2 mb-2 sparepart-row">
        <div class="col-6"><input type="text" name="sparepart_nama[]" class="form-control form-control-sm" placeholder="Nama sparepart"></div>
        <div class="col-2"><input type="number" name="sparepart_jumlah[]" class="form-control form-control-sm" placeholder="Qty" min="1" value="1"></div>
        <div class="col-3"><input type="text" name="sparepart_satuan[]" class="form-control form-control-sm" placeholder="pcs/set/m"></div>
        <div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger btn-hapus-sp"><i class="bi bi-x"></i></button></div>
    </div>`;
    document.getElementById('sparepart-container').insertAdjacentHTML('beforeend', row);
});
document.addEventListener('click', function(e){
    if(e.target.closest('.btn-hapus-sp')){
        e.target.closest('.sparepart-row').remove();
    }
});
</script>
@endsection
