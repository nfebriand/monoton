@extends('layouts.app')
@section('title','Tambah Pemancar')
@section('page-title','Tambah Data Pemancar')

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-xl-10">
<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-broadcast-pin text-primary"></i> Form Data Pemancar Baru
        <a href="{{ route('pemancar.index') }}" class="btn btn-sm btn-outline-secondary ms-auto">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
    <div class="card-body">
    <form action="{{ route('pemancar.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <h6 class="section-title mb-3">📡 Identitas Pemancar</h6>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <label class="form-label">Nama Stasiun <span class="text-danger">*</span></label>
            <input type="text" name="nama_stasiun" class="form-control"
                   value="{{ old('nama_stasiun') }}" required placeholder="LPPL Radio Lampung">
        </div>
        <div class="col-md-3">
            <label class="form-label">Merk <span class="text-danger">*</span></label>
            <input type="text" name="merk" class="form-control"
                   value="{{ old('merk') }}" required placeholder="Nautel, BW Broadcast...">
        </div>
        <div class="col-md-3">
            <label class="form-label">Tipe Unit <span class="text-danger">*</span></label>
            <input type="text" name="tipe_unit" class="form-control"
                   value="{{ old('tipe_unit') }}" required placeholder="VS300, TX1000...">
        </div>
        <div class="col-md-3">
            <label class="form-label">Tipe Komponen <span class="text-danger">*</span></label>
            <select name="tipe_komponen" class="form-select" required>
                <option value="">– Pilih –</option>
                <option value="solid_state" {{ old('tipe_komponen')==='solid_state'?'selected':'' }}>Solid State</option>
                <option value="tabung"      {{ old('tipe_komponen')==='tabung'?'selected':'' }}>Tabung</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Modulasi <span class="text-danger">*</span></label>
            <select name="modulasi" id="selModulasi" class="form-select" required>
                <option value="">– Pilih –</option>
                <option value="FM" {{ old('modulasi')==='FM'?'selected':'' }}>FM</option>
                <option value="AM" {{ old('modulasi')==='AM'?'selected':'' }}>AM</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Frekuensi</label>
            <div class="input-group">
                <input type="number" name="frekuensi" class="form-control mono"
                       step="0.001" value="{{ old('frekuensi') }}" placeholder="95.600">
                <span class="input-group-text" id="satuanFrek">MHz</span>
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label">No. Izin</label>
            <input type="text" name="nomor_izin" class="form-control"
                   value="{{ old('nomor_izin') }}" placeholder="PM/00001/2024">
        </div>
        <div class="col-md-3">
            <label class="form-label">Tanggal Instalasi</label>
            <input type="date" name="tanggal_instalasi" class="form-control"
                   value="{{ old('tanggal_instalasi') }}">
        </div>
    </div>

    <h6 class="section-title mb-3">⚡ Spesifikasi Teknis</h6>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label">Kapasitas Output Final (Watt) <span class="text-danger">*</span></label>
            <div class="input-group">
                <input type="number" name="kapasitas_output_final" class="form-control mono"
                       step="0.01" value="{{ old('kapasitas_output_final') }}" required placeholder="300">
                <span class="input-group-text">W</span>
            </div>
        </div>
        <div class="col-md-4">
            <label class="form-label">Tipe Exciter</label>
            <input type="text" name="tipe_exciter" class="form-control"
                   value="{{ old('tipe_exciter') }}" placeholder="Nautel NV10 Exciter">
        </div>
        <div class="col-md-4">
            <label class="form-label">Tipe Driver</label>
            <input type="text" name="tipe_driver" class="form-control"
                   value="{{ old('tipe_driver') }}" placeholder="Internal Driver Module">
        </div>
    </div>

    <h6 class="section-title mb-3">📍 Lokasi Pemancar</h6>
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label">Nama Lokasi</label>
            <input type="text" name="lokasi" class="form-control"
                   value="{{ old('lokasi') }}" placeholder="Bukit Randu, Gedung Air...">
        </div>
        <div class="col-md-8">
            <label class="form-label">Alamat Lengkap</label>
            <input type="text" name="alamat_lokasi" class="form-control"
                   value="{{ old('alamat_lokasi') }}" placeholder="Jl. Raden Intan No.1, Bandar Lampung">
        </div>
        <div class="col-md-2">
            <label class="form-label">Latitude</label>
            <input type="number" name="latitude" id="lat" class="form-control mono"
                   step="0.0000001" value="{{ old('latitude') }}" placeholder="-5.4294">
        </div>
        <div class="col-md-2">
            <label class="form-label">Longitude</label>
            <input type="number" name="longitude" id="lng" class="form-control mono"
                   step="0.0000001" value="{{ old('longitude') }}" placeholder="105.2614">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="button" class="btn btn-outline-secondary btn-sm w-100"
                    onclick="tampilkanPeta()">
                <i class="bi bi-map me-1"></i>Preview Peta
            </button>
        </div>
    </div>
    <div id="mapContainer" class="mb-4" style="display:none">
        <div id="map" style="height:240px;border-radius:8px;border:1px solid #dfe6e9"></div>
        <div class="form-text">Klik peta atau seret marker untuk ubah koordinat.</div>
    </div>

    <div class="mb-4">
        <label class="form-label">Keterangan</label>
        <textarea name="keterangan" class="form-control" rows="2"
                  placeholder="Informasi tambahan...">{{ old('keterangan') }}</textarea>
    </div>

    <h6 class="section-title mb-3">📷 Foto Pemancar — klik preview untuk memperbesar</h6>
    <div class="mb-4">
        <input type="file" name="fotos[]" id="inputFoto" class="form-control"
               multiple accept="image/jpeg,image/png,image/webp">
        <div class="form-text">Format: JPG, PNG, WEBP. Maks 5MB per foto.</div>
        <div id="previewFoto" class="foto-edit-grid mt-3"></div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        <button type="submit" class="btn btn-primary-custom">
            <i class="bi bi-save me-1"></i>Simpan Data Pemancar
        </button>
        <a href="{{ route('pemancar.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
    </form>
    </div>
