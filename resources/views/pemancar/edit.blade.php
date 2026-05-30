@extends('layouts.app')
@section('title','Edit Pemancar')
@section('page-title','Edit Data Pemancar')

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-xl-10">
<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-pencil-square text-warning"></i>
        Edit: <strong>{{ $pemancar->nama_stasiun }}</strong>
        <a href="{{ route('pemancar.show',$pemancar) }}" class="btn btn-sm btn-outline-secondary ms-auto">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
    <div class="card-body">
    <form action="{{ route('pemancar.update',$pemancar) }}" method="POST" enctype="multipart/form-data">
    @csrf @method('PUT')

    <h6 class="section-title mb-3">📡 Identitas Pemancar</h6>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <label class="form-label">Nama Stasiun <span class="text-danger">*</span></label>
            <input type="text" name="nama_stasiun" class="form-control"
                   value="{{ old('nama_stasiun',$pemancar->nama_stasiun) }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Merk <span class="text-danger">*</span></label>
            <input type="text" name="merk" class="form-control"
                   value="{{ old('merk',$pemancar->merk) }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Tipe Unit <span class="text-danger">*</span></label>
            <input type="text" name="tipe_unit" class="form-control"
                   value="{{ old('tipe_unit',$pemancar->tipe_unit) }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Tipe Komponen <span class="text-danger">*</span></label>
            <select name="tipe_komponen" class="form-select" required>
                <option value="solid_state" {{ old('tipe_komponen',$pemancar->tipe_komponen)==='solid_state'?'selected':'' }}>Solid State</option>
                <option value="tabung"      {{ old('tipe_komponen',$pemancar->tipe_komponen)==='tabung'?'selected':'' }}>Tabung</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Modulasi <span class="text-danger">*</span></label>
            <select name="modulasi" class="form-select" required>
                <option value="FM" {{ old('modulasi',$pemancar->modulasi)==='FM'?'selected':'' }}>FM</option>
                <option value="AM" {{ old('modulasi',$pemancar->modulasi)==='AM'?'selected':'' }}>AM</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Frekuensi</label>
            <div class="input-group">
                <input type="number" name="frekuensi" class="form-control mono"
                       step="0.001" value="{{ old('frekuensi',$pemancar->frekuensi) }}">
                <span class="input-group-text">{{ $pemancar->modulasi==='AM'?'kHz':'MHz' }}</span>
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label">No. Izin</label>
            <input type="text" name="nomor_izin" class="form-control"
                   value="{{ old('nomor_izin',$pemancar->nomor_izin) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Tanggal Instalasi</label>
            <input type="date" name="tanggal_instalasi" class="form-control"
                   value="{{ old('tanggal_instalasi',$pemancar->tanggal_instalasi?->format('Y-m-d')) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="is_active" class="form-select">
                <option value="1" {{ $pemancar->is_active?'selected':'' }}>Aktif</option>
                <option value="0" {{ !$pemancar->is_active?'selected':'' }}>Nonaktif</option>
            </select>
        </div>
    </div>

    <h6 class="section-title mb-3">⚡ Spesifikasi Teknis</h6>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label">Kapasitas Output Final (Watt) <span class="text-danger">*</span></label>
            <div class="input-group">
                <input type="number" name="kapasitas_output_final" class="form-control mono"
                       step="0.01" value="{{ old('kapasitas_output_final',$pemancar->kapasitas_output_final) }}" required>
                <span class="input-group-text">W</span>
            </div>
        </div>
        <div class="col-md-4">
            <label class="form-label">Tipe Exciter</label>
            <input type="text" name="tipe_exciter" class="form-control"
                   value="{{ old('tipe_exciter',$pemancar->tipe_exciter) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Tipe Driver</label>
            <input type="text" name="tipe_driver" class="form-control"
                   value="{{ old('tipe_driver',$pemancar->tipe_driver) }}">
        </div>
    </div>

    <h6 class="section-title mb-3">📍 Lokasi Pemancar</h6>
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label">Nama Lokasi</label>
            <input type="text" name="lokasi" class="form-control"
                   value="{{ old('lokasi',$pemancar->lokasi) }}" placeholder="Gedung Air, Bukit Randu...">
        </div>
        <div class="col-md-8">
            <label class="form-label">Alamat Lengkap</label>
            <input type="text" name="alamat_lokasi" class="form-control"
                   value="{{ old('alamat_lokasi',$pemancar->alamat_lokasi) }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Latitude</label>
            <input type="number" name="latitude" id="lat" class="form-control mono"
                   step="0.0000001" value="{{ old('latitude',$pemancar->latitude) }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Longitude</label>
            <input type="number" name="longitude" id="lng" class="form-control mono"
                   step="0.0000001" value="{{ old('longitude',$pemancar->longitude) }}">
        </div>
    </div>
    @if($pemancar->latitude && $pemancar->longitude)
    <div class="mb-4">
        <div id="map" style="height:220px;border-radius:8px;border:1px solid #dfe6e9"></div>
        <div class="form-text">Klik peta atau seret marker untuk ubah koordinat.</div>
    </div>
    @endif

    <div class="mb-4">
        <label class="form-label">Keterangan</label>
        <textarea name="keterangan" class="form-control" rows="2">{{ old('keterangan',$pemancar->keterangan) }}</textarea>
    </div>

    {{-- Foto Existing --}}
    @if($pemancar->fotos->isNotEmpty())
    <h6 class="section-title mb-3">📷 Foto Saat Ini ({{ $pemancar->fotos->count() }}) — klik untuk preview</h6>
    <div class="foto-edit-grid mb-4">
        @foreach($pemancar->fotos as $foto)
        <div class="foto-edit-item" id="fei-{{ $foto->id }}">
            <img src="{{ $foto->url }}"
                 onerror="this.parentElement.style.background='#eee'"
                 onclick="bukaLightbox('{{ $foto->url }}','{{ addslashes($foto->keterangan??$pemancar->nama_stasiun) }}')"
                 style="width:100%;height:100%;object-fit:cover;cursor:zoom-in;display:block">
            <div class="foto-edit-actions">
                <label class="d-flex align-items-center gap-1 text-white" style="font-size:.68rem;cursor:pointer">
                    <input type="checkbox" name="hapus_foto[]" value="{{ $foto->id }}" class="form-check-input mt-0"
                           onchange="this.closest('.foto-edit-item').classList.toggle('akan-dihapus',this.checked)">
                    Hapus
                </label>
            </div>
            @if($foto->keterangan)
            <div style="position:absolute;bottom:24px;left:0;right:0;
                 background:rgba(0,0,0,.5);color:#fff;font-size:.6rem;
                 padding:.15rem .3rem;text-align:center;white-space:nowrap;
                 overflow:hidden;text-overflow:ellipsis">{{ $foto->keterangan }}</div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    <h6 class="section-title mb-3">📷 Tambah Foto Baru</h6>
    <div class="mb-4">
        <input type="file" name="fotos[]" id="inputFoto" class="form-control"
               multiple accept="image/jpeg,image/png,image/jpg,image/webp">
        <div class="form-text">Format: JPG, PNG, WEBP. Maks 5MB per foto.</div>
        <div id="previewFoto" class="foto-edit-grid mt-3"></div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        <button type="submit" class="btn btn-primary-custom">
            <i class="bi bi-save me-1"></i>Simpan Perubahan
        </button>
        <a href="{{ route('pemancar.show',$pemancar) }}" class="btn btn-outline-secondary">Batal</a>
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
.foto-edit-actions{position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,.65);padding:.3rem .5rem;}
.akan-dihapus{opacity:.3;border-color:#ee5a24!important;}
.btn-primary-custom{background:var(--primary);color:#fff;border:none;border-radius:7px;padding:.42rem 1.05rem;font-weight:600;font-size:.82rem;}
.btn-primary-custom:hover{opacity:.88;color:#fff;}
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.getElementById('inputFoto').addEventListener('change',function(){
    const c=document.getElementById('previewFoto');c.innerHTML='';
    Array.from(this.files).forEach(f=>{
        const r=new FileReader();
        r.onload=e=>{
            const d=document.createElement('div');
            d.className='foto-edit-item';
            d.innerHTML=`<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;cursor:zoom-in"
                          onclick="bukaLightbox('${e.target.result}','Preview')">`;
            c.appendChild(d);
        };
        r.readAsDataURL(f);
    });
});
@if($pemancar->latitude && $pemancar->longitude)
const map=L.map('map').setView([{{ $pemancar->latitude }},{{ $pemancar->longitude }}],14);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
const marker=L.marker([{{ $pemancar->latitude }},{{ $pemancar->longitude }}],{draggable:true}).addTo(map);
marker.on('dragend',e=>{
    document.getElementById('lat').value=e.target.getLatLng().lat.toFixed(7);
    document.getElementById('lng').value=e.target.getLatLng().lng.toFixed(7);
});
map.on('click',e=>{
    marker.setLatLng(e.latlng);
    document.getElementById('lat').value=e.latlng.lat.toFixed(7);
    document.getElementById('lng').value=e.latlng.lng.toFixed(7);
});
@endif
</script>
@endpush
