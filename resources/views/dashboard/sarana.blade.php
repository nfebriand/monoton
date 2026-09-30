@extends('layouts.app')
@section('title','Dashboard Sarana')
@section('page-title','Dashboard Sarana & Prasarana')

@section('content')
{{-- Statistik Genset Bulan Ini --}}
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-2">
                <div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Operasi Genset</div>
                <div class="mono fw-bold" style="font-size:1.6rem;color:var(--primary)">{{ $statGenset['total_operasi'] }}</div>
                <div class="text-muted" style="font-size:.65rem">bulan ini</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-2">
                <div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">BBM Terpakai</div>
                <div class="mono fw-bold" style="font-size:1.6rem;color:var(--warning)">{{ number_format($statGenset['total_bbm'],1) }}</div>
                <div class="text-muted" style="font-size:.65rem">liter bulan ini</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-2">
                <div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Jam Operasi</div>
                <div class="mono fw-bold" style="font-size:1.6rem;color:var(--success)">{{ number_format($statGenset['total_jam'],1) }}</div>
                <div class="text-muted" style="font-size:.65rem">jam bulan ini</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-2">
                <div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Eviden Bulan Ini</div>
                <div class="mono fw-bold" style="font-size:1.6rem;color:var(--accent)">{{ $evidenBulanIni }}</div>
                <div class="text-muted" style="font-size:.65rem">kegiatan tercatat</div>
            </div>
        </div>
    </div>
</div>

@if($statGenset['gangguan'] > 0)
<div class="alert alert-danger mb-3 py-2">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>{{ $statGenset['gangguan'] }} gangguan genset</strong> tercatat bulan ini.
    <a href="{{ route('genset.index') }}?kondisi=gangguan" class="btn btn-sm btn-danger ms-2">Lihat</a>
</div>
@endif

<div class="row g-3">
    {{-- Log Genset Terbaru --}}
    <div class="col-12 col-lg-7">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-battery-charging me-2 text-primary"></i>Operasional Genset Terbaru</span>
                <a href="{{ route('genset.create') }}" class="btn btn-sm btn-primary-custom">
                    <i class="bi bi-plus me-1"></i>Catat
                </a>
            </div>
            <div class="card-body p-0">
                @forelse($gensetLogs as $log)
                <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom flex-wrap" style="border-color:var(--border)!important">
                    <div style="width:10px;height:10px;border-radius:50%;flex-shrink:0;background:{{ $log->kondisi=='normal'?'#10ac84':'#ee5a24' }}"></div>
                    <div style="min-width:0;flex:1">
                        <div class="fw-bold" style="font-size:.82rem">{{ $log->gensetUnit->nama_unit }}</div>
                        <div class="text-muted" style="font-size:.7rem">
                            {{ $log->tanggal->format('d/m/Y') }}
                            {{ substr($log->jam_mulai,0,5) }}{{ $log->jam_selesai?' – '.substr($log->jam_selesai,0,5):' (berjalan)' }}
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="badge" style="background:#7b1fa2;font-size:.65rem">{{ $log->alasan_label }}</span>
                        @if($log->pemakaian_bbm!==null)
                        <div class="mono text-muted" style="font-size:.7rem">{{ number_format($log->pemakaian_bbm,1) }} L</div>
                        @endif
                    </div>
                    <a href="{{ route('genset.show',$log) }}" class="btn btn-sm btn-outline-primary flex-shrink-0"><i class="bi bi-eye"></i></a>
                </div>
                @empty
                <div class="text-center text-muted py-4 small">
                    <i class="bi bi-battery d-block fs-2 mb-1"></i>Belum ada log genset
                </div>
                @endforelse
            </div>
            <div class="card-footer py-2 text-center">
                <a href="{{ route('genset.index') }}" style="font-size:.78rem">Lihat semua log genset →</a>
            </div>
        </div>
    </div>

    {{-- Eviden Terbaru + Jadwal --}}
    <div class="col-12 col-lg-5">
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-camera me-2 text-success"></i>Eviden Terbaru</span>
                <a href="{{ route('eviden.create') }}" class="btn btn-sm btn-primary-custom">
                    <i class="bi bi-plus me-1"></i>Catat
                </a>
            </div>
            <div class="card-body p-0">
                @forelse($evidenTerbaru as $ev)
                <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom" style="border-color:var(--border)!important">
                    <div style="min-width:0;flex:1">
                        <div style="font-size:.82rem;font-weight:600">{{ Str::limit($ev->judul,40) }}</div>
                        <div class="text-muted" style="font-size:.68rem">{{ $ev->tanggal->format('d/m/Y') }} · {{ $ev->user->name }}</div>
                    </div>
                    <a href="{{ route('eviden.show',$ev) }}" class="btn btn-sm btn-outline-primary flex-shrink-0"><i class="bi bi-eye"></i></a>
                </div>
                @empty
                <div class="text-center text-muted py-3 small"><i class="bi bi-camera d-block fs-3 mb-1"></i>Belum ada eviden</div>
                @endforelse
            </div>
        </div>

        @if($jadwalHariIni->isNotEmpty())
        <div class="card">
            <div class="card-header"><i class="bi bi-calendar3 me-2 text-success"></i>Jadwal Shift Hari Ini</div>
            <div class="card-body p-0">
                @foreach($jadwalHariIni as $j)
                @php $aktif = now()->format('H:i') >= $j->jam_mulai && now()->format('H:i') < $j->jam_selesai; @endphp
                <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom" style="border-color:var(--border)!important">
                    <span class="badge" style="background:#10ac84">{{ $j->shift_label }}</span>
                    <span style="font-size:.84rem;font-weight:600">{{ $j->user->name }}</span>
                    <span class="text-muted mono" style="font-size:.72rem">{{ $j->jam_mulai }}–{{ $j->jam_selesai }}</span>
                    @if($aktif)<span class="badge bg-success ms-auto" style="font-size:.6rem">AKTIF</span>@endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