</div>
</div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
.foto-edit-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:.75rem;}
.foto-edit-item{position:relative;aspect-ratio:1;border-radius:8px;overflow:hidden;border:2px solid #dfe6e9;}
.btn-primary-custom{background:var(--primary);color:#fff;border:none;border-radius:7px;padding:.42rem 1.05rem;font-weight:600;font-size:.82rem;}
.btn-primary-custom:hover{opacity:.88;color:#fff;}
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.getElementById('selModulasi').addEventListener('change',function(){
    document.getElementById('satuanFrek').textContent=this.value==='AM'?'kHz':'MHz';
});

document.getElementById('inputFoto').addEventListener('change',function(){
    const c=document.getElementById('previewFoto');
    c.innerHTML='';
    Array.from(this.files).forEach((f,i)=>{
        const r=new FileReader();
        r.onload=e=>{
            const d=document.createElement('div');
            d.className='foto-edit-item';
            const src=e.target.result;
            d.innerHTML=`<img src="${src}"
                style="width:100%;height:100%;object-fit:cover;cursor:zoom-in;display:block;transition:opacity .15s"
                onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'"
                onclick="bukaLightbox('${src}','Foto ${i+1}')">`;
            c.appendChild(d);
        };
        r.readAsDataURL(f);
    });
});

let map,marker;
function tampilkanPeta(){
    const lat=parseFloat(document.getElementById('lat').value)||(-5.4);
    const lng=parseFloat(document.getElementById('lng').value)||(105.26);
    document.getElementById('mapContainer').style.display='block';
    if(!map){
        map=L.map('map').setView([lat,lng],13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        marker=L.marker([lat,lng],{draggable:true}).addTo(map);
        marker.on('dragend',e=>{
            document.getElementById('lat').value=e.target.getLatLng().lat.toFixed(7);
            document.getElementById('lng').value=e.target.getLatLng().lng.toFixed(7);
        });
        map.on('click',e=>{
            marker.setLatLng(e.latlng);
            document.getElementById('lat').value=e.latlng.lat.toFixed(7);
            document.getElementById('lng').value=e.latlng.lng.toFixed(7);
        });
    } else {
        map.setView([lat,lng],13);
        marker.setLatLng([lat,lng]);
        map.invalidateSize();
    }
}
</script>
@endpush