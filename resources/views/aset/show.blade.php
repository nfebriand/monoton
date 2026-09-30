@extends('layouts.app')
@section('title', $aset->nama)
@section('page-title','Detail Aset')

@section('content')
<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    <a href="{{ route('aset.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
    <span class="fw-bold text-truncate">{{ $aset->nama }}</span>
    <span class="badge bg-secondary mono">{{ $aset->kode_aset }}</span>
    <div class="d-flex gap-2 ms-auto">
        <a href="{{ route('maintenance.create') }}?aset_id={{ $aset->id }}" class="btn btn-sm btn-success">
            <i class="bi bi-tools me-1"></i>Catat Maintenance
        </a>
        <a href="{{ route('aset.edit',$aset) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil me-1"></i>Edit</a>
        @if(auth()->user()->isAdmin())
        <form action="{{ route('aset.destroy',$aset) }}" method="POST" onsubmit="return confirm('Hapus (arsipkan) aset ini?')">
            @csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Hapus</button>
        </form>
        @endif
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-box-seam me-2 text-primary"></i>Informasi Aset</span>
                <span class="badge" style="background:{{ \App\Models\Aset::KONDISI_COLOR[$aset->kondisi] }}">{{ $aset->kondisi_label }}</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="text-muted" style="font-size:.7rem">Kategori</div>
                        <div class="fw-bold">{{ $aset->kategori->nama ?? '–' }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted" style="font-size:.7rem">Lokasi</div>
                        <div class="fw-bold">{{ $aset->lokasi ?? '–' }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted" style="font-size:.7rem">Merk</div>
                        <div class="fw-bold">{{ $aset->merk ?? '–' }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted" style="font-size:.7rem">Tipe/Model</div>
                        <div class="fw-bold">{{ $aset->tipe_model ?? '–' }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted" style="font-size:.7rem">No. Seri</div>
                        <div class="fw-bold mono">{{ $aset->no_seri ?? '–' }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted" style="font-size:.7rem">Tgl Perolehan</div>
                        <div class="fw-bold mono">{{ $aset->tanggal_perolehan?->format('d/m/Y') ?? '–' }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted" style="font-size:.7rem">Harga Perolehan</div>
                        <div class="fw-bold mono">{{ $aset->harga_perolehan ? 'Rp '.number_format($aset->harga_perolehan,0,',','.') : '–' }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted" style="font-size:.7rem">Status</div>
                        <div class="fw-bold">
                            <span class="badge {{ $aset->status=='aktif'?'bg-success':'bg-secondary' }}">{{ ucfirst($aset->status) }}</span>
                        </div>
                    </div>
                </div>

                @if($aset->keterangan)
                <div class="mt-3 p-3 rounded" style="background:var(--bg);border:1px solid var(--border);border-left:4px solid var(--primary);font-size:.85rem;line-height:1.6;white-space:pre-line">
                    {{ $aset->keterangan }}
                </div>
                @endif
            </div>
        </div>

        @if($aset->fotos->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-images me-2"></i>Foto Aset</div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:.65rem">
                    @foreach($aset->fotos as $foto)
                    <div style="aspect-ratio:1;border-radius:8px;overflow:hidden;border:2px solid var(--border)">
                        <img src="{{ $foto->thumb_url }}" onclick="bukaLightbox('{{ $foto->url }}')"
                             style="width:100%;height:100%;object-fit:cover;cursor:zoom-in" onerror="this.parentElement.style.background='var(--bg)'">
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Riwayat Maintenance --}}
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-tools me-2 text-success"></i>Riwayat Maintenance</span>
                <span class="badge bg-secondary">{{ $aset->maintenanceLogs->count() }} log</span>
            </div>
            <div class="card-body p-0">
                @forelse($aset->maintenanceLogs as $log)
                <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom flex-wrap" style="border-color:var(--border)!important;cursor:pointer"
                     onclick="window.location='{{ route('maintenance.show',$log) }}'">
                    <div style="min-width:60px;text-align:center">
                        <div class="mono fw-bold" style="font-size:.78rem">{{ $log->tanggal->format('d/m/y') }}</div>
                    </div>
                    <span class="badge" style="background:{{ ['preventif'=>'#10ac84','korektif'=>'#ee5a24','inspeksi'=>'#0a3d62'][$log->jenis] }}">{{ $log->jenis_label }}</span>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:.8rem">{{ \Illuminate\Support\Str::limit($log->uraian_pekerjaan,60) }}</div>
                        <div class="text-muted" style="font-size:.68rem">{{ $log->user->name }}</div>
                    </div>
                    <span class="badge {{ $log->hasil=='selesai'?'bg-success':($log->hasil=='sebagian'?'bg-warning text-dark':'bg-secondary') }}" style="font-size:.65rem">
                        {{ $log->hasil_label }}
                    </span>
                </div>
                @empty
                <div class="text-center text-muted py-4 small"><i class="bi bi-tools d-block fs-2 mb-1"></i>Belum ada riwayat maintenance</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-calendar-check me-2 text-warning"></i>Status Maintenance</div>
            <div class="card-body">
                <div class="text-center p-3 rounded mb-3" style="background:{{ $aset->status_maintenance_color }}15;border:1px solid {{ $aset->status_maintenance_color }}40">
                    <div class="fw-bold" style="font-size:1.1rem;color:{{ $aset->status_maintenance_color }}">{{ $aset->status_maintenance_label }}</div>
                </div>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted" style="font-size:.75rem">Maintenance Terakhir</span>
                    <span class="mono fw-bold" style="font-size:.78rem">{{ $aset->maintenance_terakhir?->format('d/m/Y') ?? '–' }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted" style="font-size:.75rem">Interval</span>
                    <span class="mono" style="font-size:.78rem">{{ $aset->interval_efektif ? $aset->interval_efektif.' hari' : '–' }}</span>
                </div>
                @if($aset->jatuh_tempo_maintenance)
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted" style="font-size:.75rem">Estimasi Jatuh Tempo</span>
                    <span class="mono fw-bold" style="font-size:.78rem;color:{{ $aset->status_maintenance_color }}">{{ $aset->jatuh_tempo_maintenance->format('d/m/Y') }}</span>
                </div>
                @endif

                <a href="{{ route('maintenance.create') }}?aset_id={{ $aset->id }}" class="btn btn-success w-100 mt-2">
                    <i class="bi bi-tools me-1"></i>Catat Maintenance Sekarang
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
