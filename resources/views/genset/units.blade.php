@extends('layouts.app')
@section('title','Kelola Unit Genset')
@section('page-title','Kelola Unit Genset')

@section('content')

@if(session('success'))
<div class="alert alert-success py-2 mb-3">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger py-2 mb-3"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li style="font-size:.82rem">{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="row g-3">
{{-- Form Tambah Unit --}}
<div class="col-12 col-lg-5">
<div class="card">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Tambah Unit Genset</h6></div>
    <div class="card-body">
    <form action="{{ route('genset-unit.store') }}" method="POST">
    @csrf
    <div class="row g-2">
        <div class="col-12">
            <label class="form-label">Nama Unit <span class="text-danger">*</span></label>
            <input type="text" name="nama_unit" class="form-control form-control-sm"
                   value="{{ old('nama_unit') }}" placeholder="Contoh: Genset Utama 1" required>
        </div>
        <div class="col-12">
            <label class="form-label">Lokasi</label>
            <select name="lokasi" class="form-select form-select-sm">
                <option value="">— Pilih Lokasi —</option>
                @foreach($lokasis as $lok)
                <option value="{{ $lok->nama }}" {{ old('lokasi')===$lok->nama?'selected':'' }}>
                    {{ $lok->nama }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-6">
            <label class="form-label">Merk</label>
            <input type="text" name="merk" class="form-control form-control-sm" value="{{ old('merk') }}" placeholder="Cummins, Perkins...">
        </div>
        <div class="col-6">
            <label class="form-label">Tipe</label>
            <input type="text" name="tipe" class="form-control form-control-sm" value="{{ old('tipe') }}" placeholder="Model/tipe">
        </div>
        <div class="col-6">
            <label class="form-label">Kapasitas (kVA)</label>
            <input type="number" name="kapasitas_kva" class="form-control form-control-sm mono" value="{{ old('kapasitas_kva') }}" min="0">
        </div>
        <div class="col-6">
            <label class="form-label">Kapasitas Tangki (L)</label>
            <input type="number" step="0.1" name="kapasitas_tangki_liter" class="form-control form-control-sm mono" value="{{ old('kapasitas_tangki_liter') }}" min="0">
        </div>
        <div class="col-6">
            <label class="form-label">Tahun Pembuatan</label>
            <input type="number" name="tahun_pembuatan" class="form-control form-control-sm mono" value="{{ old('tahun_pembuatan') }}" min="1990" max="{{ date('Y') }}">
        </div>
        <div class="col-6 d-flex align-items-end">
            <div class="form-check form-switch">
                <input type="checkbox" name="is_active" value="1" class="form-check-input"
                       {{ old('is_active',1) ? 'checked' : '' }}>
                <label class="form-check-label" style="font-size:.82rem">Aktif</label>
            </div>
        </div>
    </div>
    <button type="submit" class="btn btn-primary-custom btn-sm mt-3 w-100">
        <i class="bi bi-plus-circle me-1"></i>Tambah Unit
    </button>
    </form>
    </div>
</div>
</div>

{{-- Daftar Unit --}}
<div class="col-12 col-lg-7">
<div class="card">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-list-ul me-2"></i>Daftar Unit Genset</h6></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.82rem">
                <thead>
                    <tr>
                        <th class="ps-3">Nama Unit</th>
                        <th>Lokasi</th>
                        <th>Merk/Tipe</th>
                        <th>Kapasitas</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($units as $u)
                    <tr>
                        <td class="ps-3 fw-semibold">{{ $u->nama_unit }}</td>
                        <td>{{ $u->lokasi ?? '-' }}</td>
                        <td>{{ $u->merk ?? '-' }}{{ $u->tipe ? ' '.$u->tipe : '' }}</td>
                        <td class="mono">{{ $u->kapasitas_kva ? $u->kapasitas_kva.' kVA' : '-' }}</td>
                        <td>
                            <span class="badge {{ $u->is_active ? 'bg-success' : 'bg-secondary' }}" style="font-size:.65rem">
                                {{ $u->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="text-end pe-3">
                            <button class="btn btn-sm btn-outline-warning"
                                    onclick="editUnit({{ $u->id }},'{{ addslashes($u->nama_unit) }}','{{ $u->lokasi }}','{{ $u->merk }}','{{ $u->tipe }}',{{ $u->kapasitas_kva??'null' }},{{ $u->kapasitas_tangki_liter??'null' }},{{ $u->tahun_pembuatan??'null' }},{{ $u->is_active?1:0 }})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            @if(!$u->logs()->exists())
                            <form action="{{ route('genset-unit.destroy',$u) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Hapus unit {{ addslashes($u->nama_unit) }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">
                        <i class="bi bi-gear-wide-connected d-block fs-2 mb-1"></i>Belum ada unit genset
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
</div>

{{-- Modal Edit Unit --}}
<div class="modal fade" id="modalEditUnit" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
    <div class="modal-header">
        <h6 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Unit Genset</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <form id="formEditUnit" method="POST">
    @csrf @method('PUT')
    <div class="modal-body">
        <div class="row g-2">
            <div class="col-12">
                <label class="form-label">Nama Unit <span class="text-danger">*</span></label>
                <input type="text" name="nama_unit" id="edit_nama_unit" class="form-control form-control-sm" required>
            </div>
            <div class="col-12">
                <label class="form-label">Lokasi</label>
                <select name="lokasi" id="edit_lokasi" class="form-select form-select-sm">
                    <option value="">— Pilih Lokasi —</option>
                    @foreach($lokasis as $lok)
                    <option value="{{ $lok->nama }}">{{ $lok->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6">
                <label class="form-label">Merk</label>
                <input type="text" name="merk" id="edit_merk" class="form-control form-control-sm">
            </div>
            <div class="col-6">
                <label class="form-label">Tipe</label>
                <input type="text" name="tipe" id="edit_tipe" class="form-control form-control-sm">
            </div>
            <div class="col-6">
                <label class="form-label">Kapasitas (kVA)</label>
                <input type="number" name="kapasitas_kva" id="edit_kva" class="form-control form-control-sm mono" min="0">
            </div>
            <div class="col-6">
                <label class="form-label">Kapasitas Tangki (L)</label>
                <input type="number" step="0.1" name="kapasitas_tangki_liter" id="edit_tangki" class="form-control form-control-sm mono" min="0">
            </div>
            <div class="col-6">
                <label class="form-label">Tahun Pembuatan</label>
                <input type="number" name="tahun_pembuatan" id="edit_tahun" class="form-control form-control-sm mono" min="1990" max="{{ date('Y') }}">
            </div>
            <div class="col-6 d-flex align-items-end">
                <div class="form-check form-switch">
                    <input type="checkbox" name="is_active" id="edit_aktif" value="1" class="form-check-input">
                    <label class="form-check-label" style="font-size:.82rem">Aktif</label>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-warning btn-sm fw-bold"><i class="bi bi-save me-1"></i>Simpan</button>
    </div>
    </form>
</div>
</div>
</div>

@push('scripts')
<script>
function editUnit(id, nama, lokasi, merk, tipe, kva, tangki, tahun, aktif) {
    document.getElementById('edit_nama_unit').value = nama;
    document.getElementById('edit_merk').value      = merk || '';
    document.getElementById('edit_tipe').value      = tipe || '';
    document.getElementById('edit_kva').value       = kva  || '';
    document.getElementById('edit_tangki').value    = tangki || '';
    document.getElementById('edit_tahun').value     = tahun || '';
    document.getElementById('edit_aktif').checked   = aktif == 1;

    // Set lokasi dropdown
    const sel = document.getElementById('edit_lokasi');
    for (let i = 0; i < sel.options.length; i++) {
        sel.options[i].selected = sel.options[i].value === lokasi;
    }

    document.getElementById('formEditUnit').action = '/genset-unit/' + id;
    const modal = new bootstrap.Modal(document.getElementById('modalEditUnit'));
    modal.show();
}
</script>
@endpush
@endsection