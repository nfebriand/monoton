@extends('layouts.app')
@section('title','Catatan Dinas Baru')
@section('page-title','Tulis Catatan Dinas')

@section('content')
@include('partials.offline-notice')
<div class="row justify-content-center"><div class="col-12 col-lg-9">
<form method="POST" action="{{ route('catatan-dinas.store') }}" enctype="multipart/form-data"
      data-offline-queue="catatan_dinas" data-offline-label="Catatan Dinas">
@csrf
@include('catatan-dinas._form', ['c' => null])
<div class="d-flex gap-2 mb-4">
    <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Simpan Catatan</button>
    <a href="{{ route('catatan-dinas.index') }}" class="btn btn-outline-secondary">Batal</a>
</div>
</form>
</div></div>
@endsection
