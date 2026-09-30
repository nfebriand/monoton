@extends('layouts.app')
@section('title','Operasional Genset')
@section('page-title','Operasional Genset')

@section('content')

{{-- Ringkasan Bulan Ini --}}
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-2">
                <div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Operasi Bulan Ini</div>
                <div class="mono fw-bold" style="font-size:1.6rem;color:var(--primary)">{{ $ringkasan['total_operasi'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-2">
                <div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Total BBM Terpakai</div>
                <div class="mono fw-bold" style="font-size:1.6rem;color:var(--warning)">{{ number_format($ringkasan['total_bbm'],1) }} L</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-2">
                <div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Total Jam Operasi</div>
                <div class="mono fw-bold" style="font-size:1.6rem;color:var(--success)">{{ number_format($ringkasan['total_jam'],1) }} jam</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-2">
                <div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Gangguan</div>
                <div class="mono fw-bold" style="font-size:1.6rem;color:{{ $ringkasan['gangguan']>0?'var(--danger)':'var(--success)' }}">{{ $ringkasan['gangguan'] }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Filter --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label mb-1" style="font-size:.7rem">Unit Genset</label>
                <select name="genset_unit_id" class="form-select form-select-sm">
                    <option value="">Semua Unit</option>
                    @foreach($units as $u)
                    <option value="{{ $u->id }}" {{ request('genset_unit_id')==$u->id?'selected':'' }}>{{ $u->nama_unit }}</option>
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
            <div class="col-6 col-md-2">
                <label class="form-label mb-1" style="font-size:.7rem">Kondisi</label>
                <select name="kondisi" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="normal" {{ request('kondisi')=='normal'?'selected':'' }}>Normal</option>
                    <option value="gangguan" {{ request('kondisi')=='gangguan'?'selected':'' }}>Gangguan</option>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button class="btn btn-sm btn-primary-custom flex-fill"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('genset.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

{{-- Tombol Tambah --}}
<div class="d-flex justify-content-end mb-2">
    <a href="{{ route('genset.create') }}" class="btn btn-primary-custom">
        <i class="bi bi-plus-circle me-1"></i>Catat Operasional Genset
    </a>
</div>

{{-- Tabel Log --}}
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Tanggal</th>
                        <th>Unit Genset</th>
                        <th>Alasan</th>
                        <th>Jam Operasi</th>
                        <th>BBM Terpakai</th>
                        <th>Kondisi</th>
                        <th>Operator</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="ps-3">
                            <div class="mono fw-bold" style="font-size:.8rem">{{ $log->tanggal->format('d/m/Y') }}</div>
                            <div class="text-muted mono" style="font-size:.7rem">
                                {{ substr($log->jam_mulai,0,5) }}{{ $log->jam_selesai ? ' – '.substr($log->jam_selesai,0,5) : ' (berjalan)' }}
                            </div>
                        </td>
                        <td style="font-size:.82rem">
                            {{ $log->gensetUnit->nama_unit }}
                            @if($log->gensetUnit->lokasi)<div class="text-muted" style="font-size:.68rem">{{ $log->gensetUnit->lokasi }}</div>@endif
                        </td>
                        <td>
                            <span class="badge" style="background:#7b1fa2;font-size:.68rem">{{ $log->alasan_label }}</span>
                        </td>
                        <td class="mono" style="font-size:.8rem">
                            {{ $log->jam_operasi_hm ? number_format($log->jam_operasi_hm,1).' jam (HM)' : ($log->durasi ?? '–') }}
                        </td>
                        <td class="mono" style="font-size:.8rem">
                            {{ $log->pemakaian_bbm !== null ? number_format($log->pemakaian_bbm,1).' L' : '–' }}
                            @if($log->rasio_bbm_per_jam)
                            <div class="text-muted" style="font-size:.65rem">{{ number_format($log->rasio_bbm_per_jam,2) }} L/jam</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $log->kondisi=='normal'?'bg-success':'bg-danger' }}" style="font-size:.68rem">
                                {{ $log->kondisi=='normal'?'Normal':'Gangguan' }}
                            </span>
                        </td>
                        <td style="font-size:.8rem">{{ $log->user->name }}</td>
                        <td class="text-end pe-3">
                            <a href="{{ route('genset.show',$log) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">
                        <i class="bi bi-battery-charging d-block fs-2 mb-1"></i>Belum ada log operasional genset
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
