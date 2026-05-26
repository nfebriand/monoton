@extends('layouts.app')
@section('title','Grafik Suhu')
@section('page-title','Monitoring Suhu & Kelembaban')

@section('content')
@php
    $namaBulan = \Carbon\Carbon::create($tahun,$bulan,1)->translatedFormat('F Y');
    $bulanPrev = $bulan==1?12:$bulan-1; $tahunPrev = $bulan==1?$tahun-1:$tahun;
    $bulanNext = $bulan==12?1:$bulan+1; $tahunNext = $bulan==12?$tahun+1:$tahun;
@endphp

{{-- Filter --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-sm-5 col-md-3">
                <label class="form-label mb-1">Lokasi Pemancar</label>
                <select name="lokasi" class="form-select form-select-sm" id="selLokasi">
                    <option value="">Semua Lokasi</option>
                    @foreach($semuaLokasi as $lok)
                    <option value="{{ $lok }}" {{ ($lokasi??'')===$lok?'selected':'' }}>{{ $lok }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-sm-5 col-md-3">
                <label class="form-label mb-1">Pemancar</label>
                <select name="pemancar_id" class="form-select form-select-sm">
                    <option value="">Semua Pemancar</option>
                    @foreach($pemancars as $p)
                    <option value="{{ $p->id }}" data-lokasi="{{ $p->lokasi }}"
                        {{ $pemancarId==$p->id?'selected':'' }}>{{ $p->nama_stasiun }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <label class="form-label mb-1">Bulan</label>
                <select name="bulan" class="form-select form-select-sm">
                    @for($m=1;$m<=12;$m++)
                    <option value="{{ $m }}" {{ $bulan==$m?'selected':'' }}>
                        {{ \Carbon\Carbon::create(null,$m)->translatedFormat('F') }}
                    </option>
                    @endfor
                </select>
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <label class="form-label mb-1">Tahun</label>
                <select name="tahun" class="form-select form-select-sm">
                    @for($y=now()->year;$y>=now()->year-3;$y--)
                    <option value="{{ $y }}" {{ $tahun==$y?'selected':'' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel me-1"></i>Tampilkan
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Nav bulan --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="?bulan={{ $bulanPrev }}&tahun={{ $tahunPrev }}&pemancar_id={{ $pemancarId }}&lokasi={{ $lokasi }}"
       class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-left"></i></a>
    <h6 class="mb-0 fw-bold flex-fill text-center">{{ $namaBulan }}</h6>
    <a href="?bulan={{ $bulanNext }}&tahun={{ $tahunNext }}&pemancar_id={{ $pemancarId }}&lokasi={{ $lokasi }}"
       class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-right"></i></a>
</div>

@if($dataGrafik->isNotEmpty())
@php
    $avgRuang = round($dataGrafik->avg('rata_suhu_ruangan'),1);
    $maxRuang = $dataGrafik->max('max_suhu_ruangan');
    $minRuang = $dataGrafik->min('min_suhu_ruangan');
    $avgRH    = round($dataGrafik->whereNotNull('rata_kelembaban')->avg('rata_kelembaban'),1);
@endphp

{{-- Stat cards --}}
<div class="row g-2 mb-3">
    @foreach([
        ['Rata-rata Suhu Ruang',$avgRuang.'°C','#0a3d62','bi-thermometer-half'],
        ['Suhu Tertinggi',$maxRuang.'°C','#ee5a24','bi-thermometer-high'],
        ['Suhu Terendah',$minRuang.'°C','#10ac84','bi-thermometer-low'],
        ['Rata-rata Kelembaban',$avgRH?$avgRH.'%':'–','#ff9f43','bi-droplet-half'],
    ] as [$lbl,$val,$clr,$icon])
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-2">
                <i class="bi {{ $icon }} mb-1" style="color:{{ $clr }};font-size:1.1rem"></i>
                <div style="font-size:.65rem;color:#636e72">{{ $lbl }}</div>
                <div class="mono fw-bold" style="font-size:1.2rem;color:{{ $clr }}">{{ $val }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Grafik --}}
<div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span><i class="bi bi-graph-up me-2 text-danger"></i>Grafik Suhu & Kelembaban — {{ $namaBulan }}</span>
        {{-- Tombol cetak PDF --}}
        <a href="{{ route('laporan.suhu.pdf', ['bulan'=>$bulan,'tahun'=>$tahun,'pemancar_id'=>$pemancarId,'lokasi'=>$lokasi]) }}"
           class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i>Cetak PDF
        </a>
    </div>
    <div class="card-body">
        <canvas id="chartSuhu" height="130"></canvas>
    </div>
</div>

{{-- Tabel per-log --}}
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-table me-2"></i>Data Suhu Per Pencatatan</span>
        <span class="badge bg-secondary">{{ $logs->count() }} record</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th class="ps-3">Waktu</th>
                    <th>Pemancar</th>
                    <th>Lokasi</th>
                    <th>Operator</th>
                    <th class="text-center">Suhu Ruang</th>
                    <th class="text-center">Suhu Pmcr</th>
                    <th class="text-center">Kelembaban</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                @php $sr=$log->suhu_ruangan; $sc=$sr!==null?($sr>30?'#ee5a24':($sr>27?'#ff9f43':'#10ac84')):'#ccc'; @endphp
                <tr>
                    <td class="ps-3 mono" style="font-size:.78rem">{{ $log->dicatat_pada->format('d/m/Y H:i') }}</td>
                    <td style="font-size:.82rem">{{ $log->pemancar->nama_stasiun }}</td>
                    <td>
                        @if($log->pemancar->lokasi)
                        <span class="badge bg-secondary" style="font-size:.62rem">{{ $log->pemancar->lokasi }}</span>
                        @else <span class="text-muted">–</span> @endif
                    </td>
                    <td style="font-size:.82rem">{{ $log->user->name }}</td>
                    <td class="text-center mono fw-bold" style="color:{{ $sc }}">
                        {{ $sr!==null?$sr.'°C':'–' }}
                    </td>
                    <td class="text-center mono">{{ $log->suhu_pemancar!==null?$log->suhu_pemancar.'°C':'–' }}</td>
                    <td class="text-center mono">{{ $log->kelembaban!==null?$log->kelembaban.'%':'–' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
<div class="card">
    <div class="card-body text-center text-muted py-5">
        <i class="bi bi-thermometer fs-1 d-block mb-2"></i>
        Belum ada data suhu untuk {{ $namaBulan }}
        @if($lokasi) di lokasi <strong>{{ $lokasi }}</strong>@endif
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
// Filter dropdown pemancar berdasarkan lokasi
document.getElementById('selLokasi')?.addEventListener('change', function(){
    const lok = this.value;
    document.querySelectorAll('[name=pemancar_id] option').forEach(opt => {
        if (!opt.value) return;
        opt.style.display = (!lok || opt.dataset.lokasi === lok) ? '' : 'none';
    });
    document.querySelector('[name=pemancar_id]').value = '';
});

@if($dataGrafik->isNotEmpty())
new Chart(document.getElementById('chartSuhu').getContext('2d'),{
    type:'line',
    data:{
        labels:{!! json_encode($dataGrafik->pluck('tanggal')) !!},
        datasets:[
            {label:'Rata Suhu Ruang (°C)',data:{!! json_encode($dataGrafik->pluck('rata_suhu_ruangan')) !!},
             borderColor:'#0a3d62',backgroundColor:'rgba(10,61,98,.1)',fill:true,tension:.4,pointRadius:3},
            {label:'Maks (°C)',data:{!! json_encode($dataGrafik->pluck('max_suhu_ruangan')) !!},
             borderColor:'#ee5a24',borderDash:[4,4],tension:.4,pointRadius:2,fill:false},
            {label:'Min (°C)',data:{!! json_encode($dataGrafik->pluck('min_suhu_ruangan')) !!},
             borderColor:'#10ac84',borderDash:[4,4],tension:.4,pointRadius:2,fill:false},
            {label:'Kelembaban (%)',data:{!! json_encode($dataGrafik->pluck('rata_kelembaban')) !!},
             borderColor:'#ff9f43',backgroundColor:'rgba(255,159,67,.08)',fill:true,tension:.4,
             pointRadius:3,yAxisID:'y2'},
        ]
    },
    options:{
        responsive:true,
        interaction:{mode:'index',intersect:false},
        plugins:{legend:{position:'bottom',labels:{font:{size:11}}}},
        scales:{
            y:{title:{display:true,text:'Suhu (°C)'},suggestedMin:15,suggestedMax:45},
            y2:{title:{display:true,text:'RH (%)'},position:'right',suggestedMin:0,suggestedMax:100,
                grid:{drawOnChartArea:false}},
            x:{grid:{display:false}}
        }
    }
});
@endif
</script>
@endpush
