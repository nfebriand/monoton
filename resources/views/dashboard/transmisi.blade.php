@extends('layouts.app')
@section('title','Dashboard')
@section('page-title','Dashboard Monitoring')

@section('content')
@php $user = auth()->user(); @endphp

{{-- Filter Lokasi (Admin) --}}
@if($user->isAdmin())
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
            <label class="form-label mb-0" style="font-size:.78rem">
                <i class="bi bi-funnel me-1"></i>Filter Lokasi:
            </label>
            <select name="lokasi" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="">Semua Lokasi</option>
                @foreach($lokasiList as $lok)
                <option value="{{ $lok }}" {{ $lokasiFilter===$lok?'selected':'' }}>{{ $lok }}</option>
                @endforeach
            </select>
            @if($lokasiFilter)
            <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-x-circle me-1"></i>Reset
            </a>
            @endif
        </form>
    </div>
</div>
@endif

{{-- Statistik Ringkas --}}
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-2">
                <div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Log Hari Ini</div>
                <div class="mono fw-bold" style="font-size:1.6rem;color:var(--primary)">{{ $totalLogHariIni }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-2">
                <div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Log Bulan Ini</div>
                <div class="mono fw-bold" style="font-size:1.6rem;color:var(--success)">{{ $totalLogBulanIni }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-2">
                <div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Eviden Bulan Ini</div>
                <div class="mono fw-bold" style="font-size:1.6rem;color:var(--accent)">{{ $totalEvidenBulanIni }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-2">
                <div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Total Pemancar</div>
                <div class="mono fw-bold" style="font-size:1.6rem;color:var(--warning)">{{ $pemancars->count() }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Peringatan VSWR Bermasalah --}}
@if($vswrBermasalah->isNotEmpty())
<div class="alert alert-danger mb-3">
    <div class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>VSWR Bermasalah Hari Ini</div>
    @foreach($vswrBermasalah as $v)
    <div style="font-size:.8rem" class="mb-1">
        <span class="mono fw-bold">{{ number_format($v->vswr_final,3) }}</span> —
        {{ $v->pemancar->nama_stasiun }}
        <span class="text-muted">({{ $v->dicatat_pada->format('H:i') }} oleh {{ $v->user->name }})</span>
    </div>
    @endforeach
</div>
@endif

{{-- ── STATUS PEMANCAR ── --}}
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-broadcast-pin me-2 text-primary"></i>Status Pemancar</span>
        <span class="badge bg-secondary">{{ $pemancars->count() }} pemancar</span>
    </div>
    <div class="card-body p-0">
        @forelse($pemancars as $p)
        @php
            $log = $p->logTerakhir;
            $vswr = $log?->vswr_final;
            $vswrCls = $vswr ? ($vswr<=1.5?'vswr-baik':($vswr<=2?'vswr-sedang':'vswr-buruk')) : '';
            $statusOnline = $log && $log->dicatat_pada->diffInHours(now()) < 6;
        @endphp
        <div class="d-flex align-items-center gap-3 px-3 py-3 border-bottom flex-wrap"
             style="border-color:var(--border)!important">

            {{-- Status dot --}}
            <div style="width:10px;height:10px;border-radius:50%;flex-shrink:0;
                 background:{{ $statusOnline?'#10ac84':'#aaa' }};
                 box-shadow:0 0 0 3px {{ $statusOnline?'rgba(16,172,132,.15)':'rgba(170,170,170,.15)' }}">
            </div>

            {{-- Info Pemancar --}}
            <div style="min-width:160px;flex:1">
                <a href="{{ route('pemancar.show',$p) }}" class="fw-bold text-decoration-none" style="font-size:.86rem;color:var(--text)">
                    {{ $p->nama_stasiun }}
                </a>
                <div class="d-flex gap-1 flex-wrap mt-1">
                    <span class="badge {{ $p->modulasi==='FM'?'bg-primary':'bg-warning text-dark' }}" style="font-size:.6rem">
                        {{ $p->modulasi }}{{ $p->frekuensi?' '.$p->frekuensi:'' }}
                    </span>
                    @if($p->lokasi)
                    <span class="badge bg-secondary" style="font-size:.6rem">{{ $p->lokasi }}</span>
                    @endif
                </div>
            </div>

            {{-- Log Terakhir --}}
            <div style="min-width:140px;flex:1.2">
                @if($log)
                <div class="text-muted" style="font-size:.65rem">PENCATATAN TERAKHIR</div>
                <div class="mono" style="font-size:.78rem;font-weight:600">
                    {{ $log->dicatat_pada->format('d/m/y H:i') }}
                </div>
                <div class="text-muted" style="font-size:.7rem">
                    oleh {{ $log->user->name }}
                    @if($log->jadwalShift) (Shift {{ $log->jadwalShift->shift }}) @endif
                </div>
                @else
                <div class="text-muted" style="font-size:.78rem">
                    <i class="bi bi-dash-circle me-1"></i>Belum ada pencatatan
                </div>
                @endif
            </div>

            {{-- Output & VSWR --}}
            <div style="min-width:120px;flex:1">
                @if($log && $log->output_final_pa)
                <div class="text-muted" style="font-size:.65rem">OUTPUT FINAL</div>
                <div class="mono fw-bold" style="font-size:.85rem">{{ number_format($log->output_final_pa,1) }} W</div>
                @if($p->kapasitas_output_final)
                <div class="progress" style="height:4px;margin-top:2px">
                    @php $pct = min(100, ($log->output_final_pa / $p->kapasitas_output_final) * 100); @endphp
                    <div class="progress-bar {{ $pct>95?'bg-danger':($pct>85?'bg-warning':'bg-success') }}"
                         style="width:{{ $pct }}%"></div>
                </div>
                @endif
                @else
                <span class="text-muted" style="font-size:.78rem">–</span>
                @endif
            </div>

            <div style="min-width:90px;text-align:right">
                @if($vswr)
                <div class="text-muted" style="font-size:.65rem">VSWR FINAL</div>
                <div class="mono fw-bold {{ $vswrCls }}" style="font-size:.95rem">{{ number_format($vswr,3) }}</div>
                @else
                <span class="text-muted" style="font-size:.78rem">–</span>
                @endif
            </div>

            <a href="{{ route('pemancar.show',$p) }}" class="btn btn-sm btn-outline-primary flex-shrink-0">
                <i class="bi bi-eye"></i>
            </a>
        </div>
        @empty
        <div class="text-center text-muted py-5">
            <i class="bi bi-broadcast-pin d-block fs-1 mb-2"></i>
            Belum ada data pemancar{{ $lokasiFilter?' di lokasi '.$lokasiFilter:'' }}
        </div>
        @endforelse
    </div>
</div>

{{-- Jadwal Hari Ini --}}
@if($jadwalHariIni->isNotEmpty())
<div class="card mt-3">
    <div class="card-header"><i class="bi bi-calendar3 me-2 text-success"></i>Jadwal Shift Hari Ini</div>
    <div class="card-body p-0">
        @foreach($jadwalHariIni as $j)
        @php
            $now = now()->format('H:i');
            $aktif = $now >= $j->jam_mulai && $now < $j->jam_selesai;
        @endphp
        <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom" style="border-color:var(--border)!important">
            <span class="badge" style="background:{{ [1=>'#0a3d62',2=>'#10ac84',3=>'#ff9f43'][$j->shift]??'#888' }}">
                {{ $j->shift_label }}
            </span>
            <span style="font-size:.85rem;font-weight:600">{{ $j->user->name }}</span>
            <span class="mono text-muted" style="font-size:.75rem">{{ $j->jam_mulai }}–{{ $j->jam_selesai }}</span>
            @if($aktif)<span class="badge bg-success ms-auto" style="font-size:.6rem">AKTIF</span>@endif
        </div>
        @endforeach
    </div>
</div>
@endif
@endsection
