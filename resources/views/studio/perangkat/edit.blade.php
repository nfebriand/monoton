@extends('layouts.app')
@section('title','Edit Perangkat Studio')
@section('page-title','Edit Perangkat Studio')
@section('content')
<div class="row justify-content-center"><div class="col-12 col-lg-8">
<div class="card">
<div class="card-header"><h6 class="mb-0"><i class="bi bi-pencil me-2"></i>Edit: {{ $studioPerangkat->nama }}</h6></div>
<div class="card-body">
@if($errors->any())<div class="alert alert-danger py-2"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li style="font-size:.82rem">{{ $e }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('studio.perangkat.update',$studioPerangkat) }}" enctype="multipart/form-data">
@csrf @method('PUT')
<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label">Nama Perangkat <span class="text-danger">*</span></label>
        <input type="text" name="nama" class="form-control" value="{{ old('nama',$studioPerangkat->nama) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Kode Inventaris</label>
        <input type="text" name="kode_inventaris" class="form-control" value="{{ old('kode_inventaris',$studioPerangkat->kode_inventaris) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Lokasi Studio <span class="text-danger">*</span></label>
        <select name="lokasi" class="form-select" required>
            <option value="">-- Pilih Lokasi --</option>
            @foreach($lokasisStudio as $lok)
            <option value="{{ $lok }}" {{ old('lokasi',$studioPerangkat->lokasi)==$lok?'selected':'' }}>
                Studio {{ $lok }}
            </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Kategori</label>
        <select name="kategori" class="form-select">
            <option value="">-- Pilih --</option>
            @foreach(\App\Models\StudioPerangkat::KATEGORI_LABEL as $val=>$label)
            <option value="{{ $val }}" {{ old('kategori',$studioPerangkat->kategori)==$val?'selected':'' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Merk</label>
        <input type="text" name="merk" class="form-control" value="{{ old('merk',$studioPerangkat->merk) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Tipe / Model</label>
        <input type="text" name="tipe" class="form-control" value="{{ old('tipe',$studioPerangkat->tipe) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">No. Seri</label>
        <input type="text" name="no_seri" class="form-control" value="{{ old('no_seri',$studioPerangkat->no_seri) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Tahun Pengadaan</label>
        <input type="number" name="tahun_pengadaan" class="form-control" value="{{ old('tahun_pengadaan',$studioPerangkat->tahun_pengadaan) }}" min="1990" max="{{ date('Y') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Kondisi <span class="text-danger">*</span></label>
        <select name="kondisi" class="form-select" required>
            @foreach(\App\Models\StudioPerangkat::KONDISI_LABEL as $val=>$label)
            <option value="{{ $val }}" {{ old('kondisi',$studioPerangkat->kondisi)==$val?'selected':'' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Status <span class="text-danger">*</span></label>
        <select name="status" class="form-select" required>
            @foreach(\App\Models\StudioPerangkat::STATUS_LABEL as $val=>$label)
            <option value="{{ $val }}" {{ old('status',$studioPerangkat->status)==$val?'selected':'' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Interval Maintenance (hari)</label>
        <input type="number" name="interval_maintenance_hari" class="form-control" value="{{ old('interval_maintenance_hari',$studioPerangkat->interval_maintenance_hari) }}" min="1">
    </div>
    <div class="col-12">
        <label class="form-label">Keterangan</label>
        <textarea name="keterangan" class="form-control" rows="2">{{ old('keterangan',$studioPerangkat->keterangan) }}</textarea>
    </div>
    <div class="col-12">
        <label class="form-label"><i class="bi bi-camera me-1"></i>Foto Perangkat</label>
        @include('components.foto-upload',['existingFotos'=>$studioPerangkat->fotos])
    </div>
</div> {{-- penutup row g-3 --}}

<div class="d-flex gap-2 mt-4">

    <button type="submit" class="btn btn-primary-custom">
        <i class="bi bi-save me-1"></i>
        Simpan Perubahan
    </button>

    <a href="{{ route('studio.perangkat.show',$studioPerangkat) }}" 
       class="btn btn-outline-secondary">
        Batal
    </a>

</div>

</form> {{-- TUTUP FORM UPDATE --}}


@if(auth()->user()->isAdmin())

<form method="POST"
      action="{{ route('studio.perangkat.destroy',$studioPerangkat) }}"
      class="mt-2"
      onsubmit="return confirm('Hapus perangkat ini?')">

    @csrf
    @method('DELETE')

    <button type="submit" class="btn btn-outline-danger">
        <i class="bi bi-trash me-1"></i>
        Hapus
    </button>

</form>

@endif     

</div> {{-- card-body --}}
</div> {{-- card --}}
</div></div>
@endsection