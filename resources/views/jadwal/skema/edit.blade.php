@extends('layouts.app')
@section('title','Edit Skema Shift')
@section('page-title','Edit Skema Shift')

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-8">

@if($errors->any())
<div class="alert alert-danger py-2 mb-3"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li style="font-size:.82rem">{{ $e }}</li>@endforeach</ul></div>
@endif

@if($skemaShift->is_default)
<div class="alert alert-warning py-2 mb-3" style="font-size:.82rem">
    <i class="bi bi-info-circle me-1"></i>
    Ini adalah skema bawaan sistem. Anda tetap bisa mengubah jam dan label shift, tapi kode tidak bisa diubah.
</div>
@endif

<form method="POST" action="{{ route('skema-shift.update', $skemaShift) }}">
@csrf @method('PUT')

<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Informasi Skema</h6></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nama Skema <span class="text-danger">*</span></label>
                <input type="text" name="nama" class="form-control"
                       value="{{ old('nama', $skemaShift->nama) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Divisi <span class="text-danger">*</span></label>
                @if($skemaShift->is_default)
                <input type="text" class="form-control" value="{{ \App\Models\User::DIVISI_LABEL[$skemaShift->divisi] ?? $skemaShift->divisi }}" disabled>
                <input type="hidden" name="divisi" value="{{ $skemaShift->divisi }}">
                @else
                <select name="divisi" class="form-select" required>
                    @foreach($divisiOptions as $val => $label)
                    <option value="{{ $val }}" {{ old('divisi',$skemaShift->divisi)==$val?'selected':'' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @endif
            </div>
            <div class="col-md-3">
                <label class="form-label">Kode Unik <span class="text-danger">*</span></label>
                @if($skemaShift->is_default)
                <input type="text" class="form-control mono" value="{{ $skemaShift->kode }}" disabled>
                <input type="hidden" name="kode" value="{{ $skemaShift->kode }}">
                @else
                <input type="text" name="kode" class="form-control mono"
                       value="{{ old('kode', $skemaShift->kode) }}"
                       pattern="[a-zA-Z0-9_-]+" required>
                @endif
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input"
                           {{ old('is_active', $skemaShift->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label">Aktif</label>
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
        <table class="table mb-0">
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
                @forelse($skemaShift->items as $item)
                <tr class="shift-row">
                    <td class="ps-3 text-muted no-col" style="font-size:.8rem">{{ $item->nomor }}</td>
                    <td><input type="text" name="shift_label[]" class="form-control form-control-sm"
                               value="{{ old('shift_label.'.$loop->index, $item->label) }}" required></td>
                    <td><input type="time" name="shift_mulai[]" class="form-control form-control-sm"
                               value="{{ old('shift_mulai.'.$loop->index, $item->jam_mulai_short) }}" required></td>
                    <td><input type="time" name="shift_selesai[]" class="form-control form-control-sm"
                               value="{{ old('shift_selesai.'.$loop->index, $item->jam_selesai_short) }}" required></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusShift(this)"><i class="bi bi-x"></i></button></td>
                </tr>
                @empty
                <tr class="shift-row">
                    <td class="ps-3 text-muted no-col" style="font-size:.8rem">1</td>
                    <td><input type="text" name="shift_label[]" class="form-control form-control-sm" required placeholder="Nama shift"></td>
                    <td><input type="time" name="shift_mulai[]" class="form-control form-control-sm" required></td>
                    <td><input type="time" name="shift_selesai[]" class="form-control form-control-sm" required></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusShift(this)"><i class="bi bi-x"></i></button></td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Simpan Perubahan</button>
    <a href="{{ route('skema-shift.index') }}" class="btn btn-outline-secondary">Batal</a>
    @if(!$skemaShift->is_default)
    <form action="{{ route('skema-shift.destroy', $skemaShift) }}" method="POST" class="ms-auto"
          onsubmit="return confirm('Hapus skema ini?')">
        @csrf @method('DELETE')
        <button class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>Hapus</button>
    </form>
    @endif
</div>
</form>
</div>
</div>

@push('scripts')
<script>
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
