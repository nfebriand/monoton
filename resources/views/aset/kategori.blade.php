@extends('layouts.app')
@section('title','Kategori Aset')
@section('page-title','Kategori Aset')

@section('content')
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('aset.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
    <span class="fw-bold">Kelola Kategori Aset</span>
    <button class="btn btn-sm btn-primary-custom ms-auto" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-circle me-1"></i>Tambah Kategori
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th class="ps-3">Nama Kategori</th><th>Interval Default</th><th>Jumlah Aset</th><th class="text-end pe-3">Aksi</th></tr>
            </thead>
            <tbody>
                @forelse($kategoris as $k)
                <tr>
                    <td class="ps-3 fw-bold">{{ $k->nama }}</td>
                    <td class="mono">{{ $k->interval_maintenance_hari ? $k->interval_maintenance_hari.' hari' : '–' }}</td>
                    <td class="mono">{{ $k->asets_count }}</td>
                    <td class="text-end pe-3">
                        <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalEdit{{ $k->id }}"><i class="bi bi-pencil"></i></button>
                        <form action="{{ route('aset.kategori.destroy',$k) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus kategori ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <div class="modal fade" id="modalEdit{{ $k->id }}" tabindex="-1">
                <div class="modal-dialog"><div class="modal-content">
                <form action="{{ route('aset.kategori.update',$k) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-header"><h6 class="modal-title">Edit Kategori</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label">Nama</label><input type="text" name="nama" class="form-control" value="{{ $k->nama }}" required></div>
                        <div><label class="form-label">Interval Maintenance Default (hari)</label><input type="number" name="interval_maintenance_hari" class="form-control mono" value="{{ $k->interval_maintenance_hari }}"></div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn btn-warning fw-bold">Simpan</button></div>
                </form>
                </div></div>
                </div>
                @empty
                <tr><td colspan="4" class="text-center text-muted py-4">Belum ada kategori</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
<form action="{{ route('aset.kategori.store') }}" method="POST">
    @csrf
    <div class="modal-header"><h6 class="modal-title">Tambah Kategori</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3"><label class="form-label">Nama <span class="text-danger">*</span></label><input type="text" name="nama" class="form-control" required placeholder="Elektronik, Mebel, dll"></div>
        <div><label class="form-label">Interval Maintenance Default (hari)</label><input type="number" name="interval_maintenance_hari" class="form-control mono" placeholder="cth. 90"></div>
    </div>
    <div class="modal-footer"><button type="submit" class="btn btn-primary-custom">Simpan</button></div>
</form>
</div></div>
</div>
@endsection
