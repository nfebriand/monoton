@extends('layouts.app')
@section('title', $t->exists ? 'Edit Titik Grounding' : 'Tambah Titik Grounding')
@section('page-title', $t->exists ? 'Edit Titik Grounding' : 'Tambah Titik Grounding')
@section('content')
@php
    $nf = fn($v) => $v === null ? '' : rtrim(rtrim(number_format((float) $v, 3, '.', ''), '0'), '.');
    $px = old('pos_x', $nf($t->pos_x)); $py = old('pos_y', $nf($t->pos_y));
    $lain = $w->titiks->where('id', '!=', $t->id);
@endphp
<div class="row justify-content-center"><div class="col-12 col-lg-9">
@if($errors->any())<div class="alert alert-danger py-2"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li style="font-size:.82rem">{{ $e }}</li>@endforeach</ul></div>@endif
<form method="POST" enctype="multipart/form-data" action="{{ $t->exists ? route('grounding.titik.update', $t) : route('grounding.titik.store') }}">
@csrf @if($t->exists) @method('PUT') @else <input type="hidden" name="wilayah_id" value="{{ $w->id }}"> @endif

<div class="card mb-3"><div class="card-header"><h6 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Titik pada {{ $w->nama }}</h6></div>
<div class="card-body"><div class="row g-3">
    <div class="col-12 col-md-4"><label class="form-label">Kode Titik <span class="text-danger">*</span></label><input type="text" name="kode" class="form-control mono" maxlength="40" required value="{{ old('kode', $t->kode) }}"></div>
    <div class="col-12 col-md-8"><label class="form-label">Nama / Letak <span class="text-danger">*</span></label><input type="text" name="nama" class="form-control" maxlength="255" required value="{{ old('nama', $t->nama) }}" placeholder="mis. Grounding tower sisi timur"></div>
    <div class="col-12 col-md-6"><label class="form-label">Peruntukan</label>
        <input type="text" name="peruntukan" list="dl-peruntukan" class="form-control" maxlength="60" value="{{ old('peruntukan', $t->peruntukan) }}">
        <datalist id="dl-peruntukan">@foreach(\App\Models\GroundingTitik::PERUNTUKAN as $o)<option value="{{ $o }}">@endforeach</datalist></div>
    <div class="col-12 col-md-3"><label class="form-label">Status titik</label>
        <select name="status" class="form-select">@foreach(\App\Models\GroundingTitik::STATUS as $k => $l)<option value="{{ $k }}" {{ old('status', $t->status) === $k ? 'selected' : '' }}>{{ $l }}</option>@endforeach</select></div>
    <div class="col-12 col-md-3"><label class="form-label">Batas tahanan (&Omega;) <span class="text-danger">*</span></label><input type="text" inputmode="decimal" name="batas_ohm" class="form-control mono" required value="{{ old('batas_ohm', $nf($t->batas_ohm)) }}"></div>
</div></div></div>

<div class="card mb-3"><div class="card-header"><h6 class="mb-0"><i class="bi bi-pin-map me-2"></i>Posisi di Peta</h6></div>
<div class="card-body">
    @if($w->peta_url)
    <div class="text-muted mb-2" style="font-size:.78rem">Klik pada peta untuk menaruh/memindahkan titik ini.</div>
    <div id="gmap" style="position:relative;display:inline-block;max-width:100%;line-height:0;cursor:crosshair">
        <img id="gimg" src="{{ $w->peta_url }}" alt="" style="max-width:100%;height:auto;display:block;border-radius:6px" draggable="false">
        @foreach($lain as $o)@if($o->pos_x !== null)<span title="{{ $o->kode }}" style="position:absolute;left:{{ $o->pos_x }}%;top:{{ $o->pos_y }}%;transform:translate(-50%,-100%);background:#95a5a6;color:#fff;font-size:.58rem;line-height:1;padding:3px 5px;border-radius:9px;border:2px solid #fff;opacity:.8;pointer-events:none">{{ $o->kode }}</span>@endif @endforeach
        <span id="gpin" style="position:absolute;display:none;transform:translate(-50%,-100%);background:#0a3d62;color:#fff;font-size:.66rem;line-height:1;font-weight:700;padding:4px 7px;border-radius:10px;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.5);pointer-events:none">TITIK INI</span>
    </div>
    <div class="mt-2"><button type="button" class="btn btn-sm btn-outline-secondary" id="gclear">Hapus posisi</button></div>
    @else
    <div class="alert alert-info py-2 mb-2" style="font-size:.8rem">Wilayah ini belum memiliki gambar peta. Unggah denah di <a href="{{ route('grounding.wilayah.edit', $w) }}">Edit Wilayah</a> agar titik bisa ditandai di peta, atau isi koordinat GPS di bawah.</div>
    @endif
    <input type="hidden" name="pos_x" id="px" value="{{ $px }}"><input type="hidden" name="pos_y" id="py" value="{{ $py }}">
    <div class="row g-2 mt-1">
        <div class="col-6 col-md-3"><label class="form-label">Latitude</label><input type="text" inputmode="decimal" name="latitude" id="glat" class="form-control mono" value="{{ old('latitude', $nf($t->latitude)) }}"></div>
        <div class="col-6 col-md-3"><label class="form-label">Longitude</label><input type="text" inputmode="decimal" name="longitude" id="glng" class="form-control mono" value="{{ old('longitude', $nf($t->longitude)) }}"></div>
        <div class="col-12 col-md-4 d-flex align-items-end"><button type="button" class="btn btn-sm btn-outline-primary" id="ggps"><i class="bi bi-crosshair me-1"></i>Ambil lokasi saya</button><span id="gstat" class="ms-2 text-muted" style="font-size:.72rem"></span></div>
    </div>
