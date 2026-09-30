@extends('layouts.app')
@section('title','Edit User')
@section('page-title','Edit User')

@section('content')
@php
    $auth = auth()->user();
    $lokasiList = \App\Models\Lokasi::orderBy('divisi')->orderBy('nama')->get();
    $lokasiVal  = old('lokasi_dinas', $user->lokasi_dinas ?? '');
    $isCustom   = $lokasiVal && !$lokasiList->pluck('nama')->contains($lokasiVal);
@endphp
<div class="row justify-content-center">
<div class="col-12 col-md-7">
<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-person-gear text-warning"></i>Edit User: <strong>{{ $user->name }}</strong>
        <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary ms-auto">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
    <div class="card-body">
    <form action="{{ route('users.update',$user) }}" method="POST" enctype="multipart/form-data">
    @csrf @method('PUT')

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6">
            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" value="{{ old('name',$user->name) }}" required>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">NIP</label>
            <input type="text" name="nip" class="form-control mono" value="{{ old('nip',$user->nip) }}" placeholder="Opsional">
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" value="{{ old('email',$user->email) }}" required>
        </div>

        {{-- Lokasi Dinas — dropdown dari tabel lokasis --}}
        <div class="col-12 col-md-6">
            <label class="form-label">Lokasi Dinas</label>
            <select name="lokasi_dinas" id="selectLokasiDinas" class="form-select" onchange="toggleLokasiCustom(this)">
                <option value="">— Pilih Lokasi —</option>
                @foreach(\App\Models\Lokasi::orderBy('nama')->get() as $lok)
                <option value="{{ $lok->nama }}" {{ (!$isCustom && $lokasiVal===$lok->nama)?'selected':'' }}>{{ $lok->nama }}</option>
                @endforeach
                <option value="__custom__" {{ $isCustom?'selected':'' }}>Lainnya (isi manual)</option>
            </select>
            <input type="text" id="inputLokasiCustom" class="form-control mt-2"
                   style="display:{{ $isCustom?'block':'none' }}"
                   value="{{ $isCustom?$lokasiVal:'' }}" placeholder="Ketik lokasi...">
        </div>
    </div>

    <h6 class="section-title mb-3">🏢 Divisi & Peran</h6>
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6">
            <label class="form-label">Divisi <span class="text-danger">*</span></label>
            @if($auth->isAdminDivisi())
            <input type="text" class="form-control" value="{{ $user->divisi_label }}" disabled>
            <input type="hidden" name="divisi" value="{{ $user->divisi }}">
            <div class="form-text">Anda tidak dapat mengubah divisi user.</div>
            @else
            <select name="divisi" class="form-select" required>
                @foreach(\App\Models\User::DIVISI_LABEL as $val => $label)
                <option value="{{ $val }}" {{ old('divisi',$user->divisi)==$val?'selected':'' }}>{{ $label }}</option>
                @endforeach
            </select>
            @endif
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Peran <span class="text-danger">*</span></label>
            @if($auth->isAdminDivisi() && $user->isAdmin())
            <input type="text" class="form-control" value="{{ $user->role_label }}" disabled>
            <input type="hidden" name="role" value="{{ $user->role }}">
            @else
            <select name="role" class="form-select" required>
                @if($auth->isAdmin())
                <option value="{{ \App\Models\User::ROLE_ADMIN }}" {{ old('role',$user->role)==\App\Models\User::ROLE_ADMIN?'selected':'' }}>{{ \App\Models\User::ROLE_LABEL[\App\Models\User::ROLE_ADMIN] }}</option>
                @endif
                <option value="{{ \App\Models\User::ROLE_ADMIN_DIVISI }}" {{ old('role',$user->role)==\App\Models\User::ROLE_ADMIN_DIVISI?'selected':'' }}>{{ \App\Models\User::ROLE_LABEL[\App\Models\User::ROLE_ADMIN_DIVISI] }}</option>
                <option value="{{ \App\Models\User::ROLE_OPERATOR }}" {{ old('role',$user->role)==\App\Models\User::ROLE_OPERATOR?'selected':'' }}>{{ \App\Models\User::ROLE_LABEL[\App\Models\User::ROLE_OPERATOR] }}</option>
            </select>
            @endif
        </div>
    </div>

    <h6 class="section-title mb-3">✍️ Tanda Tangan Digital</h6>
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            @if($user->ttd_path)
            <div class="mb-2">
                <div style="font-size:.8rem;color:var(--muted);margin-bottom:4px">Tanda tangan saat ini:</div>
                <div style="border:1px solid #ddd;border-radius:6px;padding:8px;background:#f8f9fa;display:inline-block">
                    <img src="{{ $user->ttd_url }}" alt="TTD {{ $user->name }}"
                         style="max-height:80px;max-width:200px;object-fit:contain">
                </div>
                <div class="mt-2">
                    <div class="form-check">
                        <input type="checkbox" name="hapus_ttd" value="1" class="form-check-input" id="hapusTtdCheck">
                        <label class="form-check-label text-danger" for="hapusTtdCheck" style="font-size:.82rem">
                            <i class="bi bi-trash me-1"></i>Hapus tanda tangan ini
                        </label>
                    </div>
                </div>
            </div>
            @else
            <div class="mb-2" style="font-size:.8rem;color:var(--muted)">Belum ada tanda tangan.</div>
            @endif
            <label class="form-label">{{ $user->ttd_path ? 'Ganti' : 'Upload' }} Tanda Tangan</label>
            <input type="file" name="ttd" class="form-control" accept="image/png,image/jpeg,image/jpg"
                   id="inputTtdUser">
            <div class="form-text">Format PNG/JPG, max 1MB. Gunakan latar belakang putih atau transparan (PNG).</div>
            <div id="ttd-preview-wrap" class="mt-2" style="display:none">
                <div style="font-size:.78rem;color:var(--muted);margin-bottom:3px">Preview:</div>
                <div style="border:1px solid #ddd;border-radius:6px;padding:8px;background:#f8f9fa;display:inline-block">
                    <img id="ttd-preview-img" src="" alt="Preview TTD"
                         style="max-height:80px;max-width:200px;object-fit:contain">
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6" style="font-size:.8rem;color:var(--muted)">
            <div class="alert alert-info py-2" style="font-size:.78rem">
                <i class="bi bi-info-circle me-1"></i>
                <strong>Tips tanda tangan:</strong><br>
                • Gunakan file PNG dengan latar transparan untuk hasil terbaik di PDF<br>
                • Tanda tangan akan muncul di kolom "Dibuat Oleh" pada setiap PDF yang dibuat user ini<br>
                • Ukuran ideal: lebar 200–300px, tinggi 80–100px
            </div>
        </div>
    </div>

    <h6 class="section-title mb-3">🔐 Akun</h6>
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <label class="form-label">Password Baru</label>
            <input type="password" name="password" class="form-control" minlength="8" placeholder="Kosongkan jika tidak diubah">
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Konfirmasi Password Baru</label>
            <input type="password" name="password_confirmation" class="form-control" minlength="8">
        </div>
        <div class="col-12">
            <div class="form-check form-switch">
                <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ old('is_active',$user->is_active)?'checked':'' }}>
                <label class="form-check-label">Status Aktif</label>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-save me-1"></i>Simpan Perubahan</button>
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
document.getElementById('inputTtdUser')?.addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('ttd-preview-img').src = e.target.result;
        document.getElementById('ttd-preview-wrap').style.display = 'block';
    };
    reader.readAsDataURL(file);
});

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
