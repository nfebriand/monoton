@extends('layouts.app')
@section('title','Riwayat Log')
@section('page-title','Riwayat Log Operasional')

@section('content')

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            {{-- Lokasi dulu --}}
            <div class="col-12 col-sm-6 col-md-2">
                <label class="form-label mb-1">Lokasi Pemancar</label>
                <select name="lokasi" class="form-select form-select-sm" id="selLokasi">
                    <option value="">Semua Lokasi</option>
                    @foreach($semuaLokasi as $lok)
                    <option value="{{ $lok }}" {{ request('lokasi')===$lok?'selected':'' }}>{{ $lok }}</option>
                    @endforeach
                </select>
            </div>
            {{-- Pemancar setelah lokasi --}}
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label mb-1">Pemancar</label>
                <select name="pemancar_id" class="form-select form-select-sm" id="selPemancar">
                    <option value="">Semua Pemancar</option>
                    @foreach($pemancars as $p)
                    <option value="{{ $p->id }}" data-lokasi="{{ $p->lokasi }}"
                        {{ request('pemancar_id')==$p->id?'selected':'' }}>
                        {{ $p->nama_stasiun }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <label class="form-label mb-1">Dari</label>
                <input type="date" name="tanggal_dari" class="form-control form-control-sm"
                       value="{{ request('tanggal_dari') }}">
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <label class="form-label mb-1">Sampai</label>
                <input type="date" name="tanggal_sampai" class="form-control form-control-sm"
                       value="{{ request('tanggal_sampai') }}">
            </div>
            <div class="col-12 col-md-3 d-flex gap-2 flex-wrap align-items-end">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="{{ route('operasional.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                <a href="{{ route('operasional.create') }}" class="btn btn-success btn-sm ms-auto">
                    <i class="bi bi-plus-lg me-1"></i>Catat
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Desktop table --}}
<div class="card d-none d-md-block">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-journal-text me-1"></i>Log Operasional</span>
        <span class="badge bg-secondary">{{ $logs->total() }} record</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th class="ps-3">Waktu</th>
                    <th>Pemancar</th>
                    <th>Lokasi</th>
                    <th>Operator</th>
                    <th class="text-end">Out Final</th>
                    <th class="text-center">VSWR</th>
                    <th class="text-end">Suhu Rmg</th>
                    <th class="text-end">RH%</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td class="ps-3">
                        <div class="mono" style="font-size:.8rem">{{ $log->dicatat_pada->format('d/m/Y') }}</div>
                        <div class="mono text-muted" style="font-size:.72rem">{{ $log->dicatat_pada->format('H:i') }}</div>
                    </td>
                    <td>
                        <div style="font-size:.83rem;font-weight:600">{{ $log->pemancar->nama_stasiun }}</div>
                        <small class="text-muted">{{ $log->pemancar->modulasi }}{{ $log->pemancar->frekuensi?' '.$log->pemancar->frekuensi.' MHz':'' }}</small>
                    </td>
                    <td>
                        @if($log->pemancar->lokasi)
                        <span class="badge bg-secondary" style="font-size:.65rem">{{ $log->pemancar->lokasi }}</span>
                        @else <span class="text-muted">–</span> @endif
                    </td>
                    <td style="font-size:.83rem">{{ $log->user->name }}</td>
                    <td class="text-end mono" style="font-size:.8rem">
                        {{ $log->output_final_pa ? number_format($log->output_final_pa,1).' W':'–' }}
                    </td>
                    <td class="text-center">
                        @if($log->vswr_final)
                            @php $v=$log->vswr_final;$cls=$v<=1.5?'vswr-baik':($v<=2?'vswr-sedang':'vswr-buruk'); @endphp
                            <span class="mono {{ $cls }}" style="font-size:.8rem">{{ number_format($v,3) }}</span>
                        @else <span class="text-muted">–</span> @endif
                    </td>
                    <td class="text-end mono" style="font-size:.8rem">
                        {{ $log->suhu_ruangan!==null?$log->suhu_ruangan.'°C':'–' }}
                    </td>
                    <td class="text-end mono" style="font-size:.8rem">
                        {{ $log->kelembaban!==null?$log->kelembaban.'%':'–' }}
                    </td>
                    <td class="pe-2">
                        <div class="d-flex gap-1">
                            <a href="{{ route('operasional.show',$log) }}"
                               class="btn btn-sm btn-outline-primary" style="padding:.12rem .38rem">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('operasional.edit',$log) }}"
                               class="btn btn-sm btn-outline-warning" style="padding:.12rem .38rem">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @if(auth()->user()->isAdmin())
                            <form action="{{ route('operasional.destroy',$log) }}" method="POST"
                                  onsubmit="return confirm('Hapus log ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" style="padding:.12rem .38rem">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-muted py-5">
                    <i class="bi bi-journal-x d-block fs-2 mb-2"></i>Belum ada data
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
    <div class="card-footer d-flex align-items-center justify-content-between">
        <small class="text-muted">{{ $logs->firstItem() }}–{{ $logs->lastItem() }} dari {{ $logs->total() }}</small>
        {{ $logs->links() }}
    </div>
    @endif