</div></div>

<div class="card mb-3"><div class="card-header"><h6 class="mb-0"><i class="bi bi-rulers me-2"></i>Spesifikasi</h6></div>
<div class="card-body"><div class="row g-3">
    <div class="col-12 col-md-4"><label class="form-label">Tipe grounding</label><input type="text" name="tipe" list="dl-tipe" class="form-control" maxlength="40" value="{{ old('tipe', $t->tipe) }}"><datalist id="dl-tipe">@foreach(\App\Models\GroundingTitik::TIPE as $o)<option value="{{ $o }}">@endforeach</datalist></div>
    <div class="col-12 col-md-4"><label class="form-label">Jenis bahan</label><input type="text" name="bahan" list="dl-bahan" class="form-control" maxlength="60" value="{{ old('bahan', $t->bahan) }}"><datalist id="dl-bahan">@foreach(\App\Models\GroundingTitik::BAHAN as $o)<option value="{{ $o }}">@endforeach</datalist></div>
    <div class="col-6 col-md-4"><label class="form-label">Tahun pasang</label><input type="number" name="tahun_pasang" class="form-control" min="1950" max="{{ date('Y') + 1 }}" value="{{ old('tahun_pasang', $t->tahun_pasang) }}"></div>
    <div class="col-6 col-md-3"><label class="form-label">Kedalaman (m)</label><input type="text" inputmode="decimal" name="kedalaman_m" class="form-control mono" value="{{ old('kedalaman_m', $nf($t->kedalaman_m)) }}"></div>
    <div class="col-6 col-md-3"><label class="form-label">Diameter rod (mm)</label><input type="text" inputmode="decimal" name="diameter_mm" class="form-control mono" value="{{ old('diameter_mm', $nf($t->diameter_mm)) }}"></div>
    <div class="col-6 col-md-3"><label class="form-label">Panjang kabel (m)</label><input type="text" inputmode="decimal" name="panjang_kabel_m" class="form-control mono" value="{{ old('panjang_kabel_m', $nf($t->panjang_kabel_m)) }}"></div>
    <div class="col-6 col-md-3"><label class="form-label">Ukuran kabel (mm&sup2;)</label><input type="text" inputmode="decimal" name="ukuran_kabel_mm2" class="form-control mono" value="{{ old('ukuran_kabel_mm2', $nf($t->ukuran_kabel_mm2)) }}"></div>
    <div class="col-6 col-md-3"><label class="form-label">Jumlah elektroda</label><input type="number" name="jumlah_elektroda" class="form-control" min="1" value="{{ old('jumlah_elektroda', $t->jumlah_elektroda) }}"></div>
    <div class="col-12"><label class="form-label">Catatan</label><textarea name="catatan" rows="2" class="form-control" maxlength="2000">{{ old('catatan', $t->catatan) }}</textarea></div>
</div></div></div>

<div class="card mb-3"><div class="card-header"><h6 class="mb-0"><i class="bi bi-camera me-2"></i>Foto Grounding</h6></div>
<div class="card-body">@include('components.foto-multi-ket', ['capName' => $t->exists ? 'foto_keterangan_baru' : 'foto_keterangan', 'existing' => $t->exists ? $t->fotos : null])</div></div>

<div class="d-flex gap-2 mb-4"><button class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Simpan Titik</button>
    <a href="{{ $t->exists ? route('grounding.titik.show', $t) : route('grounding.wilayah.show', $w) }}" class="btn btn-outline-secondary">Batal</a></div>
</form>
</div></div>
@endsection

@push('scripts')
<script>
(function(){
    const img = document.getElementById('gimg'), pin = document.getElementById('gpin');
    const px = document.getElementById('px'), py = document.getElementById('py');
    function tampil(){
        if (!pin) return;
        if (px.value !== '' && py.value !== '') { pin.style.display = ''; pin.style.left = px.value + '%'; pin.style.top = py.value + '%'; }
        else pin.style.display = 'none';
    }
    if (img) {
        document.getElementById('gmap').addEventListener('click', function(e){
            const r = img.getBoundingClientRect();
            const x = (e.clientX - r.left) / r.width * 100, y = (e.clientY - r.top) / r.height * 100;
            if (x < 0 || x > 100 || y < 0 || y > 100) return;
            px.value = x.toFixed(3); py.value = y.toFixed(3); tampil();
        });
        document.getElementById('gclear').addEventListener('click', function(){ px.value = ''; py.value = ''; tampil(); });
    }
    tampil();
    document.getElementById('ggps').addEventListener('click', function(){
        const st = document.getElementById('gstat');
        if (!navigator.geolocation) { st.textContent = 'Perangkat tidak mendukung GPS.'; return; }
        st.textContent = 'Mencari lokasi...';
        navigator.geolocation.getCurrentPosition(function(p){
            document.getElementById('glat').value = p.coords.latitude.toFixed(7);
            document.getElementById('glng').value = p.coords.longitude.toFixed(7);
            st.textContent = 'Akurasi sekitar ' + Math.round(p.coords.accuracy) + ' m';
        }, function(){ st.textContent = 'Gagal mengambil lokasi (izinkan akses lokasi).'; }, {enableHighAccuracy: true, timeout: 15000});
    });
})();
</script>
@endpush
