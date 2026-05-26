@extends('layouts.app')
@section('title','Tambah Eviden')
@section('page-title','Tambah Catatan Eviden')

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-xl-9">
<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-camera text-primary"></i> Form Catatan Eviden Baru
        <a href="{{ route('eviden.index') }}" class="btn btn-sm btn-outline-secondary ms-auto">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
    <div class="card-body">
    <form action="{{ route('eviden.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <h6 class="section-title mb-3">📋 Informasi Kegiatan</h6>
    <div class="row g-3 mb-4">
        <div class="col-12">
            <label class="form-label">Judul Kegiatan <span class="text-danger">*</span></label>
            <input type="text" name="judul" class="form-control"
                   value="{{ old('judul') }}" placeholder="Pemeliharaan rutin, pemasangan antena, dll..." required>
        </div>
        <div class="col-12">
            <label class="form-label">Deskripsi / Uraian Pekerjaan</label>
            <textarea name="deskripsi" class="form-control" rows="4"
                      placeholder="Uraikan kegiatan yang dilakukan secara detail...">{{ old('deskripsi') }}</textarea>
        </div>
    </div>

    <h6 class="section-title mb-3">⏰ Waktu & Lokasi</h6>
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <label class="form-label">Tanggal <span class="text-danger">*</span></label>
            <input type="date" name="tanggal" class="form-control"
                   value="{{ old('tanggal', now()->toDateString()) }}" required>
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Jam Mulai <span class="text-danger">*</span></label>
            <input type="time" name="jam_mulai" class="form-control mono"
                   value="{{ old('jam_mulai', now()->format('H:i')) }}" required>
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Jam Selesai <span class="text-danger">*</span></label>
            <input type="time" name="jam_selesai" class="form-control mono"
                   value="{{ old('jam_selesai') }}" required>
        </div>
        <div class="col-12">
            <label class="form-label">Lokasi Kegiatan</label>
            <input type="text" name="lokasi" class="form-control"
                   value="{{ old('lokasi') }}" placeholder="Gedung Air, Bukit Randu, dll...">
        </div>
    </div>

    <h6 class="section-title mb-3">👥 Personil Terlibat</h6>
    <div class="row g-3 mb-4">
        <div class="col-12">
            <label class="form-label">Operator Terlibat</label>
            <div class="row g-2">
                @foreach($operators as $op)
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="form-check p-2 rounded" style="background:#f8fafc;border:1px solid #eee">
                        <input type="checkbox" name="operator_ids[]" value="{{ $op->id }}"
                               id="op{{ $op->id }}" class="form-check-input"
                               {{ collect(old('operator_ids',[])) ->contains($op->id) ? 'checked' : '' }}>
                        <label for="op{{ $op->id }}" class="form-check-label" style="font-size:.8rem">
                            <div class="fw-600">{{ $op->name }}</div>
                            @if($op->lokasi_dinas)
                            <small class="text-muted">{{ $op->lokasi_dinas }}</small>
                            @endif
                        </label>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        <div class="col-12">
            <label class="form-label">Nama Supervisi / Pengelola / Koordinator yang Hadir</label>
            <input type="text" name="supervisi" class="form-control"
                   value="{{ old('supervisi') }}"
                   placeholder="Nama orang yang hadir sebagai supervisi (opsional)">
            <div class="form-text">Isi jika ada pengelola, koordinator, atau tamu yang hadir saat kegiatan.</div>
        </div>
    </div>

    <h6 class="section-title mb-3">📷 Foto Dokumentasi</h6>
    <div class="mb-4">
        <input type="file" name="fotos[]" id="inputFoto" class="form-control"
               multiple accept="image/jpeg,image/png,image/jpg,image/webp">
        <div class="form-text">Format: JPG, PNG, WEBP. Maks 8MB per foto. Bisa upload banyak sekaligus.</div>
        <div id="previewFoto" class="foto-grid mt-3"></div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        <button type="submit" class="btn btn-primary-custom">
            <i class="bi bi-save me-1"></i> Simpan Eviden
        </button>
        <a href="{{ route('eviden.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
    </form>
    </div>
</div>
</div>
</div>
@endsection

@push('styles')
<style>
.foto-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:.75rem;}
.foto-grid .fi{aspect-ratio:1;border-radius:8px;overflow:hidden;border:2px solid #dfe6e9;position:relative;}
.foto-grid .fi img{width:100%;height:100%;object-fit:cover;}
.foto-grid .fi .fi-rm{position:absolute;top:3px;right:3px;background:rgba(220,53,69,.85);
    color:#fff;border:none;border-radius:50%;width:20px;height:20px;font-size:.65rem;
    cursor:pointer;display:flex;align-items:center;justify-content:center;}
</style>
@endpush

@push('scripts')
<script>
document.getElementById('inputFoto').addEventListener('change',function(){
    const c=document.getElementById('previewFoto');
    c.innerHTML='';
    Array.from(this.files).forEach((f,i)=>{
        const r=new FileReader();
        r.onload=e=>{
            const d=document.createElement('div');
            d.className='fi';
            d.innerHTML=`<img src="${e.target.result}">`;
            c.appendChild(d);
        };
        r.readAsDataURL(f);
    });
});
</script>
@endpush
