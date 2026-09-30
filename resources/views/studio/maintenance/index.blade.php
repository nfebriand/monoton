@extends('layouts.app')
@section('title','Maintenance Perangkat Studio')
@section('page-title','Maintenance Perangkat Studio')

@section('content')

@if($perluPerhatian->count())
<div class="alert alert-warning py-2 mb-3">
    <strong><i class="bi bi-exclamation-triangle me-1"></i>Perlu Perhatian:</strong>
    <ul class="mb-0 mt-1 ps-3">
        @foreach($perluPerhatian as $p)
        <li style="font-size:.82rem">
            <a href="{{ route('studio.maintenance.create',['studio_perangkat_id'=>$p->id]) }}">{{ $p->nama }}</a>
            — <span style="color:{{ $p->status_maintenance_color }}">{{ $p->status_maintenance_label }}</span>
            @if($p->jatuh_tempo_maintenance)({{ $p->jatuh_tempo_maintenance->format('d/m/Y') }})@endif
        </li>
        @endforeach
    </ul>
</div>
@endif

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label mb-1" style="font-size:.7rem">Perangkat</label>
                <select name="studio_perangkat_id" class="form-select form-select-sm">
                    <option value="">Semua Perangkat</option>
                    @foreach($perangkats as $p)
                    <option value="{{ $p->id }}" {{ request('studio_perangkat_id')==$p->id?'selected':'' }}>{{ $p->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1" style="font-size:.7rem">Jenis</label>
                <select name="jenis" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach(\App\Models\StudioMaintenance::JENIS_LABEL as $val=>$label)
                    <option value="{{ $val }}" {{ request('jenis')==$val?'selected':'' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1" style="font-size:.7rem">Dari Tanggal</label>
                <input type="date" name="tanggal_dari" class="form-control form-control-sm" value="{{ request('tanggal_dari') }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1" style="font-size:.7rem">Sampai Tanggal</label>
                <input type="date" name="tanggal_sampai" class="form-control form-control-sm" value="{{ request('tanggal_sampai') }}">
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button class="btn btn-sm btn-primary-custom flex-fill"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('studio.maintenance.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-2 gap-2 flex-wrap">
    <a href="{{ route('studio.maintenance.cetak', ['tanggal_dari'=>now()->startOfMonth()->toDateString(),'tanggal_sampai'=>now()->toDateString()]) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
        <i class="bi bi-file-earmark-pdf me-1"></i>Cetak Laporan
    </a>
    <a href="{{ route('studio.maintenance.create') }}" class="btn btn-primary-custom">
        <i class="bi bi-plus-circle me-1"></i>Catat Maintenance
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Tanggal</th>
                        <th>Perangkat</th>
                        <th>Jenis</th>
                        <th>Uraian</th>
                        <th>Hasil</th>
                        <th>Teknisi</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="ps-3 mono" style="font-size:.8rem">{{ $log->tanggal->format('d/m/Y') }}</td>
                        <td style="font-size:.82rem">{{ $log->perangkat->nama }}</td>
                        <td><span class="badge bg-info" style="font-size:.68rem">{{ $log->jenis_label }}</span></td>
                        <td style="font-size:.8rem;max-width:200px">{{ Str::limit($log->uraian_pekerjaan,50) }}</td>
                        <td><span class="badge {{ $log->hasil=='selesai'?'bg-success':($log->hasil=='sebagian'?'bg-warning':'bg-secondary') }}" style="font-size:.68rem">{{ $log->hasil_label }}</span></td>
                        <td style="font-size:.8rem">{{ $log->user->name }}</td>
                        <td class="text-end pe-3">
                            <a href="{{ route('studio.maintenance.show',$log) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                            @if(auth()->user()->isAdmin() || (auth()->user()->isAdminDivisi() && auth()->user()->isDivisi('studio')))
                            <form method="POST" action="{{ route('studio.maintenance.destroy',$log) }}" class="d-inline" onsubmit="return confirm('Hapus log ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-tools d-block fs-2 mb-1"></i>Belum ada log maintenance studio
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div class="px-3 py-2 border-top" style="border-color:var(--border)!important">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
@endsection
