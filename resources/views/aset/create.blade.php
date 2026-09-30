@extends('layouts.app')
@section('title','Tambah Aset')
@section('page-title','Tambah Aset Baru')

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-8">
<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-box-seam text-primary"></i>Form Tambah Aset
        <span class="badge bg-secondary ms-auto mono">{{ $kodeBaru }}</span>
    </div>
    <div class="card-body">
    <form action="{{ route('aset.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="row g-3 mb-4">
        <div class="col-12">
            <label class="form-label">Nama Aset <span class="text-danger">*</span></label>
            <input type="text" name="nama" class="form-control" value="{{ old('nama') }}" required placeholder="AC Split 1PK Ruang Server">
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Kategori</label>
            <select name="aset_kategori_id" class="form-select">
                <option value="">— Pilih Kategori —</option>
                @foreach($kategoris as $k)
                <option value="{{ $k->id }}" {{ old('aset_kategori_id')==$k->id?'selected':'' }}>{{ $k->nama }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Lokasi</label>
            <select name="lokasi" id="selectLokasi" class="form-select" onchange="toggleLokasiCustom(this)">
                <option value="">— Pilih Lokasi —</option>
                @foreach($lokasiList as $lok)
                <option value="{{ $lok->nama }}" {{ old('lokasi')===$lok->nama?'selected':'' }}>{{ $lok->nama }}</option>
                @endforeach
                <option value="__custom__">Lainnya (isi manual)</option>
            </select>
            <input type="text" id="inputLokasiCustom" class="form-control mt-2" style="display:none" placeholder="Ketik lokasi...">
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Merk</label>
            <input type="text" name="merk" class="form-control" value="{{ old('merk') }}">
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Tipe/Model</label>
            <input type="text" name="tipe_model" class="form-control" value="{{ old('tipe_model') }}">
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label">No. Seri</label>
            <input type="text" name="no_seri" class="form-control mono" value="{{ old('no_seri') }}">
        </div>
    </div>

    <h6 class="section-title mb-3">💰 Perolehan</h6>
    <div class="row g-3 mb-4">
        <div class="col-6">
            <label class="form-label">Tanggal Perolehan</label>
            <input type="date" name="tanggal_perolehan" class="form-control" value="{{ old('tanggal_perolehan') }}">
        </div>
        <div class="col-6">
            <label class="form-label">Harga Perolehan (Rp)</label>
            <input type="number" step="1000" name="harga_perolehan" class="form-control mono" value="{{ old('harga_perolehan') }}">
        </div>
    </div>

    <h6 class="section-title mb-3">🔧 Kondisi & Maintenance</h6>
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <label class="form-label">Kondisi <span class="text-danger">*</span></label>
            <select name="kondisi" class="form-select" required>
                @foreach(\App\Models\Aset::KONDISI_LABEL as $val => $label)
                <option value="{{ $val }}" {{ old('kondisi','baik')==$val?'selected':'' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Interval Maintenance (hari)</label>
            <input type="number" name="interval_maintenance_hari" class="form-control mono" value="{{ old('interval_maintenance_hari') }}" placeholder="cth. 90">
            <div class="form-text">Kosongkan untuk pakai default kategori</div>
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label">Keterangan</label>
        <textarea name="keterangan" class="form-control" rows="3">{{ old('keterangan') }}</textarea>
    </div>

    <div class="mb-4">
        <label class="form-label">Foto Aset</label>
        <input type="file" name="fotos[]" class="form-control" multiple accept="image/jpeg,image/png,image/jpg,image/webp">
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Simpan</button>
        <a href="{{ route('aset.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
    </form>
    </div>
</div>
</div>
</div>
@endsection

@push('scripts')
<script>
function toggleLokasiCustom(sel){
    const c=document.getElementById('inputLokasiCustom');
    c.style.display=sel.value==='__custom__'?'block':'none';
    if(sel.value==='__custom__') c.focus();
}
document.querySelector('form').addEventListener('submit', function(){
    const sel=document.getElementById('selectLokasi');
    const c=document.getElementById('inputLokasiCustom');
    if(sel.value==='__custom__'&&c.value){
        const o=document.createElement('option');o.value=c.value;o.selected=true;sel.appendChild(o);sel.value=c.value;
    }
});
</script>
@endpush
