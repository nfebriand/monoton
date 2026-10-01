@extends('layouts.app')
@section('title', $pemancar->nama_stasiun)
@section('page-title','Detail Pemancar')

@section('content')
<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    <a href="{{ route('pemancar.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
    <span class="fw-bold text-truncate">{{ $pemancar->nama_stasiun }}</span>
    @if(auth()->user()->isAdmin())
    <div class="d-flex gap-2 ms-auto">
        <a href="{{ route('pemancar.edit',$pemancar) }}" class="btn btn-sm btn-outline-warning">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        <form action="{{ route('pemancar.destroy',$pemancar) }}" method="POST"
              onsubmit="return confirm('Hapus pemancar ini beserta seluruh data terkait?')">
            @csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Hapus</button>
        </form>
    </div>
    @endif
</div>

<div class="row g-3">
    {{-- KIRI: Info, Statistik, Foto, Riwayat --}}
    <div class="col-12 col-lg-8">

        {{-- Info Utama --}}
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-broadcast-pin me-2 text-primary"></i>Informasi Pemancar</span>
                <span class="badge {{ $pemancar->is_active?'bg-success':'bg-secondary' }}">
                    {{ $pemancar->is_active?'Aktif':'Non-aktif' }}
                </span>
            </div>
            <div class="card-body">
                <h5 class="fw-bold mb-1">{{ $pemancar->nama_stasiun }}</h5>
                <div class="d-flex gap-2 flex-wrap mb-3">
                    <span class="badge {{ $pemancar->modulasi==='FM'?'bg-primary':'bg-warning text-dark' }}">
                        {{ $pemancar->modulasi }}
                        @if($pemancar->frekuensi) {{ $pemancar->frekuensi }} @endif
                    </span>
                    @if($pemancar->lokasi)
                    <span class="badge bg-secondary"><i class="bi bi-geo-alt me-1"></i>{{ $pemancar->lokasi }}</span>
                    @endif
                    @if($umurUnit !== null)
                    <span class="badge" style="background:#7b1fa2">
                        <i class="bi bi-clock-history me-1"></i>{{ $umurUnit }} tahun
                    </span>
                    @endif
                </div>

                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="text-muted" style="font-size:.7rem">Merk</div>
                        <div class="fw-bold">{{ $pemancar->merk ?? '–' }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted" style="font-size:.7rem">Tipe Unit</div>
                        <div class="fw-bold">{{ $pemancar->tipe_unit ?? '–' }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted" style="font-size:.7rem">Tahun Pembuatan</div>
                        <div class="fw-bold mono">{{ $pemancar->tahun_pembuatan ?? '–' }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted" style="font-size:.7rem">ID Pemancar</div>
                        <div class="fw-bold mono">#{{ str_pad($pemancar->id,4,'0',STR_PAD_LEFT) }}</div>
                    </div>
                </div>

                <hr style="border-color:var(--border)">

                <div class="text-muted mb-2" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px">
                    Kapasitas Output Maksimum
                </div>
                <div class="row g-2">
                    <div class="col-4">
                        <div class="p-2 rounded text-center" style="background:var(--bg);border:1px solid var(--border)">
                            <div class="text-muted" style="font-size:.65rem">FINAL PA</div>
                            <div class="fw-bold mono" style="font-size:1.1rem;color:var(--primary)">
                                {{ number_format($pemancar->kapasitas_output_final,0) }}<small style="font-size:.6rem"> W</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 rounded text-center" style="background:var(--bg);border:1px solid var(--border)">
                            <div class="text-muted" style="font-size:.65rem">DRIVER</div>
                            <div class="fw-bold mono" style="font-size:1.1rem">
                                {{ $pemancar->kapasitas_output_driver ? number_format($pemancar->kapasitas_output_driver,0) : '–' }}
                                @if($pemancar->kapasitas_output_driver)<small style="font-size:.6rem"> W</small>@endif
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 rounded text-center" style="background:var(--bg);border:1px solid var(--border)">
                            <div class="text-muted" style="font-size:.65rem">EXCITER</div>
                            <div class="fw-bold mono" style="font-size:1.1rem">
                                {{ $pemancar->kapasitas_output_exciter ? number_format($pemancar->kapasitas_output_exciter,0) : '–' }}
                                @if($pemancar->kapasitas_output_exciter)<small style="font-size:.6rem"> W</small>@endif
                            </div>
                        </div>
                    </div>
                </div>
                @if($efisiensi)
                <div class="text-muted mt-2" style="font-size:.72rem">
                    <i class="bi bi-graph-up me-1"></i>Rasio penguatan Driver→Final: <strong>{{ $efisiensi }}%</strong>
                </div>
                @endif

                @if($pemancar->keterangan)
                <div class="mt-3 p-3 rounded" style="background:var(--bg);border:1px solid var(--border);
                     border-left:4px solid var(--primary);font-size:.85rem;line-height:1.6;white-space:pre-line">
                    {{ $pemancar->keterangan }}
                </div>
                @endif
            </div>
        </div>

        {{-- ── Statistik 30 Hari ── --}}
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-bar-chart-line me-2 text-success"></i>Statistik 30 Hari Terakhir</span>
                <span class="badge bg-secondary">{{ $statistik['total_log'] }} pencatatan</span>
            </div>
            <div class="card-body">
                @if($statistik['total_log'] > 0)
                <div class="row g-2">
                    <div class="col-6 col-md-3">
                        <div class="p-2 rounded text-center" style="background:var(--bg);border:1px solid var(--border)">
                            <div class="text-muted" style="font-size:.62rem">RATA OUTPUT FINAL</div>
                            <div class="fw-bold mono" style="font-size:1rem">
                                {{ $statistik['rata_output'] ? number_format($statistik['rata_output'],1).' W' : '–' }}
                            </div>
                            @if($statistik['min_output'] && $statistik['max_output'])
                            <div class="text-muted" style="font-size:.6rem">
                                {{ number_format($statistik['min_output'],0) }} – {{ number_format($statistik['max_output'],0) }} W
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        @php
                            $rv = $statistik['rata_vswr'];
                            $vc = $rv ? ($rv<=1.5?'vswr-baik':($rv<=2?'vswr-sedang':'vswr-buruk')) : '';
                        @endphp
                        <div class="p-2 rounded text-center" style="background:var(--bg);border:1px solid var(--border)">
                            <div class="text-muted" style="font-size:.62rem">RATA VSWR FINAL</div>
                            <div class="fw-bold mono {{ $vc }}" style="font-size:1rem">
                                {{ $rv ? number_format($rv,3) : '–' }}
                            </div>
                            @if($statistik['max_vswr'])
                            <div class="text-muted" style="font-size:.6rem">max: {{ number_format($statistik['max_vswr'],3) }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 rounded text-center" style="background:var(--bg);border:1px solid var(--border)">
                            <div class="text-muted" style="font-size:.62rem">RATA SUHU PEMANCAR</div>
                            <div class="fw-bold mono" style="font-size:1rem">
                                {{ $statistik['rata_suhu_pmcr'] ? number_format($statistik['rata_suhu_pmcr'],1).'°C' : '–' }}
                            </div>
                            @if($statistik['max_suhu_pmcr'])
                            <div class="text-muted" style="font-size:.6rem">max: {{ number_format($statistik['max_suhu_pmcr'],1) }}°C</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 rounded text-center" style="background:var(--bg);border:1px solid var(--border)">
                            <div class="text-muted" style="font-size:.62rem">VSWR BERMASALAH</div>
                            <div class="fw-bold mono" style="font-size:1rem;color:{{ $statistik['jumlah_vswr_buruk']>0?'var(--danger)':'var(--success)' }}">
                                {{ $statistik['jumlah_vswr_buruk'] }}x
                            </div>
                            <div class="text-muted" style="font-size:.6rem">VSWR ≥ 2.0</div>
                        </div>
                    </div>
                </div>
                @if($statistik['rata_suhu_ruang'])
                <div class="text-muted mt-2" style="font-size:.72rem">
                    <i class="bi bi-thermometer-half me-1"></i>Rata-rata suhu ruangan: <strong>{{ number_format($statistik['rata_suhu_ruang'],1) }}°C</strong>
                </div>
                @endif
                @else
                <div class="text-center text-muted py-3 small">
                    <i class="bi bi-bar-chart d-block fs-3 mb-1"></i>Belum ada data 30 hari terakhir
                </div>
                @endif
            </div>
        </div>

        {{-- Foto --}}
        @if($pemancar->fotos->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-images me-2"></i>Foto Pemancar ({{ $pemancar->fotos->count() }})</div>
            <div class="card-body">
                <div style="height:280px;border-radius:8px;overflow:hidden;margin-bottom:.75rem;background:var(--bg)">
                    <img id="pemancarFotoUtama" src="{{ $pemancar->fotos->first()->url }}"
                         onerror="this.parentElement.style.background='#eee'"
                         onclick="bukaLightbox(this.src)"
                         style="width:100%;height:100%;object-fit:cover;cursor:zoom-in;display:block">
                </div>
                @if($pemancar->fotos->count()>1)
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(80px,1fr));gap:.5rem">
                    @foreach($pemancar->fotos as $i=>$foto)
                    <div style="aspect-ratio:1;border-radius:6px;overflow:hidden;cursor:pointer;
                         border:2px solid {{ $i===0?'var(--primary)':'var(--border)' }}"
                         onclick="document.getElementById('pemancarFotoUtama').src='{{ $foto->url }}'">
                        <img src="{{ $foto->thumb_url }}" style="width:100%;height:100%;object-fit:cover"
                             onerror="this.parentElement.style.background='#eee'">
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Riwayat 10 Log Terakhir --}}
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-journal-text me-2 text-primary"></i>10 Pencatatan Terakhir</span>
                <a href="{{ route('operasional.index') }}?pemancar_id={{ $pemancar->id }}" class="btn btn-sm btn-outline-primary" style="font-size:.7rem">
                    Lihat Semua
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Waktu</th><th>Operator</th>
                            <th>Out Final</th><th>VSWR</th><th>Suhu Pmcr</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($riwayatLog as $log)
                        @php
                            $v=$log->vswr_final;
                            $vc=$v ? ($v<=1.5?'vswr-baik':($v<=2?'vswr-sedang':'vswr-buruk')) : '';
                        @endphp
                        <tr style="cursor:pointer" onclick="window.location='{{ route('operasional.show',$log) }}'">
                            <td class="ps-3 mono" style="font-size:.76rem">{{ $log->dicatat_pada->format('d/m/y H:i') }}</td>
                            <td style="font-size:.78rem">{{ $log->user->name }}</td>
                            <td class="mono" style="font-size:.78rem">{{ $log->output_final_pa ? number_format($log->output_final_pa,1).'W' : '–' }}</td>
                            <td class="mono {{ $vc }}" style="font-size:.78rem">{{ $v ? number_format($v,3) : '–' }}</td>
                            <td class="mono" style="font-size:.78rem">{{ $log->suhu_pemancar!==null ? $log->suhu_pemancar.'°C' : '–' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">Belum ada riwayat</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- KANAN: Operator, Log Terakhir, Riwayat --}}
    <div class="col-12 col-lg-4">

        {{-- Operator yang bertugas di lokasi ini --}}
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-people me-2 text-primary"></i>Operator Bertugas</span>
                @if($pemancar->lokasi)
                <span class="badge bg-secondary" style="font-size:.62rem">{{ $pemancar->lokasi }}</span>
                @endif
            </div>
            <div class="card-body p-0">
                @if($operatorLokasi->isNotEmpty())
                @foreach($operatorLokasi as $op)
                <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom" style="border-color:var(--border)!important">
                    <div style="width:34px;height:34px;min-width:34px;border-radius:50%;
                         background:var(--primary);color:#fff;display:flex;align-items:center;
                         justify-content:center;font-size:.72rem;font-weight:700">
                        {{ strtoupper(substr($op->name,0,2)) }}
                    </div>
                    <div class="flex-fill" style="min-width:0">
                        <div style="font-size:.84rem;font-weight:600">{{ $op->name }}</div>
                        <div class="text-muted" style="font-size:.68rem">
                            {{ $op->nip ? 'NIP. '.$op->nip : 'Operator' }}
                        </div>
                    </div>
                    <span class="badge {{ $op->is_active?'bg-success':'bg-secondary' }}" style="font-size:.6rem">
                        {{ $op->is_active?'Aktif':'Off' }}
                    </span>
                </div>
                @endforeach
                @else
                <div class="text-center text-muted py-4 small">
                    <i class="bi bi-person-x d-block fs-3 mb-1"></i>
                    @if($pemancar->lokasi)
                    Belum ada operator terdaftar<br>di lokasi {{ $pemancar->lokasi }}
                    @else
                    Lokasi pemancar belum diset
                    @endif
                </div>
                @endif
            </div>
            @if($operatorLokasi->isNotEmpty())
            <div class="card-footer py-2 text-center" style="border-color:var(--border)">
                <small class="text-muted">{{ $operatorLokasi->count() }} operator terdaftar</small>
            </div>
            @endif
        </div>

        {{-- Log Terakhir --}}
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-clock-history me-2 text-success"></i>Pencatatan Terakhir</div>
            <div class="card-body">
                @if($logTerakhir)
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted" style="font-size:.75rem">Waktu</span>
                    <span class="mono fw-bold" style="font-size:.78rem">{{ $logTerakhir->dicatat_pada->format('d/m/Y H:i') }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted" style="font-size:.75rem">Operator</span>
                    <span style="font-size:.8rem;font-weight:600">{{ $logTerakhir->user->name }}</span>
                </div>
                @if($logTerakhir->jadwalShift)
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted" style="font-size:.75rem">Shift</span>
                    <span style="font-size:.8rem">{{ $logTerakhir->jadwalShift->shift_label }}</span>
                </div>
                @endif
                @if($logTerakhir->output_final_pa)
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted" style="font-size:.75rem">Output Final</span>
                    <span class="mono" style="font-size:.78rem">{{ number_format($logTerakhir->output_final_pa,1) }} W</span>
                </div>
                @endif
                @if($logTerakhir->vswr_final)
                @php
                    $v=$logTerakhir->vswr_final;
                    $cls=$v<=1.5?'vswr-baik':($v<=2?'vswr-sedang':'vswr-buruk');
                @endphp
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted" style="font-size:.75rem">VSWR Final</span>
                    <span class="mono {{ $cls }}" style="font-size:.78rem">{{ number_format($v,3) }}</span>
                </div>
                @endif
                @if($logTerakhir->keterangan)
                <div class="mt-2 p-2 rounded" style="background:var(--bg);font-size:.75rem;line-height:1.5">
                    {{ $logTerakhir->keterangan }}
                </div>
                @endif
                <a href="{{ route('operasional.show',$logTerakhir) }}" class="btn btn-sm btn-outline-primary w-100 mt-2">
                    <i class="bi bi-eye me-1"></i>Lihat Detail Log
                </a>
                @else
                <div class="text-center text-muted py-3 small">
                    <i class="bi bi-journal-x d-block fs-3 mb-1"></i>
                    Belum ada pencatatan
                </div>
                @endif
            </div>
        </div>

        {{-- Riwayat Pencatatan Sepanjang Waktu --}}
        <div class="card">
            <div class="card-header"><i class="bi bi-archive me-2 text-warning"></i>Riwayat Sepanjang Waktu</div>
            <div class="card-body p-0">
                <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom" style="border-color:var(--border)!important">
                    <span class="text-muted" style="font-size:.78rem">Total Pencatatan</span>
                    <span class="mono fw-bold">{{ number_format($totalLogSemua) }}</span>
                </div>
                @if($logPertama)
                <div class="d-flex align-items-center justify-content-between px-3 py-2">
                    <span class="text-muted" style="font-size:.78rem">Pencatatan Pertama</span>
                    <span class="mono" style="font-size:.75rem">{{ $logPertama->dicatat_pada->format('d/m/Y') }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
