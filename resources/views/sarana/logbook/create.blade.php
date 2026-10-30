@extends('layouts.app')
@section('title','Isi Logbook Sarana')
@section('page-title','Isi Logbook Harian Sarana & Prasarana')
@section('content')
@include('partials.offline-notice')
<div class="row justify-content-center"><div class="col-12 col-lg-9">

@if($sebelumnya->isNotEmpty())
<div class="card mb-3" style="border-left:4px solid #f39c12">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-arrow-left-right me-2"></i>Catatan dari petugas sebelumnya <small class="text-muted">(serah terima)</small></h6></div>
    <div class="card-body py-2">
        @foreach($sebelumnya as $s)
        <div class="{{ !$loop->last ? 'pb-2 mb-2 border-bottom' : '' }}">
            <div class="text-muted" style="font-size:.72rem">
                {{ $s->tanggal->translatedFormat('l, d F Y') }} &bull; {{ $s->shift_label }} &bull; {{ $s->user->name ?? '-' }}
                <a href="{{ route('sarana.logbook.show', $s) }}" class="ms-1">buka</a>
            </div>
            <div style="white-space:pre-wrap;word-break:break-word;font-size:.85rem">{{ \Illuminate\Support\Str::limit($s->catatan_petugas, 600) }}</div>
        </div>
        @endforeach
    </div>
</div>
@endif

<form method="POST" action="{{ route('sarana.logbook.store') }}" enctype="multipart/form-data"
      data-offline-queue="sarana_logbook" data-offline-label="Logbook Sarana">
@csrf
@include('sarana.logbook._form', ['log' => null])
<div class="d-flex gap-2 mb-4">
    <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Simpan Logbook</button>
    <a href="{{ route('sarana.logbook.index') }}" class="btn btn-outline-secondary">Batal</a>
</div>
</form>
</div></div>
@endsection
