@extends('layouts.app')
@section('title','Tambah User')
@section('page-title','Tambah User')

@section('content')
@php
    $auth = auth()->user();
    $lokasiList = \App\Models\Lokasi::orderBy('divisi')->orderBy('nama')->get();
@endphp
<div class="row justify-content-center">
<div class="col-12 col-md-7">
<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-person-plus text-primary"></i>Tambah User Baru
        <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary ms-auto">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
    <div class="card-body">
    <form action="{{ route('users.store') }}" method="POST">
    @csrf

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6">
            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">NIP</label>
            <input type="text" name="nip" class="form-control mono" value="{{ old('nip') }}" placeholder="Opsional">
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
        </div>

        {{-- Lokasi Dinas — dropdown dari tabel lokasis --}}
        <div class="col-12 col-md-6">
            <label class="form-label">Lokasi Dinas</label>
            <select name="lokasi_dinas" id="selectLokasiDinas" class="form-select" onchange="toggleLokasiCustom(this)">
                <option value="">— Pilih Lokasi —</option>
                
                @foreach(\App\Models\Lokasi::orderBy('nama')->get() as $lok)
                <option value="{{ $lok->nama }}" {{ old('lokasi_dinas')===$lok->nama?'selected':'' }}>{{ $lok->nama }}</option>
                @endforeach
                <option value="__custom__">Lainnya (isi manual)</option>
            </select>
            <input type="text" id="inputLokasiCustom" class="form-control mt-2" style="display:none" placeholder="Ketik lokasi...">
        </div>
    </div>

    <h6 class="section-title mb-3">🏢 Divisi & Peran</h6>
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6">
            <label class="form-label">Divisi <span class="text-danger">*</span></label>
            <select name="divisi" class="form-select" required {{ $auth->isAdminDivisi() ? 'disabled':'' }}>
                @foreach(\App\Models\User::DIVISI_LABEL as $val => $label)
                <option value="{{ $val }}"
                    {{ old('divisi', $auth->isAdminDivisi() ? $auth->divisi : '')==$val ? 'selected':'' }}>
                    {{ $label }}
                </option>
                @endforeach
            </select>
            @if($auth->isAdminDivisi())
            <input type="hidden" name="divisi" value="{{ $auth->divisi }}">
            <div class="form-text">Anda hanya dapat menambah user untuk divisi {{ $auth->divisi_label }}.</div>
            @endif
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Peran <span class="text-danger">*</span></label>
            <select name="role" class="form-select" required>
                @if($auth->isAdmin())
                <option value="{{ \App\Models\User::ROLE_ADMIN }}" {{ old('role')==\App\Models\User::ROLE_ADMIN?'selected':'' }}>{{ \App\Models\User::ROLE_LABEL[\App\Models\User::ROLE_ADMIN] }}</option>
                @endif
                <option value="{{ \App\Models\User::ROLE_ADMIN_DIVISI }}" {{ old('role')==\App\Models\User::ROLE_ADMIN_DIVISI?'selected':'' }}>{{ \App\Models\User::ROLE_LABEL[\App\Models\User::ROLE_ADMIN_DIVISI] }}</option>
                <option value="{{ \App\Models\User::ROLE_OPERATOR }}" {{ old('role',\App\Models\User::ROLE_OPERATOR)==\App\Models\User::ROLE_OPERATOR?'selected':'' }}>{{ \App\Models\User::ROLE_LABEL[\App\Models\User::ROLE_OPERATOR] }}</option>
            </select>
            <div class="form-text"><strong>Admin Divisi</strong>: bisa kelola jadwal shift, laporan, user — terbatas divisinya sendiri.</div>
        </div>
    </div>

    <h6 class="section-title mb-3">🔐 Akun</h6>
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <label class="form-label">Password <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" required minlength="8">
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
            <input type="password" name="password_confirmation" class="form-control" required minlength="8">
        </div>
        <div class="col-12">
            <div class="form-check form-switch">
                <input type="checkbox" name="is_active" class="form-check-input" value="1" checked>
                <label class="form-check-label">Status Aktif</label>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Simpan</button>
        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
    </form>
    </div>
</div>
</div>
</div>
@endsection

@push('scripts')
<script>
function toggleLokasiCustom(sel){
    const c = document.getElementById('inputLokasiCustom');
    c.style.display = sel.value==='__custom__'?'block':'none';
    if(sel.value==='__custom__') c.focus();
}
document.querySelector('form').addEventListener('submit', function(){
    const sel = document.getElementById('selectLokasiDinas');
    const c   = document.getElementById('inputLokasiCustom');
    if(sel && sel.value==='__custom__' && c.value){
        const o=document.createElement('option'); o.value=c.value; o.selected=true;
        sel.appendChild(o); sel.value=c.value;
    }
});
</script>
@endpush
