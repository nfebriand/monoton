@extends('layouts.app')
@section('title','Catat Pengukuran Grounding')
@section('page-title','Catat Pengukuran Grounding')
@section('content')
@include('partials.offline-notice')
@php $u = $t->ukurTerakhir; $ukur = $ukur ?? null; @endphp
<div class="row justify-content-center"><div class="col-12 col-lg-8">
@if($errors->any())<div class="alert alert-danger py-2"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li style="font-size:.82rem">{{ $e }}</li>@endforeach</ul></div>@endif
<form method="POST" enctype="multipart/form-data" action="{{ $ukur ? route('grounding.ukur.update', $ukur) : route('grounding.ukur.store', $t) }}" @if(!$ukur) data-offline-queue="grounding_ukur" data-offline-label="Pengukuran Grounding" @endif>
@csrf @if($ukur) @method('PUT') @endif
<div class="card mb-3"><div class="card-header"><h6 class="mb-0">{{ $t->kode }} &mdash; {{ $t->nama }} <small class="text-muted">({{ $t->wilayah->nama }})</small></h6></div>
<div class="card-body"><div class="row g-3">
    <div class="col-12"><div class="text-muted" style="font-size:.78rem">Batas maksimum <strong>{{ rtrim(rtrim(number_format($t->batas_ohm, 2, ',', '.'), '0'), ',') }} &Omega;</strong>@if($u) &bull; terakhir <strong>{{ rtrim(rtrim(number_format($u->nilai_ohm, 2, ',', '.'), '0'), ',') }} &Omega;</strong> ({{ $u->tanggal->format('d/m/Y') }})@endif</div></div>
    <div class="col-12 col-md-4"><label class="form-label">Nilai tahanan (&Omega;) <span class="text-danger">*</span></label><input type="text" inputmode="decimal" name="nilai_ohm" class="form-control mono" required value="{{ $ukur ? rtrim(rtrim(number_format((float) $ukur->nilai_ohm, 2, '.', ''), '0'), '.') : old('nilai_ohm') }}" autofocus></div>
    <div class="col-6 col-md-4"><label class="form-label">Tanggal <span class="text-danger">*</span></label><input type="date" name="tanggal" class="form-control" required max="{{ now()->toDateString() }}" value="{{ old('tanggal', $ukur ? $ukur->tanggal->toDateString() : now()->toDateString()) }}"></div>
    <div class="col-6 col-md-4"><label class="form-label">Jam</label><input type="time" name="jam" class="form-control" value="{{ old('jam', $ukur && $ukur->jam ? substr($ukur->jam, 0, 5) : '') }}"></div>
    <div class="col-12 col-md-4"><label class="form-label">Kondisi tanah</label>
        <select name="kondisi_tanah" class="form-select"><option value="">-</option>@foreach(\App\Models\GroundingUkur::KONDISI as $k => $l)<option value="{{ $k }}" {{ old('kondisi_tanah', $ukur->kondisi_tanah ?? '') === $k ? 'selected' : '' }}>{{ $l }}</option>@endforeach</select></div>
    <div class="col-12 col-md-4"><label class="form-label">Alat ukur</label><input type="text" name="alat" class="form-control" maxlength="80" value="{{ old('alat', $ukur->alat ?? '') }}"></div>
    <div class="col-12 col-md-4"><label class="form-label">Metode</label>
        <select name="metode" class="form-select"><option value="">-</option>@foreach(\App\Models\GroundingUkur::METODE as $m)<option {{ old('metode', $ukur->metode ?? '') === $m ? 'selected' : '' }}>{{ $m }}</option>@endforeach</select></div>
    <div class="col-12"><label class="form-label">Keterangan</label><textarea name="keterangan" rows="3" class="form-control" maxlength="2000">{{ old('keterangan', $ukur->keterangan ?? '') }}</textarea></div>
</div></div></div>
<div class="card mb-3"><div class="card-header"><h6 class="mb-0"><i class="bi bi-camera me-2"></i>Foto Bukti <small class="text-muted">(layar alat, kondisi titik)</small></h6></div>
<div class="card-body">@include('components.foto-multi-ket', ['capName' => $ukur ? 'foto_keterangan_baru' : 'foto_keterangan', 'existing' => $ukur ? $ukur->fotos : null])</div></div>
@php
    $kandidat = $kandidat ?? collect(); $evidenDipilih = $evidenDipilih ?? null;
    if (!$ukur) $buatUrl = route('eviden.create', ['grounding_titik' => $t->id]);
@endphp
@include('grounding._eviden-pilih')
<div class="d-flex gap-2 mb-4"><button class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>{{ $ukur ? 'Simpan Perubahan' : 'Simpan Pengukuran' }}</button><a href="{{ route('grounding.titik.show', $t) }}" class="btn btn-outline-secondary">Batal</a></div>
</form>
</div></div>
@endsection
