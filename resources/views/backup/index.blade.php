@extends('layouts.app')
@section('title','Backup Database')
@section('page-title','Backup & Restore Database')

@section('content')
@php
    $fmt = function ($b) { return $b >= 1048576 ? number_format($b/1048576, 1).' MB' : number_format(max($b,1)/1024, 1).' KB'; };
@endphp

@if($errors->any())
<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
@endif

<div class="row g-3">
<div class="col-12 col-xl-8">
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span><i class="bi bi-database me-1"></i>Daftar Backup — {{ $dbName }}</span>
            <span class="badge bg-secondary">{{ count($files) }} berkas · {{ $fmt($total) }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead>
                    <tr><th class="ps-3">Berkas</th><th>Dibuat</th><th>Ukuran</th><th class="text-end pe-3">Aksi</th></tr>
                </thead>
                <tbody>
                @forelse($files as $f)
                    <tr>
                        <td class="ps-3 mono" style="font-size:.78rem">{{ $f['name'] }}</td>
                        <td style="font-size:.8rem">{{ date('d/m/Y H:i', $f['mtime']) }}</td>
                        <td style="font-size:.8rem">{{ $fmt($f['size']) }}</td>
                        <td class="text-end pe-3 text-nowrap">
                            <a href="{{ route('backup.download', $f['name']) }}" class="btn btn-sm btn-outline-primary" title="Unduh"><i class="bi bi-download"></i></a>
                            <button type="button" class="btn btn-sm btn-outline-warning" title="Restore"
                                    data-bs-toggle="modal" data-bs-target="#modalRestore"
                                    data-file="{{ $f['name'] }}" data-action="{{ route('backup.restore', $f['name']) }}">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                            <form action="{{ route('backup.destroy', $f['name']) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Hapus berkas backup ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada backup.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="col-12 col-xl-4">
    <div class="card mb-3">
        <div class="card-header fw-bold"><i class="bi bi-plus-circle me-2 text-success"></i>Backup Sekarang</div>
        <div class="card-body">
            <p class="text-muted" style="font-size:.78rem">Membuat berkas <code>.sql.gz</code> berisi seluruh tabel dan data. Disimpan di server (folder privat), bisa diunduh.</p>
            <form action="{{ route('backup.create') }}" method="POST">
                @csrf
                <button class="btn btn-success w-100"><i class="bi bi-database-down me-1"></i>Buat Backup</button>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header fw-bold"><i class="bi bi-upload me-2 text-primary"></i>Unggah Backup</div>
        <div class="card-body">
            <form action="{{ route('backup.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="file" name="berkas" class="form-control form-control-sm mb-2" accept=".sql,.gz" required>
                <div class="text-muted mb-2" style="font-size:.7rem">Format .sql atau .sql.gz (buatan aplikasi ini atau mysqldump). Setelah diunggah, restore dari daftar.</div>
                <button class="btn btn-primary btn-sm w-100"><i class="bi bi-upload me-1"></i>Unggah</button>
            </form>
        </div>
    </div>

    <div class="alert alert-warning" style="font-size:.76rem">
        <b>Perhatian restore:</b> seluruh data saat ini diganti dengan isi berkas. Aplikasi otomatis membuat backup pengaman
        (<code>...-pre-restore.sql.gz</code>) sebelum memulihkan. Lakukan di luar jam operasional.
    </div>
</div>
</div>

{{-- Modal Restore --}}
<div class="modal fade" id="modalRestore" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" id="formRestore" class="modal-content">
      @csrf
      <div class="modal-header">
        <h6 class="modal-title"><i class="bi bi-exclamation-triangle text-warning me-1"></i>Restore Database</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2" style="font-size:.85rem">Pulihkan dari <b id="restoreNama" class="mono"></b>? Data saat ini akan ditimpa.</p>
        <div class="mb-2">
            <label class="form-label">Password Anda</label>
            <input type="password" name="password" class="form-control" required autocomplete="current-password">
        </div>
        <div class="mb-2">
            <label class="form-label">Ketik <b>RESTORE</b> untuk konfirmasi</label>
            <input type="text" name="konfirmasi" class="form-control" required autocomplete="off">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-sm btn-warning" id="btnRestore">Restore Sekarang</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('modalRestore').addEventListener('show.bs.modal', function (ev) {
    var b = ev.relatedTarget;
    document.getElementById('restoreNama').textContent = b.dataset.file;
    document.getElementById('formRestore').action = b.dataset.action;
});
document.getElementById('formRestore').addEventListener('submit', function () {
    var btn = document.getElementById('btnRestore');
    btn.disabled = true; btn.textContent = 'Memulihkan… jangan tutup halaman';
});
</script>
@endpush
