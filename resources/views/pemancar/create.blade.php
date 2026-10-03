@extends('layouts.app')
@section('title','Tambah Pemancar')
@section('page-title','Tambah Pemancar Baru')

@section('content')
@php $lokasiList = \App\Models\Lokasi::where('divisi','transmisi')->orderBy('nama')->get(); @endphp
<div class="row justify-content-center">
<div class="col-12 col-lg-8">
<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-broadcast-pin text-primary"></i>Form Tambah Pemancar
        <a href="{{ route('pemancar.index') }}" class="btn btn-sm btn-outline-secondary ms-auto">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
    <div class="card-body">
    <form action="{{ route('pemancar.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-8">
            <label class="form-label">Nama Stasiun <span class="text-danger">*</span></label>
            <input type="text" name="nama_stasiun" class="form-control" value="{{ old('nama_stasiun') }}" required>
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label">Lokasi</label>
            <select name="lokasi" id="selectLokasi" class="form-select" onchange="toggleLokasiCustom(this)">
                <option value="">— Pilih Lokasi —</option>
                @foreach($lokasiList as $lok)
				<option value="{{ $lok }}" {{ old('lokasi', $defaultLokasi ?? '')==$lok?'selected':'' }}></option>
                @endforeach
                <option value="__custom__">Lainnya (isi manual)</option>
            </select>
            <input type="text" id="inputLokasiCustom" class="form-control mt-2" style="display:none" placeholder="Ketik lokasi...">
        </div>

        <div class="col-6 col-md-3">
            <label class="form-label">Modulasi <span class="text-danger">*</span></label>
            <select name="modulasi" class="form-select" required>
                <option value="FM" {{ old('modulasi')=='FM'?'selected':'' }}>FM</option>
                <option value="AM" {{ old('modulasi')=='AM'?'selected':'' }}>AM</option>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label">Frekuensi</label>
            <input type="text" name="frekuensi" class="form-control" value="{{ old('frekuensi') }}" placeholder="cth. 95.5 MHz">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label">Merk</label>
            <input type="text" name="merk" class="form-control" value="{{ old('merk') }}">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label">Tipe Unit</label>
            <input type="text" name="tipe_unit" class="form-control" value="{{ old('tipe_unit') }}">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label">Tahun Pembuatan</label>
            <input type="number" name="tahun_pembuatan" class="form-control mono" value="{{ old('tahun_pembuatan') }}">
        </div>
    </div>

    <h6 class="section-title mb-3">⚡ Kapasitas Output</h6>
    <div class="row g-3 mb-4">
        <div class="col-4">
            <label class="form-label">Final PA (W) <span class="text-danger">*</span></label>
            <input type="number" step="0.1" name="kapasitas_output_final" class="form-control mono" value="{{ old('kapasitas_output_final') }}" required>
        </div>
        <div class="col-4">
            <label class="form-label">Driver (W)</label>
            <input type="number" step="0.1" name="kapasitas_output_driver" class="form-control mono" value="{{ old('kapasitas_output_driver') }}">
        </div>
        <div class="col-4">
            <label class="form-label">Exciter (W)</label>
            <input type="number" step="0.1" name="kapasitas_output_exciter" class="form-control mono" value="{{ old('kapasitas_output_exciter') }}">
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label">Keterangan</label>
        <textarea name="keterangan" class="form-control" rows="3">{{ old('keterangan') }}</textarea>
    </div>

    <div class="mb-4">
        <label class="form-label">Foto Pemancar</label>
        <input type="file" name="fotos[]" class="form-control" multiple accept="image/jpeg,image/png,image/jpg,image/webp">
        <div class="form-text">Bisa pilih beberapa foto sekaligus.</div>
    </div>

    <div class="mb-4">
        <div class="form-check form-switch">
            <input type="checkbox" name="is_active" class="form-check-input" value="1" checked>
            <label class="form-check-label">Status Aktif</label>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Simpan</button>
        <a href="{{ route('pemancar.index') }}" class="btn btn-outline-secondary">Batal</a>
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
    const c = document.getElementById('inputLokasiCustom');
    c.style.display = sel.value==='__custom__'?'block':'none';
    if(sel.value==='__custom__') c.focus();
}
document.querySelector('form').addEventListener('submit', function(){
    const sel = document.getElementById('selectLokasi');
    const c   = document.getElementById('inputLokasiCustom');
    if(sel && sel.value==='__custom__' && c.value){
        const o=document.createElement('option'); o.value=c.value; o.selected=true;
        sel.appendChild(o); sel.value=c.value;
    }
});
</script>
@endpush