</div>

{{-- Mobile card list --}}
<div class="d-md-none">
    <div class="mb-2 px-1 d-flex justify-content-between align-items-center">
        <span class="text-muted small">{{ $logs->total() }} record</span>
    </div>
    @forelse($logs as $log)
    <div class="card mb-2">
        <div class="card-body py-2 px-3">
            <div class="d-flex align-items-start gap-2">
                <div class="flex-fill" style="min-width:0">
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        <span class="fw-bold text-truncate" style="font-size:.85rem">{{ $log->pemancar->nama_stasiun }}</span>
                        @if($log->pemancar->lokasi)
                        <span class="badge bg-secondary" style="font-size:.62rem">{{ $log->pemancar->lokasi }}</span>
                        @endif
                        @if($log->vswr_final)
                            @php $v=$log->vswr_final;$cls=$v<=1.5?'vswr-baik':($v<=2?'vswr-sedang':'vswr-buruk'); @endphp
                            <span class="mono {{ $cls }}" style="font-size:.75rem">VSWR {{ number_format($v,3) }}</span>
                        @endif
                    </div>
                    <div class="d-flex flex-wrap gap-2" style="font-size:.73rem;color:#636e72">
                        <span><i class="bi bi-clock me-1"></i>{{ $log->dicatat_pada->format('d/m H:i') }}</span>
                        <span><i class="bi bi-person me-1"></i>{{ $log->user->name }}</span>
                        @if($log->output_final_pa)<span><i class="bi bi-lightning me-1"></i>{{ number_format($log->output_final_pa,1) }} W</span>@endif
                        @if($log->suhu_ruangan!==null)<span>🌡 {{ $log->suhu_ruangan }}°C</span>@endif
                        @if($log->kelembaban!==null)<span>💧 {{ $log->kelembaban }}%</span>@endif
                    </div>
                </div>
                <div class="d-flex flex-column gap-1 flex-shrink-0">
                    <a href="{{ route('operasional.show',$log) }}"
                       class="btn btn-sm btn-outline-primary" style="font-size:.7rem;padding:.15rem .42rem">
                        <i class="bi bi-eye"></i>
                    </a>
                    <a href="{{ route('operasional.edit',$log) }}"
                       class="btn btn-sm btn-outline-warning" style="font-size:.7rem;padding:.15rem .42rem">
                        <i class="bi bi-pencil"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="text-center text-muted py-5">
        <i class="bi bi-journal-x d-block fs-2 mb-2"></i>Belum ada data
    </div>
    @endforelse
    @if($logs->hasPages())
    <div class="mt-2">{{ $logs->links() }}</div>
    @endif
</div>

@endsection

@push('scripts')
<script>
// Filter pemancar berdasarkan lokasi yang dipilih
document.getElementById('selLokasi').addEventListener('change',function(){
    const lok=this.value;
    const selP=document.getElementById('selPemancar');
    selP.value='';
    Array.from(selP.options).forEach(opt=>{
        if(!opt.value){return;}
        opt.style.display=(!lok||opt.dataset.lokasi===lok)?'':'none';
    });
});
// Init: sembunyikan pemancar yang tidak sesuai lokasi terpilih saat load
(function(){
    const lok=document.getElementById('selLokasi').value;
    if(!lok)return;
    Array.from(document.getElementById('selPemancar').options).forEach(opt=>{
        if(!opt.value)return;
        opt.style.display=opt.dataset.lokasi===lok?'':'none';
    });
})();
</script>
@endpush
