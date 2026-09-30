@extends('layouts.app')
@section('title','Buat Skema Shift')
@section('page-title','Buat Skema Shift Baru')

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-8">

@if($errors->any())
<div class="alert alert-danger py-2 mb-3"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li style="font-size:.82rem">{{ $e }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ route('skema-shift.store') }}">
@csrf

<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Informasi Skema</h6></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nama Skema <span class="text-danger">*</span></label>
                <input type="text" name="nama" class="form-control" value="{{ old('nama') }}"
                       placeholder="Contoh: Studio Pagi, Sarana Weekend" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Divisi <span class="text-danger">*</span></label>
                <select name="divisi" class="form-select" required>
                    @foreach($divisiOptions as $val => $label)
                    <option value="{{ $val }}" {{ old('divisi')==$val?'selected':'' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Kode Unik <span class="text-danger">*</span></label>
                <input type="text" name="kode" id="inp-kode" class="form-control mono"
                       value="{{ old('kode') }}" placeholder="cth: studio_pagi"
                       pattern="[a-zA-Z0-9_-]+" required>
                <div class="form-text">Huruf, angka, underscore, strip. Tidak bisa sama.</div>
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input"
                           {{ old('is_active',1) ? 'checked' : '' }}>
                    <label class="form-check-label">Aktif (langsung bisa digunakan)</label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-clock me-2"></i>Definisi Shift</h6>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="tambahShift()">
            <i class="bi bi-plus me-1"></i>Tambah Shift
        </button>
    </div>
    <div class="card-body p-0">
        <table class="table mb-0" id="tbl-shift">
            <thead style="background:#f8f9fa">
                <tr>
                    <th class="ps-3" style="width:40px">No</th>
                    <th style="width:35%">Nama Shift</th>
                    <th style="width:22%">Jam Mulai</th>
                    <th style="width:22%">Jam Selesai</th>
                    <th style="width:50px"></th>
                </tr>
            </thead>
            <tbody id="shift-rows">
                @if(old('shift_label'))
                    @foreach(old('shift_label') as $i => $lbl)
                    <tr class="shift-row">
                        <td class="ps-3 text-muted no-col" style="font-size:.8rem">{{ $i+1 }}</td>
                        <td><input type="text" name="shift_label[]" class="form-control form-control-sm" value="{{ $lbl }}" required placeholder="Pagi / Shift 1 / MCR"></td>
                        <td><input type="time" name="shift_mulai[]" class="form-control form-control-sm" value="{{ old('shift_mulai')[$i] }}" required></td>
                        <td><input type="time" name="shift_selesai[]" class="form-control form-control-sm" value="{{ old('shift_selesai')[$i] }}" required></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusShift(this)"><i class="bi bi-x"></i></button></td>
                    </tr>
                    @endforeach
                @else
                {{-- Default: 3 baris kosong --}}
                <tr class="shift-row">
                    <td class="ps-3 text-muted no-col" style="font-size:.8rem">1</td>
                    <td><input type="text" name="shift_label[]" class="form-control form-control-sm" required placeholder="Pagi / Shift 1"></td>
                    <td><input type="time" name="shift_mulai[]" class="form-control form-control-sm" required></td>
                    <td><input type="time" name="shift_selesai[]" class="form-control form-control-sm" required></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusShift(this)"><i class="bi bi-x"></i></button></td>
                </tr>
                <tr class="shift-row">
                    <td class="ps-3 text-muted no-col" style="font-size:.8rem">2</td>
                    <td><input type="text" name="shift_label[]" class="form-control form-control-sm" placeholder="Siang / Shift 2"></td>
                    <td><input type="time" name="shift_mulai[]" class="form-control form-control-sm"></td>
                    <td><input type="time" name="shift_selesai[]" class="form-control form-control-sm"></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusShift(this)"><i class="bi bi-x"></i></button></td>
                </tr>
                <tr class="shift-row">
                    <td class="ps-3 text-muted no-col" style="font-size:.8rem">3</td>
                    <td><input type="text" name="shift_label[]" class="form-control form-control-sm" placeholder="Malam / Shift 3"></td>
                    <td><input type="time" name="shift_mulai[]" class="form-control form-control-sm"></td>
                    <td><input type="time" name="shift_selesai[]" class="form-control form-control-sm"></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusShift(this)"><i class="bi bi-x"></i></button></td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Simpan Skema</button>
    <a href="{{ route('skema-shift.index') }}" class="btn btn-outline-secondary">Batal</a>
</div>
</form>
</div>
</div>

@push('scripts')
<script>
// Auto-generate kode dari nama
document.querySelector('[name=nama]').addEventListener('input', function() {
    const kodeInp = document.getElementById('inp-kode');
    if (kodeInp.dataset.manual) return;
    kodeInp.value = this.value.toLowerCase()
        .replace(/[^a-z0-9\s]/g, '')
        .replace(/\s+/g, '_')
        .substring(0, 50);
});
document.getElementById('inp-kode').addEventListener('input', function() {
    this.dataset.manual = '1';
});

function tambahShift() {
    const tbody = document.getElementById('shift-rows');
    const no = tbody.querySelectorAll('.shift-row').length + 1;
    const tr = document.createElement('tr');
    tr.className = 'shift-row';
    tr.innerHTML = `
        <td class="ps-3 text-muted no-col" style="font-size:.8rem">${no}</td>
        <td><input type="text" name="shift_label[]" class="form-control form-control-sm" required placeholder="Nama shift"></td>
        <td><input type="time" name="shift_mulai[]" class="form-control form-control-sm" required></td>
        <td><input type="time" name="shift_selesai[]" class="form-control form-control-sm" required></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusShift(this)"><i class="bi bi-x"></i></button></td>
    `;
    tbody.appendChild(tr);
    updateNomor();
    tr.querySelector('input').focus();
}

function hapusShift(btn) {
    const rows = document.querySelectorAll('.shift-row');
    if (rows.length <= 1) { alert('Minimal harus ada 1 shift.'); return; }
    btn.closest('.shift-row').remove();
    updateNomor();
}

function updateNomor() {
    document.querySelectorAll('.shift-row').forEach((row, i) => {
        const no = row.querySelector('.no-col');
        if (no) no.textContent = i + 1;
    });
}
</script>
@endpush
@endsection
