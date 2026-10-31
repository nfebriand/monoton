@extends('layouts.app')
@section('title','Ronde Pengukuran Grounding')
@section('page-title','Ronde Pengukuran Grounding')
@section('content')
@include('partials.offline-notice')
<div class="row justify-content-center"><div class="col-12 col-lg-9">
@if($errors->any())<div class="alert alert-danger py-2"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li style="font-size:.82rem">{{ $e }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('grounding.ronde.store', $w) }}" data-offline-queue="grounding_ronde" data-offline-label="Ronde Grounding">
@csrf
<div class="card mb-3"><div class="card-header"><h6 class="mb-0"><i class="bi bi-clipboard2-pulse me-2"></i>{{ $w->nama }}</h6></div>
<div class="card-body"><div class="row g-3">
    <div class="col-6 col-md-3"><label class="form-label">Tanggal <span class="text-danger">*</span></label><input type="date" name="tanggal" class="form-control" required max="{{ now()->toDateString() }}" value="{{ old('tanggal', now()->toDateString()) }}"></div>
    <div class="col-6 col-md-3"><label class="form-label">Jam</label><input type="time" name="jam" class="form-control" value="{{ old('jam') }}"></div>
    <div class="col-12 col-md-6"><label class="form-label">Kondisi tanah</label>
        <select name="kondisi_tanah" class="form-select"><option value="">-</option>@foreach(\App\Models\GroundingUkur::KONDISI as $k => $l)<option value="{{ $k }}" {{ old('kondisi_tanah') === $k ? 'selected' : '' }}>{{ $l }}</option>@endforeach</select></div>
    <div class="col-12 col-md-6"><label class="form-label">Alat ukur</label><input type="text" name="alat" class="form-control" maxlength="80" value="{{ old('alat') }}" placeholder="mis. Kyoritsu 4105A"></div>
    <div class="col-12 col-md-6"><label class="form-label">Metode</label>
        <select name="metode" class="form-select"><option value="">-</option>@foreach(\App\Models\GroundingUkur::METODE as $m)<option {{ old('metode') === $m ? 'selected' : '' }}>{{ $m }}</option>@endforeach</select></div>
</div></div></div>

<div class="card mb-3"><div class="card-header"><h6 class="mb-0">Nilai tahanan per titik (&Omega;) <small class="text-muted">- kosongkan titik yang tidak diukur</small></h6></div>
<div class="card-body p-0">
@forelse($w->titiks as $t)
@php $u = $t->ukurTerakhir; @endphp
<div class="p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
    <div class="d-flex flex-wrap align-items-center gap-2">
        <div class="flex-grow-1"><div class="fw-semibold" style="font-size:.86rem">{{ $t->kode }} &mdash; {{ $t->nama }}</div>
            <div class="text-muted" style="font-size:.72rem">Batas {{ rtrim(rtrim(number_format($t->batas_ohm, 2, ',', '.'), '0'), ',') }} &Omega;@if($u) &bull; terakhir {{ rtrim(rtrim(number_format($u->nilai_ohm, 2, ',', '.'), '0'), ',') }} &Omega; ({{ $u->tanggal->format('d/m/Y') }})@endif</div></div>
        <div style="width:130px"><input type="text" inputmode="decimal" name="nilai[{{ $t->id }}]" class="form-control text-end mono" placeholder="&Omega;" value="{{ old('nilai.'.$t->id) }}"></div>
    </div>
    <input type="text" name="ket[{{ $t->id }}]" class="form-control form-control-sm mt-2" maxlength="1000" placeholder="Keterangan (opsional)" value="{{ old('ket.'.$t->id) }}">
</div>
@empty
<div class="p-4 text-center text-muted">Wilayah ini belum memiliki titik aktif.</div>
@endforelse
</div></div>
@php
    $kandidat = $kandidat ?? collect(); $evidenDipilih = $evidenDipilih ?? null;
    $buatUrl = route('eviden.create', ['grounding_wilayah' => $w->id]);
@endphp
@include('grounding._eviden-pilih')
<div class="d-flex gap-2 mb-4"><button class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Simpan Ronde</button><a href="{{ route('grounding.wilayah.show', $w) }}" class="btn btn-outline-secondary">Batal</a></div>
</form>
</div></div>
@endsection
