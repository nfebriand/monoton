@extends('layouts.app')
@section('title','Tambah User')
@section('page-title','Tambah User Baru')

@section('content')
<div class="row justify-content-center"><div class="col-md-7">
<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-person-plus text-primary"></i> Form Tambah User Baru
        <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary ms-auto">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
    <div class="card-body">
    <form action="{{ route('users.store') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label">NIP</label>
            <input type="text" name="nip" class="form-control mono" value="{{ old('nip') }}" placeholder="19850101001">
        </div>
        <div class="col-md-6">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
        </div>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label">Password <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" required placeholder="Min. 6 karakter">
        </div>
        <div class="col-md-6">
            <label class="form-label">Konfirmasi Password</label>
            <input type="password" name="password_confirmation" class="form-control">
        </div>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label">Role <span class="text-danger">*</span></label>
            <select name="role" class="form-select" required>
                <option value="operator" {{ old('role','operator')==='operator'?'selected':'' }}>Operator</option>
                <option value="admin"    {{ old('role')==='admin'?'selected':'' }}>Administrator</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Lokasi Dinas</label>
            <select name="lokasi_dinas" class="form-select">
                <option value="">– Pilih Lokasi –</option>
                @foreach(\App\Models\User::LOKASI_DINAS as $lok)
                <option value="{{ $lok }}" {{ old('lokasi_dinas')===$lok?'selected':'' }}>{{ $lok }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Status</label>
            <select name="is_active" class="form-select">
                <option value="1" selected>Aktif</option>
                <option value="0">Nonaktif</option>
            </select>
        </div>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan</button>
        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
    </form>
    </div>
</div>
</div></div>
@endsection
