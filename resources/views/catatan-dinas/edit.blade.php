@extends('layouts.app')
@section('title','Edit Catatan Dinas')
@section('page-title','Edit Catatan Dinas')

@section('content')
<div class="row justify-content-center"><div class="col-12 col-lg-9">
<form method="POST" action="{{ route('catatan-dinas.update', $c) }}" enctype="multipart/form-data">
@csrf @method('PUT')
@include('catatan-dinas._form', ['c' => $c])
<div class="d-flex gap-2 mb-4">
    <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-save me-1"></i>Simpan Perubahan</button>
    <a href="{{ route('catatan-dinas.show', $c) }}" class="btn btn-outline-secondary">Batal</a>
</div>
</form>
</div></div>
@endsection
