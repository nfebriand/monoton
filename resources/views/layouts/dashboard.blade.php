@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#0a3d62,#1e5f8a);color:#fff">
            <div class="stat-icon" style="background:rgba(255,255,255,.15)"><i class="bi bi-broadcast-pin"></i></div>
            <div>
                <div class="stat-value">{{ $stats['total_pemancar'] }}</div>
                <div class="stat-label" style="color:rgba(255,255,255,.7)">Total Pemancar Aktif</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#10ac84,#00b894);color:#fff">
            <div class="stat-icon" style="background:rgba(255,255,255,.15)"><i class="bi bi-people-fill"></i></div>
            <div>
                <div class="stat-value">{{ $stats['total_operator'] }}</div>
                <div class="stat-label" style="color:rgba(255,255,255,.7)">Operator Aktif</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#ff9f43,#feca57);color:#fff">
            <div class="stat-icon" style="background:rgba(255,255,255,.15)"><i class="bi bi-journal-check"></i></div>
            <div>
                <div class="stat-value">{{ $stats['log_hari_ini'] }}</div>
                <div class="stat-label" style="color:rgba(255,255,255,.7)">Log Hari Ini</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#ee5a24,#d63031);color:#fff">
            <div class="stat-icon" style="background:rgba(255,255,255,.15)"><i class="bi bi-calendar3"></i></div>
            <div>
                <div class="stat-value">{{ $stats['shift_hari_ini'] }}</div>
                <div class="stat-label" style="color:rgba(255,255,255,.7)">Shift Terjadwal Hari Ini</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Status Pemancar -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-broadcast-pin me-2 text-primary"></i>Status Pemancar</span>
                <a href="{{ route('pemancar.index') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Nama Stasiun</th>
                            <th>Modulasi</th>
                            <th>Kapasitas</th>
                            <th>Log Terakhir</th>
                            <th>VSWR Final</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pemancars as $p)
                        @php $lastLog = $p->operasionalLogs->first(); @endphp
                        <tr>
                            <td class="ps-3">
                                <div class="fw-600">{{ $p->nama_stasiun }}</div>
                                <small class="text-muted">{{ $p->merk }} {{ $p->tipe_unit }}</small>
                            </td>
                            <td>
                                <span class="badge {{ $p->modulasi === 'FM' ? 'bg-primary' : 'bg-warning text-dark' }}">
                                    {{ $p->modulasi }}
                                </span>
                            </td>
                            <td class="mono">{{ number_format($p->kapasitas_output_final, 0) }} W</td>
                            <td>
                                @if($lastLog)
                                    <div class="mono" style="font-size:.78rem">{{ $lastLog->dicatat_pada->format('d/m H:i') }}</div>
                                    <small class="text-muted">{{ $lastLog->user->name }}</small>
                                @else
									<div class="mono" style="font-size:.78rem">{{ $lastLog->dicatat_pada->format('d/m H:i') }}</div>
                                    <small class="text-muted">{{ $lastLog->user->name }}</small>
								@endif
                            </td>
                            <td>
                                @if($lastLog && $lastLog->vswr_final)
                                    @php $st = \App\Services\VswrCalculator::getStatus($lastLog->vswr_final); @endphp
                                    <span class="mono vswr-{{ $st['color'] === 'success' ? 'baik' : ($st['color'] === 'warning' ? 'sedang' : 'buruk') }}">
                                        {{ $st['icon'] }} {{ number_format($lastLog->vswr_final, 2) }}
                                    </span>
                                @else
                                    <span class="text-muted">–</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('pemancar.show', $p) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data pemancar</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Jadwal Shift Hari Ini -->
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-clock me-2 text-warning"></i>Jadwal Shift Hari Ini
                <small class="text-muted ms-1">{{ now()->format('d/m/Y') }}</small>
            </div>
            <div class="card-body p-0">
                @forelse($jadwalHariIni as $jadwal)
                <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom">
                    <div class="badge bg-primary bg-opacity-10 text-primary" style="width:64px;font-size:.7rem">
                        Shift {{ $jadwal->shift }}
                    </div>
                    <div style="flex:1">
                        <div class="fw-600" style="font-size:.83rem">{{ $jadwal->user->name }}</div>
                        <small class="text-muted mono">{{ $jadwal->jam_mulai }} – {{ $jadwal->jam_selesai }}</small>
                    </div>
                    <div class="text-end">
                        <small class="text-muted" style="font-size:.68rem">{{ $jadwal->pemancar->nama_stasiun }}</small>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-3 small">Tidak ada jadwal hari ini</div>
                @endforelse
            </div>
        </div>

        <!-- Grafik Aktivitas 7 Hari -->
        <div class="card">
            <div class="card-header"><i class="bi bi-bar-chart me-2"></i>Log 7 Hari Terakhir</div>
            <div class="card-body">
                <canvas id="chartLog" height="140"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const ctx = document.getElementById('chartLog').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: {!! json_encode($grafikData->pluck('tanggal')) !!},
        datasets: [{
            label: 'Jumlah Log',
            data: {!! json_encode($grafikData->pluck('count')) !!},
            backgroundColor: 'rgba(10,61,98,.7)',
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } },
            x: { grid: { display: false } }
        }
    }
});
</script>
@endpush
