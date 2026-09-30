@extends('layouts.app')
@section('title','Maintenance Aset')
@section('page-title','Maintenance Aset')

@section('content')

@if($perluPerhatian->isNotEmpty())
<div class="card mb-3" style="border-color:var(--danger)">
    <div class="card-header" style="background:rgba(238,90,36,.08)">
        <i class="bi bi-exclamation-triangle-fill me-2 text-danger"></i>Perlu Perhatian ({{ $perluPerhatian->count() }})
    </div>
    <div class="card-body p-0">
        @foreach($perluPerhatian as $a)
        <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom flex-wrap" style="border-color:var(--border)!important">
            <div style="flex:1;min-width:0">
                <a href="{{ route('aset.show',$a) }}" class="text-decoration-none fw-bold" style="font-size:.84rem;color:var(--text)">{{ $a->nama }}</a>
                <div class="text-muted" style="font-size:.7rem">{{ $a->kode_aset }} {{ $a->lokasi?'· '.$a->lokasi:'' }}</div>
            </div>
            <span class="badge" style="background:{{ $a->status_maintenance_color }}">{{ $a->status_maintenance_label }}</span>
            @if($a->jatuh_tempo_maintenance)
            <span class="text-muted mono" style="font-size:.72rem">{{ $a->jatuh_tempo_maintenance->format('d/m/Y') }}</span>
            @endif
            <a href="{{ route('maintenance.create') }}?aset_id={{ $a->id }}" class="btn btn-sm btn-success">Catat</a>
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label mb-1" style="font-size:.7rem">Aset</label>
                <select name="aset_id" class="form-select form-select-sm">
                    <option value="">Semua Aset</option>
                    @foreach($asets as $a)
                    <option value="{{ $a->id }}" {{ request('aset_id')==$a->id?'selected':'' }}>{{ $a->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label mb-1" style="font-size:.7rem">Jenis</label>
                <select name="jenis" class="form-select form-select-sm">
                    <option value="">Semua Jenis</option>
                    @foreach(\App\Models\MaintenanceLog::JENIS_LABEL as $val=>$label)
                    <option value="{{ $val }}" {{ request('jenis')==$val?'selected':'' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1" style="font-size:.7rem">Dari</label>
                <input type="date" name="tanggal_dari" class="form-control form-control-sm" value="{{ request('tanggal_dari') }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1" style="font-size:.7rem">Sampai</label>
                <input type="date" name="tanggal_sampai" class="form-control form-control-sm" value="{{ request('tanggal_sampai') }}">
            </div>
            <div class="col-12 col-md-2 d-flex gap-1">
                <button class="btn btn-sm btn-primary-custom flex-fill"><i class="bi bi-funnel"></i></button>
                <a href="{{ route('maintenance.index') }}" class="btn btn-sm btn-outline-secondary">✕</a>
            </div>
        </form>
    </div>
</div>

<div class="d-flex justify-content-end mb-2">
    <a href="{{ route('maintenance.create') }}" class="btn btn-primary-custom">
        <i class="bi bi-plus-circle me-1"></i>Catat Maintenance
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Tanggal</th><th>Aset</th><th>Jenis</th>
                        <th>Uraian</th><th>Hasil</th><th>Petugas</th><th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr style="cursor:pointer" onclick="window.location='{{ route('maintenance.show',$log) }}'">
                        <td class="ps-3 mono" style="font-size:.78rem">{{ $log->tanggal->format('d/m/Y') }}</td>
                        <td style="font-size:.82rem">
                            {{ $log->aset->nama }}
                            <div class="text-muted" style="font-size:.65rem">{{ $log->aset->kode_aset }}</div>
                        </td>
                        <td><span class="badge" style="background:{{ ['preventif'=>'#10ac84','korektif'=>'#ee5a24','inspeksi'=>'#0a3d62'][$log->jenis] }};font-size:.68rem">{{ $log->jenis_label }}</span></td>
                        <td style="font-size:.78rem">{{ \Illuminate\Support\Str::limit($log->uraian_pekerjaan,40) }}</td>
                        <td><span class="badge {{ $log->hasil=='selesai'?'bg-success':($log->hasil=='sebagian'?'bg-warning text-dark':'bg-secondary') }}" style="font-size:.65rem">{{ $log->hasil_label }}</span></td>
                        <td style="font-size:.78rem">{{ $log->user->name }}</td>
                        <td class="text-end pe-3" onclick="event.stopPropagation()">
                            <a href="{{ route('maintenance.show',$log) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-tools d-block fs-2 mb-1"></i>Belum ada log maintenance</td></tr>
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
