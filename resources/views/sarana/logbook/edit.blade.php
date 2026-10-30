@extends('layouts.app')
@section('title','Edit Logbook Sarana')
@section('page-title','Edit Logbook Sarana & Prasarana')
@section('content')
<div class="row justify-content-center"><div class="col-12 col-lg-9">
<form method="POST" action="{{ route('sarana.logbook.update', $log) }}" enctype="multipart/form-data">
@csrf @method('PUT')
@include('sarana.logbook._form', ['log' => $log])
<div class="d-flex gap-2 mb-4">
    <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-save me-1"></i>Simpan Perubahan</button>
    <a href="{{ route('sarana.logbook.show', $log) }}" class="btn btn-outline-secondary">Batal</a>
</div>
</form>
</div></div>
@endsection
