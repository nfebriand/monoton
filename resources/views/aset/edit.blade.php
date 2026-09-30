@extends('layouts.app')
@section('title','Edit Aset')
@section('page-title','Edit Aset')

@section('content')
@php
    $lokasiVal = old('lokasi', $aset->lokasi ?? '');
    $isCustom  = $lokasiVal && !$lokasiList->pluck('nama')->contains($lokasiVal);
@endphp
<div class="row justify-content-center">
<div class="col-12 col-lg-8">
<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-pencil-square text-warning"></i>Edit: <strong>{{ $aset->nama }}</strong>
        <span class="badge bg-secondary ms-auto mono">{{ $aset->kode_aset }}</span>
    </div>
    <div class="card-body">
    <form action="{{ route('aset.update',$aset) }}" method="POST" enctype="multipart/form-data">
    @csrf @method('PUT')

    <div class="row g-3 mb-4">
        <div class="col-12">
            <label class="form-label">Nama Aset <span class="text-danger">*</span></label>
            <input type="text" name="nama" class="form-control" value="{{ old('nama',$aset->nama) }}" required>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Kategori</label>
            <select name="aset_kategori_id" class="form-select">
                <option value="">— Pilih Kategori —</option>
                @foreach($kategoris as $k)
                <option value="{{ $k->id }}" {{ old('aset_kategori_id',$aset->aset_kategori_id)==$k->id?'selected':'' }}>{{ $k->nama }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Lokasi</label>
            <select name="lokasi" id="selectLokasi" class="form-select" onchange="toggleLokasiCustom(this)">
                <option value="">— Pilih Lokasi —</option>
                @foreach($lokasiList as $lok)
                <option value="{{ $lok->nama }}" {{ (!$isCustom && $lokasiVal===$lok->nama)?'selected':'' }}>{{ $lok->nama }}</option>
                @endforeach
                <option value="__custom__" {{ $isCustom?'selected':'' }}>Lainnya (isi manual)</option>
            </select>
            <input type="text" id="inputLokasiCustom" class="form-control mt-2" style="display:{{ $isCustom?'block':'none' }}" value="{{ $isCustom?$lokasiVal:'' }}" placeholder="Ketik lokasi...">
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Merk</label>
            <input type="text" name="merk" class="form-control" value="{{ old('merk',$aset->merk) }}">
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Tipe/Model</label>
            <input type="text" name="tipe_model" class="form-control" value="{{ old('tipe_model',$aset->tipe_model) }}">
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label">No. Seri</label>
            <input type="text" name="no_seri" class="form-control mono" value="{{ old('no_seri',$aset->no_seri) }}">
        </div>
    </div>

    <h6 class="section-title mb-3">💰 Perolehan</h6>
    <div class="row g-3 mb-4">
        <div class="col-6">
            <label class="form-label">Tanggal Perolehan</label>
            <input type="date" name="tanggal_perolehan" class="form-control" value="{{ old('tanggal_perolehan',$aset->tanggal_perolehan?->format('Y-m-d')) }}">
        </div>
        <div class="col-6">
            <label class="form-label">Harga Perolehan (Rp)</label>
            <input type="number" step="1000" name="harga_perolehan" class="form-control mono" value="{{ old('harga_perolehan',$aset->harga_perolehan) }}">
        </div>
    </div>

    <h6 class="section-title mb-3">🔧 Kondisi & Maintenance</h6>
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <label class="form-label">Kondisi <span class="text-danger">*</span></label>
            <select name="kondisi" class="form-select" required>
                @foreach(\App\Models\Aset::KONDISI_LABEL as $val => $label)
                <option value="{{ $val }}" {{ old('kondisi',$aset->kondisi)==$val?'selected':'' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Status</label>
            <select name="status" class="form-select" required>
                <option value="aktif" {{ old('status',$aset->status)=='aktif'?'selected':'' }}>Aktif</option>
                <option value="nonaktif" {{ old('status',$aset->status)=='nonaktif'?'selected':'' }}>Non-aktif</option>
            </select>
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label">Interval Maintenance (hari)</label>
            <input type="number" name="interval_maintenance_hari" class="form-control mono" value="{{ old('interval_maintenance_hari',$aset->interval_maintenance_hari) }}">
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label">Keterangan</label>
        <textarea name="keterangan" class="form-control" rows="3">{{ old('keterangan',$aset->keterangan) }}</textarea>
    </div>

    @if($aset->fotos->isNotEmpty())
    <div class="mb-3">
        <label class="form-label">Foto Saat Ini</label>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:.5rem">
            @foreach($aset->fotos as $foto)
            <div style="position:relative;aspect-ratio:1;border-radius:6px;overflow:hidden;border:2px solid var(--border)">
                <img src="{{ $foto->url }}" style="width:100%;height:100%;object-fit:cover" onerror="this.parentElement.style.background='var(--bg)'">
                <label style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,.6);color:#fff;font-size:.62rem;padding:.2rem;text-align:center;cursor:pointer">
                    <input type="checkbox" name="hapus_foto[]" value="{{ $foto->id }}" class="form-check-input mt-0"> Hapus
                </label>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="mb-4">
        <label class="form-label">Tambah Foto Baru</label>
        <input type="file" name="fotos[]" class="form-control" multiple accept="image/jpeg,image/png,image/jpg,image/webp">
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-save me-1"></i>Simpan Perubahan</button>
        <a href="{{ route('aset.show',$aset) }}" class="btn btn-outline-secondary">Batal</a>
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
