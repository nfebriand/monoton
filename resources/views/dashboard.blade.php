@extends('layouts.app')
@section('title','Dashboard')
@section('page-title','Dashboard')

@section('content')
@php $user = auth()->user(); @endphp

{{-- STAT CARDS --}}
<div class="row g-2 mb-3">
    <div class="col-6 col-xl-3">
        <div class="card h-100" style="background:linear-gradient(135deg,#0a3d62,#1e5f8a);border:none">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div style="width:44px;height:44px;min-width:44px;border-radius:10px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:#fff">
                    <i class="bi bi-broadcast-pin"></i>
                </div>
                <div>
                    <div style="font-size:1.6rem;font-weight:700;color:#fff;line-height:1">{{ $stats['total_pemancar'] }}</div>
                    <div style="font-size:.7rem;color:rgba(255,255,255,.7)">Pemancar Aktif</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card h-100" style="background:linear-gradient(135deg,#10ac84,#00b894);border:none">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div style="width:44px;height:44px;min-width:44px;border-radius:10px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:#fff">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <div style="font-size:1.6rem;font-weight:700;color:#fff;line-height:1">{{ $stats['total_operator'] }}</div>
                    <div style="font-size:.7rem;color:rgba(255,255,255,.7)">Operator Aktif</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card h-100" style="background:linear-gradient(135deg,#ff9f43,#feca57);border:none">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div style="width:44px;height:44px;min-width:44px;border-radius:10px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:#fff">
                    <i class="bi bi-journal-check"></i>
                </div>
                <div>
                    <div style="font-size:1.6rem;font-weight:700;color:#fff;line-height:1">{{ $stats['log_hari_ini'] }}</div>
                    <div style="font-size:.7rem;color:rgba(255,255,255,.7)">Log Hari Ini</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card h-100" style="background:linear-gradient(135deg,#ee5a24,#d63031);border:none">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div style="width:44px;height:44px;min-width:44px;border-radius:10px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:#fff">
                    <i class="bi bi-calendar3"></i>
                </div>
                <div>
                    <div style="font-size:1.6rem;font-weight:700;color:#fff;line-height:1">{{ $stats['shift_hari_ini'] }}</div>
                    <div style="font-size:.7rem;color:rgba(255,255,255,.7)">Shift Terjadwal</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- STATUS PEMANCAR --}}
    <div class="col-12 col-xl-8">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2 flex-wrap">
                <span><i class="bi bi-broadcast-pin me-1 text-primary"></i>Status Pemancar</span>

                {{-- Filter Lokasi (hanya admin) --}}
                @if($user->isAdmin())
                <form method="GET" class="ms-auto d-flex align-items-center gap-2">
                    <select name="lokasi" class="form-select form-select-sm" style="width:auto;font-size:.75rem"
                            onchange="this.form.submit()">
                        <option value="">Semua Lokasi</option>
                        @foreach($semuaLokasi as $lok)
                        <option value="{{ $lok }}" {{ $filterLokasi===$lok?'selected':'' }}>{{ $lok }}</option>
                        @endforeach
                    </select>
                </form>
                @else
                <span class="badge bg-primary ms-auto" style="font-size:.7rem">
                    <i class="bi bi-geo-alt me-1"></i>{{ $user->lokasi_dinas ?? 'Semua' }}
                </span>
                @endif
            </div>

            {{-- Mobile: card view; Desktop: table --}}
            <div class="d-none d-md-block table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Nama Stasiun</th>
                            <th>Lokasi</th>
                            <th>Mod.</th>
                            <th class="text-end">Kapasitas</th>
                            <th>Log Terakhir</th>
                            <th class="text-center">VSWR</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pemancars as $p)
                        @php $lastLog = $p->operasionalLogs->first(); @endphp
                        <tr>
                            <td class="ps-3">
                                <div class="fw-600" style="font-size:.85rem">{{ $p->nama_stasiun }}</div>
                                <small class="text-muted">{{ $p->merk }} {{ $p->tipe_unit }}</small>
                            </td>
                            <td>
                                @if($p->lokasi)
                                <span class="badge bg-secondary" style="font-size:.65rem">{{ $p->lokasi }}</span>
                                @else <span class="text-muted">–</span> @endif
                            </td>
                            <td><span class="badge {{ $p->modulasi==='FM'?'bg-primary':'bg-warning text-dark' }}">{{ $p->modulasi }}</span></td>
                            <td class="text-end mono" style="font-size:.82rem">{{ number_format($p->kapasitas_output_final,0) }} W</td>
                            <td>
                                @if($lastLog)
                                <div class="mono" style="font-size:.75rem">{{ $lastLog->dicatat_pada->format('d/m H:i') }}</div>
                                <small class="text-muted">{{ $lastLog->user->name }}</small>
                                @else <span class="text-muted small">Belum ada</span> @endif
                            </td>
                            <td class="text-center">
                                @if($lastLog && $lastLog->vswr_final)
                                    @php $v=$lastLog->vswr_final;$cls=$v<=1.5?'vswr-baik':($v<=2?'vswr-sedang':'vswr-buruk'); @endphp
                                    <span class="mono {{ $cls }}" style="font-size:.82rem">{{ number_format($v,3) }}</span>
                                @else <span class="text-muted">–</span> @endif
                            </td>
                            <td class="pe-2">
                                <a href="{{ route('pemancar.show',$p) }}" class="btn btn-sm btn-outline-primary" style="padding:.15rem .45rem">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data pemancar</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile card list --}}
            <div class="d-md-none">
                @forelse($pemancars as $p)
                @php $lastLog = $p->operasionalLogs->first(); @endphp
                <div class="d-flex align-items-center gap-3 px-3 py-3 border-bottom">
                    <div style="width:40px;height:40px;min-width:40px;border-radius:8px;background:#f0f4f8;display:flex;align-items:center;justify-content:center">
                        <i class="bi bi-broadcast-pin text-primary"></i>
                    </div>
                    <div class="flex-fill" style="min-width:0">
                        <div class="fw-600 text-truncate" style="font-size:.85rem">{{ $p->nama_stasiun }}</div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mt-1">
                            <span class="badge {{ $p->modulasi==='FM'?'bg-primary':'bg-warning text-dark' }}" style="font-size:.62rem">{{ $p->modulasi }}</span>
                            @if($p->lokasi)<span class="badge bg-secondary" style="font-size:.62rem">{{ $p->lokasi }}</span>@endif
                            @if($lastLog && $lastLog->vswr_final)
                                @php $v=$lastLog->vswr_final;$cls=$v<=1.5?'vswr-baik':($v<=2?'vswr-sedang':'vswr-buruk'); @endphp
                                <span class="mono {{ $cls }}" style="font-size:.75rem">VSWR: {{ number_format($v,3) }}</span>
                            @endif
                        </div>
                        @if($lastLog)
                        <small class="text-muted">Log: {{ $lastLog->dicatat_pada->format('d/m H:i') }} — {{ $lastLog->user->name }}</small>
                        @endif
                    </div>
                    <a href="{{ route('pemancar.show',$p) }}" class="btn btn-sm btn-outline-primary flex-shrink-0">
                        <i class="bi bi-eye"></i>
                    </a>
                </div>
                @empty
                <div class="text-center text-muted py-4">Belum ada pemancar</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- JADWAL & GRAFIK --}}
    <div class="col-12 col-xl-4">
        {{-- Jadwal Shift --}}
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-clock me-1 text-warning"></i>Jadwal Shift Hari Ini
                <small class="text-muted ms-1 fw-normal">{{ now()->format('d/m/Y') }}</small>
            </div>
            @forelse($jadwalHariIni as $j)
            @php $sc=[1=>'#0a3d62',2=>'#10ac84',3=>'#ff9f43']; @endphp
            <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom">
                <span class="badge" style="background:{{ $sc[$j->shift]??'#888' }};min-width:58px;font-size:.68rem">
                    Shift {{ $j->shift }}
                </span>
                <div class="flex-fill" style="min-width:0">
                    <div class="fw-600 text-truncate" style="font-size:.82rem">{{ $j->user->name }}</div>
                    <div class="mono text-muted" style="font-size:.7rem">{{ $j->jam_mulai }}–{{ $j->jam_selesai }}</div>
                </div>
            </div>
            @empty
            <div class="text-center text-muted py-3 small">Tidak ada jadwal hari ini</div>
            @endforelse
        </div>

        {{-- Grafik --}}
        <div class="card">
            <div class="card-header"><i class="bi bi-bar-chart me-1"></i>Log 7 Hari Terakhir</div>
            <div class="card-body">
                <canvas id="chartLog" height="150"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
new Chart(document.getElementById('chartLog').getContext('2d'),{
    type:'bar',
    data:{
        labels:{!! json_encode($grafikData->pluck('tanggal')) !!},
        datasets:[{
            label:'Jumlah Log',
            data:{!! json_encode($grafikData->pluck('count')) !!},
            backgroundColor:'rgba(10,61,98,.75)',borderRadius:5
        }]
    },
    options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}},x:{grid:{display:false}}}}
});
</script>
@endpush
