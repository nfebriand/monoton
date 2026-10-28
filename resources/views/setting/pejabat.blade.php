@extends('layouts.app')
@section('title','Pergantian Pejabat')
@section('page-title','Pergantian Pejabat Penandatangan')

@section('content')
<div class="mb-3 d-flex align-items-center gap-2">
    <a href="{{ route('setting.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Pengaturan</a>
</div>

<div class="alert alert-info" style="font-size:.82rem">
    <strong>Cara kerja:</strong> saat ada pergantian, klik <em>Ganti Pejabat</em> dan isi tanggal mulai berlakunya.
    Pejabat lama otomatis diarsipkan beserta masa jabatannya. Laporan dicetak dengan pejabat yang menjabat pada
    <strong>tanggal akhir periode laporan</strong> (laporan periode berjalan memakai pejabat hari ini),
    sehingga laporan bulan-bulan sebelumnya tetap memakai pejabat lama.
    Untuk sekadar membetulkan salah ketik pejabat yang sedang menjabat, gunakan halaman Pengaturan biasa.
</div>

@if($errors->any())
<div class="alert alert-danger" style="font-size:.82rem">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

@foreach($pejabat as $kode => $p)
@php $now = $p['saat_ini']; @endphp
<div class="card mb-3">
    <div class="card-header d-flex flex-wrap align-items-center gap-2">
        <strong style="font-size:.9rem">{{ $p['label'] }}</strong>
        <button class="btn btn-sm btn-primary ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#ganti-{{ $kode }}">
            <i class="bi bi-arrow-repeat"></i> Ganti Pejabat
        </button>
    </div>
    <div class="card-body">
        <div class="row g-2" style="font-size:.85rem">
            <div class="col-md-4"><span class="text-muted">Saat ini:</span><br><strong>{{ $now['nama'] ?: '-' }}</strong></div>
            <div class="col-md-3"><span class="text-muted">NIP:</span><br>{{ $now['nip'] ?: '-' }}</div>
            <div class="col-md-3"><span class="text-muted">Jabatan:</span><br>{{ $now['jabatan'] }}</div>
            <div class="col-md-2"><span class="text-muted">Menjabat sejak:</span><br>{{ $p['mulai'] ? \Carbon\Carbon::parse($p['mulai'])->format('d/m/Y') : 'awal pencatatan' }}</div>
        </div>

        {{-- Form ganti pejabat --}}
        <div class="collapse mt-3" id="ganti-{{ $kode }}">
            <form method="POST" action="{{ route('setting.pejabat.ganti') }}" enctype="multipart/form-data" class="border rounded p-3 bg-light">
                @csrf
                <input type="hidden" name="kode" value="{{ $kode }}">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label">Berlaku sejak <span class="text-danger">*</span></label>
                        <input type="date" name="berlaku" class="form-control" required value="{{ old('kode')===$kode ? old('berlaku') : '' }}">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Nama pejabat baru <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control" required maxlength="100" value="{{ old('kode')===$kode ? old('nama') : '' }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">NIP</label>
                        <input type="text" name="nip" class="form-control" maxlength="30" value="{{ old('kode')===$kode ? old('nip') : '' }}">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Jabatan (kosongkan = tetap)</label>
                        <input type="text" name="jabatan" class="form-control" maxlength="100" placeholder="{{ $now['jabatan'] }}" value="{{ old('kode')===$kode ? old('jabatan') : '' }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanda tangan baru (PNG/JPG)</label>
                        <input type="file" name="ttd" class="form-control" accept="image/png,image/jpeg">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Catatan arsip</label>
                        <input type="text" name="keterangan" class="form-control" maxlength="200" placeholder="mis. mutasi / pensiun">
                    </div>
                </div>
                <div class="form-text mt-2">Pejabat yang sekarang diarsipkan dengan masa jabatan berakhir sehari sebelum tanggal berlaku. Tanda tangan lama tetap tersimpan untuk arsip.</div>
                <button class="btn btn-sm btn-success mt-2" onclick="return confirm('Ganti pejabat? Pejabat sekarang akan diarsipkan.')">
                    <i class="bi bi-check2-circle"></i> Simpan Pergantian
                </button>
            </form>
        </div>

        {{-- Arsip --}}
        @if($p['riwayat']->isNotEmpty())
        <div class="table-responsive mt-3">
            <table class="table table-sm align-middle mb-0" style="font-size:.8rem">
                <thead class="table-light">
                    <tr><th>Pejabat Terdahulu</th><th>NIP</th><th>Jabatan</th><th>Masa Jabatan</th><th>TTD</th><th></th></tr>
                </thead>
                <tbody>
                @foreach($p['riwayat'] as $r)
                    <tr>
                        <td>{{ $r->nama }}@if($r->keterangan)<div class="text-muted" style="font-size:.7rem">{{ $r->keterangan }}</div>@endif</td>
                        <td>{{ $r->nip ?: '-' }}</td>
                        <td>{{ $r->jabatan ?: '-' }}</td>
                        <td>{{ $r->berlaku_mulai ? $r->berlaku_mulai->format('d/m/Y') : 'awal' }} &ndash; {{ $r->berlaku_sampai->format('d/m/Y') }}</td>
                        <td>@if($r->ttd_path)<img src="{{ \App\Helpers\ImageHelper::url($r->ttd_path) }}" alt="TTD" style="height:28px">@else - @endif</td>
                        <td class="text-end text-nowrap">
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#edit-{{ $r->id }}"><i class="bi bi-pencil"></i></button>
                            <form method="POST" action="{{ route('setting.pejabat.destroy', $r) }}" class="d-inline" onsubmit="return confirm('Hapus arsip pejabat ini? Laporan periode tersebut akan memakai pejabat saat ini.')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <tr class="collapse" id="edit-{{ $r->id }}">
                        <td colspan="6" class="bg-light">
                            <form method="POST" action="{{ route('setting.pejabat.update', $r) }}" enctype="multipart/form-data" class="row g-2 p-2">
                                @csrf @method('PUT')
                                <div class="col-md-3"><label class="form-label">Nama</label><input type="text" name="nama" class="form-control form-control-sm" required value="{{ $r->nama }}"></div>
                                <div class="col-md-2"><label class="form-label">NIP</label><input type="text" name="nip" class="form-control form-control-sm" value="{{ $r->nip }}"></div>
                                <div class="col-md-3"><label class="form-label">Jabatan</label><input type="text" name="jabatan" class="form-control form-control-sm" value="{{ $r->jabatan }}"></div>
                                <div class="col-md-2"><label class="form-label">Mulai</label><input type="date" name="berlaku_mulai" class="form-control form-control-sm" value="{{ $r->berlaku_mulai ? $r->berlaku_mulai->toDateString() : '' }}"></div>
                                <div class="col-md-2"><label class="form-label">Sampai</label><input type="date" name="berlaku_sampai" class="form-control form-control-sm" required value="{{ $r->berlaku_sampai->toDateString() }}"></div>
                                <div class="col-md-4"><label class="form-label">Ganti TTD</label><input type="file" name="ttd" class="form-control form-control-sm" accept="image/png,image/jpeg"></div>
                                <div class="col-md-2 d-flex align-items-end"><button class="btn btn-sm btn-primary">Simpan</button></div>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-muted mt-3" style="font-size:.78rem">Belum ada arsip pejabat terdahulu.</div>
        @endif
    </div>
</div>
@endforeach
@endsection
