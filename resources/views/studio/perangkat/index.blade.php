@extends('layouts.app')
@section('title','Master Perangkat Studio')
@section('page-title','Master Perangkat Studio')

@section('content')

{{-- Ringkasan --}}
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="card text-center"><div class="card-body py-2">
            <div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Total Perangkat</div>
            <div class="mono fw-bold" style="font-size:1.6rem;color:var(--primary)">{{ $ringkasan['total'] }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center"><div class="card-body py-2">
            <div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Aktif</div>
            <div class="mono fw-bold" style="font-size:1.6rem;color:var(--success)">{{ $ringkasan['aktif'] }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center"><div class="card-body py-2">
            <div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Kondisi Baik</div>
            <div class="mono fw-bold" style="font-size:1.6rem;color:var(--success)">{{ $ringkasan['baik'] }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center"><div class="card-body py-2">
            <div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Perlu Perhatian</div>
            <div class="mono fw-bold" style="font-size:1.6rem;color:{{ $ringkasan['perhatian']>0?'var(--danger)':'var(--success)' }}">{{ $ringkasan['perhatian'] }}</div>
        </div></div>
    </div>
</div>

{{-- Alert Maintenance --}}
@if($perluPerhatian->count())
<div class="alert alert-warning py-2 mb-3">
    <strong><i class="bi bi-exclamation-triangle me-1"></i>Perhatian Maintenance:</strong>
    <ul class="mb-0 mt-1 ps-3">
        @foreach($perluPerhatian as $p)
        <li style="font-size:.82rem">
            <a href="{{ route('studio.perangkat.show',$p) }}">{{ $p->nama }}</a>
            — <span style="color:{{ $p->status_maintenance_color }}">{{ $p->status_maintenance_label }}</span>
            @if($p->jatuh_tempo_maintenance)
            ({{ $p->jatuh_tempo_maintenance->format('d/m/Y') }})
            @endif
        </li>
        @endforeach
    </ul>
</div>
@endif

{{-- Filter --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label mb-1" style="font-size:.7rem">Kategori</label>
                <select name="kategori" class="form-select form-select-sm">
                    <option value="">Semua Kategori</option>
                    @foreach(\App\Models\StudioPerangkat::KATEGORI_LABEL as $val=>$label)
                    <option value="{{ $val }}" {{ request('kategori')==$val?'selected':'' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1" style="font-size:.7rem">Lokasi</label>
                <select name="lokasi" class="form-select form-select-sm">
                    <option value="">Semua Lokasi</option>
                    <option value="Pahoman" {{ request('lokasi')==='Pahoman'?'selected':'' }}>Studio Pahoman</option>
                    <option value="Way Kanan" {{ request('lokasi')==='Way Kanan'?'selected':'' }}>Studio Way Kanan</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1" style="font-size:.7rem">Kondisi</label>
                <select name="kondisi" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach(\App\Models\StudioPerangkat::KONDISI_LABEL as $val=>$label)
                    <option value="{{ $val }}" {{ request('kondisi')==$val?'selected':'' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1" style="font-size:.7rem">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach(\App\Models\StudioPerangkat::STATUS_LABEL as $val=>$label)
                    <option value="{{ $val }}" {{ request('status')==$val?'selected':'' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-5 d-flex gap-2">
                <button class="btn btn-sm btn-primary-custom flex-fill"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('studio.perangkat.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

{{-- Tombol Aksi --}}
<div class="d-flex justify-content-between align-items-center mb-2 gap-2 flex-wrap">
    <a href="{{ route('studio.perangkat.cetak-inventaris') }}" class="btn btn-sm btn-outline-secondary" target="_blank">
        <i class="bi bi-file-earmark-pdf me-1"></i>Cetak Inventaris
    </a>
    @if(auth()->user()->isAdmin() || (auth()->user()->isAdminDivisi() && auth()->user()->isDivisi('studio')))
    <a href="{{ route('studio.perangkat.create') }}" class="btn btn-primary-custom">
        <i class="bi bi-plus-circle me-1"></i>Tambah Perangkat
    </a>
    @endif
</div>

{{-- Tabel --}}
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Perangkat</th>
                        <th>Lokasi</th>
                        <th>Kategori</th>
                        <th>Merk / Tipe</th>
                        <th>Kondisi</th>
                        <th>Status</th>
                        <th>Maintenance</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($perangkats as $p)
                    <tr>
                        <td class="ps-3">
                            <div class="fw-semibold" style="font-size:.82rem">{{ $p->nama }}</div>
                            @if($p->kode_inventaris)<div class="mono text-muted" style="font-size:.68rem">{{ $p->kode_inventaris }}</div>@endif
                        </td>
                        <td>
                            @if($p->lokasi)
                            <span class="badge" style="font-size:.65rem;background:{{ $p->lokasi=='Way Kanan'?'#8e44ad':'#0a3d62' }}">
                                {{ $p->lokasi }}
                            </span>
                            @else<span class="text-muted" style="font-size:.75rem">-</span>@endif
                        </td>
                        <td style="font-size:.8rem">{{ \App\Models\StudioPerangkat::KATEGORI_LABEL[$p->kategori] ?? ($p->kategori ?? '-') }}</td>
                        <td style="font-size:.8rem">
                            {{ $p->merk ?? '-' }}
                            @if($p->tipe)<div class="text-muted" style="font-size:.7rem">{{ $p->tipe }}</div>@endif
                        </td>
                        <td>
                            <span class="badge" style="background:{{ $p::KONDISI_COLOR[$p->kondisi] ?? '#888' }};font-size:.68rem">{{ $p->kondisi_label }}</span>
                        </td>
                        <td style="font-size:.8rem">{{ \App\Models\StudioPerangkat::STATUS_LABEL[$p->status] ?? $p->status }}</td>
                        <td style="font-size:.8rem">
                            <span style="color:{{ $p->status_maintenance_color }}">{{ $p->status_maintenance_label }}</span>
                            @if($p->maintenance_terakhir)<div class="text-muted" style="font-size:.68rem">Terakhir: {{ $p->maintenance_terakhir->format('d/m/Y') }}</div>@endif
                        </td>
                        <td class="text-end pe-3">
                            <a href="{{ route('studio.perangkat.show',$p) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                            @if(auth()->user()->isAdmin() || (auth()->user()->isAdminDivisi() && auth()->user()->isDivisi('studio')))
                            <a href="{{ route('studio.perangkat.edit',$p) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-speaker d-block fs-2 mb-1"></i>Belum ada data perangkat studio
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($perangkats->hasPages())
        <div class="px-3 py-2 border-top" style="border-color:var(--border)!important">{{ $perangkats->links() }}</div>
        @endif
    </div>
</div>
@endsection