@extends('layouts.app')
@section('title','Pengukuran Grounding')
@section('page-title','Pengukuran Grounding')
@section('content')
@if($errors->any())<div class="alert alert-danger py-2">@foreach($errors->all() as $e)<div style="font-size:.82rem">{{ $e }}</div>@endforeach</div>@endif
<div class="d-flex align-items-center flex-wrap gap-2 mb-3">
    <div class="text-muted" style="font-size:.82rem">Pantau tahanan grounding per wilayah. Pin di peta berwarna sesuai hasil ukur terakhir.</div>
    @if($bisaKelola)<a href="{{ route('grounding.wilayah.create') }}" class="btn btn-primary-custom btn-sm ms-auto"><i class="bi bi-plus-circle me-1"></i>Tambah Wilayah</a>@endif
</div>
<div class="row g-3">
@forelse($wilayah as $w)
<div class="col-12 col-md-6 col-xl-4">
    <div class="card h-100" style="{{ $w->is_active ? '' : 'opacity:.6' }}">
        @if($w->peta_url)<div style="height:130px;overflow:hidden;background:#eef2f5"><img src="{{ $w->peta_url }}" alt="" style="width:100%;height:100%;object-fit:cover"></div>@endif
        <div class="card-body">
            <a href="{{ route('grounding.wilayah.show', $w) }}" class="fw-bold text-decoration-none">{{ $w->nama }}</a>
            @if(!$w->is_active)<span class="badge bg-secondary ms-1">Nonaktif</span>@endif
            <div class="text-muted" style="font-size:.74rem">{{ $w->lokasi ?: '-' }} &bull; {{ $w->titiks->count() }} titik</div>
            <div class="d-flex gap-2 flex-wrap mt-2" style="font-size:.72rem">
                <span class="badge" style="background:#27ae60">Baik {{ $w->ringkas['baik'] }}</span>
                <span class="badge" style="background:#f39c12">Waspada {{ $w->ringkas['waspada'] }}</span>
                <span class="badge" style="background:#e74c3c">Melebihi {{ $w->ringkas['melebihi'] }}</span>
                <span class="badge" style="background:#95a5a6">Belum diukur {{ $w->ringkas['belum'] }}</span>
            </div>
        </div>
        <div class="card-footer d-flex gap-2">
            <a href="{{ route('grounding.wilayah.show', $w) }}" class="btn btn-sm btn-outline-primary flex-fill"><i class="bi bi-map me-1"></i>Buka Peta</a>
            <a href="{{ route('grounding.ronde', $w) }}" class="btn btn-sm btn-outline-success flex-fill"><i class="bi bi-clipboard2-pulse me-1"></i>Ukur (Ronde)</a>
        </div>
    </div>
</div>
@empty
<div class="col-12"><div class="card"><div class="card-body text-center text-muted py-5">Belum ada wilayah. @if($bisaKelola)<div class="mt-2"><a href="{{ route('grounding.wilayah.create') }}" class="btn btn-primary-custom btn-sm">Tambah Wilayah Pertama</a></div>@endif</div></div></div>
@endforelse
</div>
@endsection
