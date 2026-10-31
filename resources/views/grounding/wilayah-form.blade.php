@extends('layouts.app')
@section('title', $w->exists ? 'Edit Wilayah Grounding' : 'Tambah Wilayah Grounding')
@section('page-title', $w->exists ? 'Edit Wilayah Grounding' : 'Tambah Wilayah Grounding')
@section('content')
<div class="row justify-content-center"><div class="col-12 col-lg-7">
@if($errors->any())<div class="alert alert-danger py-2"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li style="font-size:.82rem">{{ $e }}</li>@endforeach</ul></div>@endif
<div class="card"><div class="card-body">
<form method="POST" enctype="multipart/form-data" action="{{ $w->exists ? route('grounding.wilayah.update', $w) : route('grounding.wilayah.store') }}">
    @csrf @if($w->exists) @method('PUT') @endif
    <div class="mb-3"><label class="form-label">Nama Wilayah <span class="text-danger">*</span></label>
        <input type="text" name="nama" class="form-control" maxlength="255" required value="{{ old('nama', $w->nama) }}" placeholder="mis. Site Way Kanan"></div>
    <div class="mb-3"><label class="form-label">Lokasi / Alamat</label>
        <input type="text" name="lokasi" class="form-control" maxlength="255" value="{{ old('lokasi', $w->lokasi) }}"></div>
    <div class="mb-3"><label class="form-label">Keterangan</label>
        <textarea name="keterangan" rows="2" class="form-control" maxlength="2000">{{ old('keterangan', $w->keterangan) }}</textarea></div>
    <div class="mb-3">
        <label class="form-label">Peta / Denah Wilayah</label>
        @if($w->peta_url)
        <div class="mb-2"><img src="{{ $w->peta_url }}" alt="" style="max-width:100%;max-height:220px;border:1px solid #ddd;border-radius:6px"></div>
        <label class="form-check mb-2"><input type="checkbox" name="hapus_peta" value="1" class="form-check-input"><span class="form-check-label">Hapus peta ini</span></label>
        @endif
        <input type="file" name="peta" class="form-control" accept="image/jpeg,image/png,image/jpg,image/webp">
        <div class="form-text">Denah situs atau foto udara/tangkapan peta (JPG/PNG/WEBP, maks 10 MB). Titik ditandai dengan klik pada gambar ini.</div>
    </div>
    @if($w->exists)
    <div class="form-check form-switch mb-3"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" id="wa" {{ old('is_active', $w->is_active) ? 'checked' : '' }}><label class="form-check-label" for="wa">Wilayah aktif</label></div>
    @endif
    <div class="d-flex gap-2"><button class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Simpan</button>
        <a href="{{ $w->exists ? route('grounding.wilayah.show', $w) : route('grounding.index') }}" class="btn btn-outline-secondary">Batal</a></div>
</form>
</div></div>
</div></div>
@endsection
