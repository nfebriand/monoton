@extends('layouts.app')
@section('title','Detail Perangkat Studio')
@section('page-title','Detail Perangkat Studio')

@section('content')
<div class="row">
<div class="col-12 col-lg-5 mb-3">
<div class="card h-100">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-speaker me-2"></i>{{ $studioPerangkat->nama }}</h6>
        <span class="badge" style="background:{{ \App\Models\StudioPerangkat::KONDISI_COLOR[$studioPerangkat->kondisi] ?? '#888' }};font-size:.68rem">{{ $studioPerangkat->kondisi_label }}</span>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-6" style="font-size:.8rem">Kode Inventaris</dt>
            <dd class="col-6 mono" style="font-size:.8rem">{{ $studioPerangkat->kode_inventaris ?? '-' }}</dd>

            <dt class="col-6" style="font-size:.8rem">Kategori</dt>
            <dd class="col-6" style="font-size:.8rem">{{ \App\Models\StudioPerangkat::KATEGORI_LABEL[$studioPerangkat->kategori] ?? ($studioPerangkat->kategori ?? '-') }}</dd>

            <dt class="col-6" style="font-size:.8rem">Merk</dt>
            <dd class="col-6" style="font-size:.8rem">{{ $studioPerangkat->merk ?? '-' }}</dd>

            <dt class="col-6" style="font-size:.8rem">Tipe</dt>
            <dd class="col-6" style="font-size:.8rem">{{ $studioPerangkat->tipe ?? '-' }}</dd>

            <dt class="col-6" style="font-size:.8rem">No. Seri</dt>
            <dd class="col-6 mono" style="font-size:.8rem">{{ $studioPerangkat->no_seri ?? '-' }}</dd>

            <dt class="col-6" style="font-size:.8rem">Tahun Pengadaan</dt>
            <dd class="col-6 mono" style="font-size:.8rem">{{ $studioPerangkat->tahun_pengadaan ?? '-' }}</dd>

            <dt class="col-6" style="font-size:.8rem">Status</dt>
            <dd class="col-6" style="font-size:.8rem">{{ \App\Models\StudioPerangkat::STATUS_LABEL[$studioPerangkat->status] ?? $studioPerangkat->status }}</dd>

            <dt class="col-6" style="font-size:.8rem">Interval Maintenance</dt>
            <dd class="col-6 mono" style="font-size:.8rem">{{ $studioPerangkat->interval_maintenance_hari ?? '-' }} hari</dd>

            <dt class="col-6" style="font-size:.8rem">Maintenance Terakhir</dt>
            <dd class="col-6 mono" style="font-size:.8rem">{{ $studioPerangkat->maintenance_terakhir ? $studioPerangkat->maintenance_terakhir->format('d/m/Y') : '-' }}</dd>

            <dt class="col-6" style="font-size:.8rem">Status Maintenance</dt>
            <dd class="col-6" style="font-size:.8rem;color:{{ $studioPerangkat->status_maintenance_color }}">{{ $studioPerangkat->status_maintenance_label }}</dd>

            @if($studioPerangkat->keterangan)
            <dt class="col-6" style="font-size:.8rem">Keterangan</dt>
            <dd class="col-6" style="font-size:.8rem">{{ $studioPerangkat->keterangan }}</dd>
            @endif
        </dl>
    </div>
    <div class="card-footer d-flex gap-2 flex-wrap">
        @if(auth()->user()->isAdmin() || (auth()->user()->isAdminDivisi() && auth()->user()->isDivisi('studio')))
        <a href="{{ route('studio.perangkat.edit',$studioPerangkat) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil me-1"></i>Edit</a>
        @endif
        <a href="{{ route('studio.maintenance.create',['studio_perangkat_id'=>$studioPerangkat->id]) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-tools me-1"></i>Catat Maintenance</a>
        <a href="{{ route('studio.perangkat.index') }}" class="btn btn-sm btn-outline-secondary ms-auto"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
    </div>
</div>
</div>

<div class="col-12 col-lg-7">
<div class="card">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-tools me-2"></i>Riwayat Maintenance</h6></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Tanggal</th>
                        <th>Jenis</th>
                        <th>Hasil</th>
                        <th>Teknisi</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($studioPerangkat->maintenances as $m)
                    <tr>
                        <td class="ps-3 mono" style="font-size:.8rem">{{ $m->tanggal->format('d/m/Y') }}</td>
                        <td><span class="badge bg-info" style="font-size:.68rem">{{ $m->jenis_label }}</span></td>
                        <td><span class="badge {{ $m->hasil=='selesai'?'bg-success':($m->hasil=='sebagian'?'bg-warning':'bg-secondary') }}" style="font-size:.68rem">{{ $m->hasil_label }}</span></td>
                        <td style="font-size:.8rem">{{ $m->user->name }}</td>
                        <td class="text-end pe-3">
                            <a href="{{ route('studio.maintenance.show',$m) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-3" style="font-size:.82rem">Belum ada riwayat maintenance</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
</div>
@endsection
