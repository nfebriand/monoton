@extends('layouts.app')
@section('title','Laporan Operasional')
@section('page-title','Laporan Operasional Pemancar')

@section('content')

<div class="d-flex align-items-center gap-2 mb-3 no-print flex-wrap">
    <a href="{{ route('laporan.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
    <button onclick="window.print()" class="btn btn-sm btn-danger ms-auto">
        <i class="bi bi-printer me-1"></i>Cetak / Simpan PDF
    </button>
</div>

<div id="laporan-content">

    {{-- KOP --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex align-items-start gap-3 flex-wrap">
                <div style="width:52px;height:52px;min-width:52px;background:#0a3d62;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem">
                    📡
                </div>
                <div class="flex-fill">
                    <h5 class="fw-bold mb-0" style="color:#0a3d62">MonOTOn — Monitoring Operasional Transmisi Online</h5>
                    <div class="text-muted" style="font-size:.82rem">LAPORAN OPERASIONAL PEMANCAR RADIO</div>
                    <div class="mt-2 d-flex flex-wrap gap-3" style="font-size:.78rem">
                        <span><i class="bi bi-calendar3 me-1"></i>Periode:
                            <strong>{{ \Carbon\Carbon::parse($tanggal_dari)->format('d/m/Y') }} s.d. {{ \Carbon\Carbon::parse($tanggal_sampai)->format('d/m/Y') }}</strong>
                        </span>
                        @if($summary['lokasi_filter'])
                        <span><i class="bi bi-geo-alt me-1"></i>Lokasi: <strong>{{ $summary['lokasi_filter'] }}</strong></span>
                        @endif
                        @if($summary['pemancar_filter'])
                        <span><i class="bi bi-broadcast-pin me-1"></i>Pemancar: <strong>{{ $summary['pemancar_filter']->nama_stasiun }}</strong></span>
                        @endif
                        @if($summary['operator_filter'])
                        <span><i class="bi bi-person me-1"></i>Operator: <strong>{{ $summary['operator_filter']->name }}</strong></span>
                        @endif
                        <span><i class="bi bi-clock me-1"></i>Dicetak: <strong>{{ $generated_at }}</strong></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Ringkasan --}}
    <div class="row g-2 mb-3">
        @foreach([
            ['Total Log', $summary['total_pencatatan'], '#0a3d62'],
            ['Rata Out Final', $summary['rata_output_final'] ? number_format($summary['rata_output_final'],1).' W' : '–', '#1e5f8a'],
            ['Rata VSWR Final', $summary['rata_vswr_final'] ? number_format($summary['rata_vswr_final'],3) : '–', '#10ac84'],
            ['Rata Suhu Ruang', $summary['rata_suhu_ruangan'] ? number_format($summary['rata_suhu_ruangan'],1).'°C' : '–', '#ff9f43'],
            ['Rata Kelembaban', $summary['rata_kelembaban'] ? number_format($summary['rata_kelembaban'],1).'%' : '–', '#ee5a24'],
        ] as [$lbl,$val,$clr])
        <div class="col-6 col-md">
            <div class="card text-center">
                <div class="card-body py-2">
                    <div style="font-size:.65rem;color:#636e72">{{ $lbl }}</div>
                    <div class="mono fw-bold" style="font-size:1.2rem;color:{{ $clr }}">{{ $val }}</div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Tabel --}}
    <div class="card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span><i class="bi bi-table me-2"></i>Data Log Operasional</span>
            <span class="badge bg-secondary">{{ $logs->count() }} record</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0" style="font-size:.76rem">
                <thead style="background:#0a3d62;color:#fff">
                    <tr>
                        <th class="ps-2">No</th>
                        <th>Tgl & Waktu</th>
                        <th>Pemancar</th>
                        <th>Lokasi</th>
                        <th>Operator</th>
                        <th>Shift</th>
                        <th class="text-end">Out Final (W)</th>
                        <th class="text-end">Out Driver (W)</th>
                        <th class="text-end">Out Exciter (W)</th>
                        <th class="text-end">Ref Final (W)</th>
                        <th class="text-end">Rej Final (W)</th>
                        <th class="text-center">VSWR Final</th>
                        <th class="text-end">Suhu Pmcr</th>
                        <th class="text-end">Suhu Rmg</th>
                        <th class="text-end">RH%</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $i => $log)
                    @php
                        $v=$log->vswr_final;
                        $cls=$v&&$v<=1.5?'vswr-baik':($v&&$v<=2?'vswr-sedang':'vswr-buruk');
                    @endphp
                    <tr class="{{ $i%2===0?'':'table-light' }}">
                        <td class="ps-2 text-muted">{{ $i+1 }}</td>
                        <td class="mono">{{ $log->dicatat_pada->format('d/m/Y H:i') }}</td>
                        <td>{{ $log->pemancar->nama_stasiun }}</td>
                        <td>
                            @if($log->pemancar->lokasi)
                            <span class="badge bg-secondary" style="font-size:.6rem">{{ $log->pemancar->lokasi }}</span>
                            @else –  @endif
                        </td>
                        <td>{{ $log->user->name }}</td>
                        <td class="text-center">{{ $log->jadwalShift ? 'S'.$log->jadwalShift->shift : '–' }}</td>
                        <td class="text-end mono">{{ $log->output_final_pa ? number_format($log->output_final_pa,1):'–' }}</td>
                        <td class="text-end mono">{{ $log->output_driver ? number_format($log->output_driver,1):'–' }}</td>
                        <td class="text-end mono">{{ $log->output_exciter ? number_format($log->output_exciter,1):'–' }}</td>
                        <td class="text-end mono">{{ $log->reflect_final ? number_format($log->reflect_final,1):'–' }}</td>
                        <td class="text-end mono">{{ $log->reject_final ? number_format($log->reject_final,1):'–' }}</td>
                        <td class="text-center">
                            @if($v)
                            <span class="mono {{ $cls }}">{{ number_format($v,3) }}</span>
                            @else – @endif
                        </td>
                        <td class="text-end mono">{{ $log->suhu_pemancar!==null?$log->suhu_pemancar.'°C':'–' }}</td>
                        <td class="text-end mono">{{ $log->suhu_ruangan!==null?$log->suhu_ruangan.'°C':'–' }}</td>
                        <td class="text-end mono">{{ $log->kelembaban!==null?$log->kelembaban.'%':'–' }}</td>
                        <td style="max-width:120px;white-space:normal">{{ Str::limit($log->keterangan,40) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="16" class="text-center py-3 text-muted">Tidak ada data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tanda Tangan --}}
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-4 text-center">
                    <div style="font-size:.82rem">Mengetahui,</div>
                    <div style="font-size:.82rem">Pengelola / Koordinator</div>
                    <div style="height:55px"></div>
                    <div style="border-top:1px solid #333;padding-top:.3rem;font-size:.82rem">
                        ( {{ $pengelola ?: '____________________' }} )
                    </div>
                </div>
                <div class="col-4"></div>
                <div class="col-4 text-center">
                    <div style="font-size:.82rem">{{ \Carbon\Carbon::parse($tanggal_sampai)->translatedFormat('d F Y') }}</div>
                    <div style="font-size:.82rem">Dibuat Oleh,</div>
                    <div style="height:55px"></div>
                    <div style="border-top:1px solid #333;padding-top:.3rem;font-size:.82rem">
                        ( {{ $generated_by }} )
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('styles')
<style>
@media print {
    #sidebar,#topbar,.no-print{display:none!important;}
    #main{margin-left:0!important;}
    .content{padding:0!important;}
    .card{break-inside:avoid;}
    body{font-size:10px;}
}
</style>
@endpush
