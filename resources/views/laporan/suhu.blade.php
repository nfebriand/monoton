@extends('layouts.app')
@section('title','Grafik Suhu')
@section('page-title','Monitoring Suhu & Kelembaban')

@section('content')
{{-- Navigasi Bulan --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <a href="?bulan={{ $bulanPrev }}&tahun={{ $tahunPrev }}{{ request()->filled('pemancar_id')?'&pemancar_id='.request('pemancar_id'):'' }}{{ request()->filled('lokasi')?'&lokasi='.urlencode(request('lokasi')):'' }}"
               class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-left"></i></a>
            <h6 class="mb-0 fw-bold flex-fill text-center">{{ $namaBulan }}</h6>
            <a href="?bulan={{ $bulanNext }}&tahun={{ $tahunNext }}{{ request()->filled('pemancar_id')?'&pemancar_id='.request('pemancar_id'):'' }}{{ request()->filled('lokasi')?'&lokasi='.urlencode(request('lokasi')):'' }}"
               class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-right"></i></a>
        </div>
    </div>
</div>

{{-- Filter --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="bulan" value="{{ $bulan }}">
            <input type="hidden" name="tahun" value="{{ $tahun }}">
            <div class="col-12 col-md-4">
                <label class="form-label mb-1" style="font-size:.7rem">Pemancar</label>
                <select name="pemancar_id" class="form-select form-select-sm">
                    <option value="">Semua Pemancar</option>
                    @foreach($pemancars as $p)
                    <option value="{{ $p->id }}" {{ request('pemancar_id')==$p->id?'selected':'' }}>
                        {{ $p->nama_stasiun }}{{ $p->lokasi ? ' ('.$p->lokasi.')':'' }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label mb-1" style="font-size:.7rem">Lokasi</label>
                <select name="lokasi" class="form-select form-select-sm">
                    <option value="">Semua Lokasi</option>
                    @foreach($lokasiList as $l)
                    <option value="{{ $l }}" {{ request('lokasi')===$l?'selected':'' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 d-flex gap-2">
                <button class="btn btn-sm btn-primary-custom flex-fill"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="?bulan={{ $bulan }}&tahun={{ $tahun }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                <a href="{{ route('laporan.suhu.pdf', request()->query()) }}"
                   class="btn btn-sm btn-danger" target="_blank">
                    <i class="bi bi-file-pdf"></i>
                </a>
            </div>
        </form>
    </div>
</div>

@if($logs->isNotEmpty())
{{-- Statistik --}}
@php
    $avgRuang = round($logs->whereNotNull('suhu_ruangan')->avg('suhu_ruangan'),1);
    $maxRuang = $logs->whereNotNull('suhu_ruangan')->max('suhu_ruangan');
    $minRuang = $logs->whereNotNull('suhu_ruangan')->min('suhu_ruangan');
    $avgRH    = round($logs->whereNotNull('kelembaban')->avg('kelembaban'),1);
@endphp
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3"><div class="card text-center"><div class="card-body py-2">
        <div style="font-size:.62rem;color:var(--muted)">RATA SUHU RUANG</div>
        <div class="mono fw-bold" style="font-size:1.4rem;color:var(--primary)">{{ $avgRuang ?? '–' }}°C</div>
    </div></div></div>
    <div class="col-6 col-md-3"><div class="card text-center"><div class="card-body py-2">
        <div style="font-size:.62rem;color:var(--muted)">SUHU TERTINGGI</div>
        <div class="mono fw-bold" style="font-size:1.4rem;color:var(--danger)">{{ $maxRuang ?? '–' }}°C</div>
    </div></div></div>
    <div class="col-6 col-md-3"><div class="card text-center"><div class="card-body py-2">
        <div style="font-size:.62rem;color:var(--muted)">SUHU TERENDAH</div>
        <div class="mono fw-bold" style="font-size:1.4rem;color:var(--success)">{{ $minRuang ?? '–' }}°C</div>
    </div></div></div>
    <div class="col-6 col-md-3"><div class="card text-center"><div class="card-body py-2">
        <div style="font-size:.62rem;color:var(--muted)">RATA KELEMBABAN</div>
        <div class="mono fw-bold" style="font-size:1.4rem;color:var(--warning)">{{ $avgRH ?: '–' }}%</div>
    </div></div></div>
</div>

{{-- ── GRAFIK ── --}}
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-graph-up me-2 text-primary"></i>Grafik Suhu & Kelembaban — {{ $namaBulan }}</div>
    <div class="card-body">
        <div style="position:relative;height:300px">
            <canvas id="grafikSuhu"></canvas>
        </div>
    </div>
</div>

{{-- Tabel Data --}}
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-table me-2"></i>Data Suhu & Kelembaban</span>
        <span class="badge bg-secondary">{{ $logs->count() }} pencatatan</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Tgl & Waktu</th>
                        <th>Pemancar</th>
                        <th>Operator</th>
                        <th>Suhu Ruang</th>
                        <th>Suhu Pmcr</th>
                        <th>Kelembaban</th>
                        <th>Ket</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    @php
                        $sr = $log->suhu_ruangan;
                        $sc = $sr!==null ? ($sr>30?'vswr-buruk':($sr>27?'vswr-sedang':'vswr-baik')) : '';
                    @endphp
                    <tr>
                        <td class="ps-3 mono" style="font-size:.76rem">{{ $log->dicatat_pada->format('d/m H:i') }}</td>
                        <td style="font-size:.78rem">{{ $log->pemancar->nama_stasiun }}</td>
                        <td style="font-size:.78rem">{{ $log->user->name }}</td>
                        <td class="mono {{ $sc }}" style="font-size:.78rem">{{ $sr!==null ? $sr.'°C' : '–' }}</td>
                        <td class="mono" style="font-size:.78rem">{{ $log->suhu_pemancar!==null ? $log->suhu_pemancar.'°C' : '–' }}</td>
                        <td class="mono" style="font-size:.78rem">{{ $log->kelembaban!==null ? $log->kelembaban.'%' : '–' }}</td>
                        <td style="font-size:.72rem;color:var(--muted)">{{ Str::limit($log->keterangan,25) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@else
<div class="card">
    <div class="card-body text-center text-muted py-5">
        <i class="bi bi-thermometer-low d-block fs-1 mb-2"></i>
        Tidak ada data suhu untuk <strong>{{ $namaBulan }}</strong>
        @if(request()->filled('pemancar_id') || request()->filled('lokasi'))
        dengan filter yang dipilih.
        @endif
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
@if($logs->isNotEmpty())
(function(){
    // Siapkan data dari PHP — encode langsung ke JSON
    const rawData = @json($chartData);

    const labels       = rawData.map(d => d.x);
    const suhuRuangan  = rawData.map(d => d.suhu_ruangan  ?? null);
    const suhuPemancar = rawData.map(d => d.suhu_pemancar ?? null);
    const kelembaban   = rawData.map(d => d.kelembaban    ?? null);

    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const gridColor   = isDark ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.06)';
    const tickColor   = isDark ? '#9aa7b3' : '#636e72';

    const ctx = document.getElementById('grafikSuhu').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Suhu Ruangan (°C)',
                    data: suhuRuangan,
                    borderColor: '#0a3d62',
                    backgroundColor: 'rgba(10,61,98,.1)',
                    tension: 0.3,
                    pointRadius: rawData.length < 60 ? 3 : 0,
                    spanGaps: true,
                    yAxisID: 'y',
                },
                {
                    label: 'Suhu Pemancar (°C)',
                    data: suhuPemancar,
                    borderColor: '#ee5a24',
                    backgroundColor: 'rgba(238,90,36,.08)',
                    tension: 0.3,
                    pointRadius: rawData.length < 60 ? 3 : 0,
                    spanGaps: true,
                    yAxisID: 'y',
                },
                {
                    label: 'Kelembaban (%)',
                    data: kelembaban,
                    borderColor: '#00b894',
                    backgroundColor: 'rgba(0,184,148,.08)',
                    tension: 0.3,
                    pointRadius: rawData.length < 60 ? 2 : 0,
                    spanGaps: true,
                    yAxisID: 'y2',
                    borderDash: [4,3],
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    labels: { color: tickColor, font: { size: 11 }, boxWidth: 16 }
                },
                tooltip: { mode: 'index', intersect: false },
            },
            scales: {
                x: {
                    ticks: {
                        color: tickColor,
                        maxTicksLimit: 15,
                        font: { size: 10 },
                        maxRotation: 45,
                    },
                    grid: { color: gridColor },
                },
                y: {
                    type: 'linear',
                    position: 'left',
                    title: { display: true, text: 'Suhu (°C)', color: tickColor, font:{size:11} },
                    ticks: { color: tickColor, font:{size:10} },
                    grid: { color: gridColor },
                },
                y2: {
                    type: 'linear',
                    position: 'right',
                    title: { display: true, text: 'Kelembaban (%)', color: '#00b894', font:{size:11} },
                    ticks: { color: '#00b894', font:{size:10} },
                    grid: { drawOnChartArea: false },
                    min: 0,
                    max: 100,
                },
            },
        },
    });
})();
@endif
</script>
@endpush
