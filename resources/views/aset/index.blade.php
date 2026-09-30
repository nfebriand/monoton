@extends('layouts.app')
@section('title','Aset & Inventaris')
@section('page-title','Aset & Inventaris Sarana')

@section('content')

<div class="row g-2 mb-3">
    <div class="col-4">
        <div class="card text-center"><div class="card-body py-2">
            <div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Total Aset Aktif</div>
            <div class="mono fw-bold" style="font-size:1.6rem;color:var(--primary)">{{ $ringkasan['total'] }}</div>
        </div></div>
    </div>
    <div class="col-4">
        <div class="card text-center"><div class="card-body py-2">
            <div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Perlu Maintenance</div>
            <div class="mono fw-bold" style="font-size:1.6rem;color:{{ $ringkasan['jatuh_tempo']>0?'var(--danger)':'var(--success)' }}">{{ $ringkasan['jatuh_tempo'] }}</div>
        </div></div>
    </div>
    <div class="col-4">
        <div class="card text-center"><div class="card-body py-2">
            <div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Rusak</div>
            <div class="mono fw-bold" style="font-size:1.6rem;color:{{ $ringkasan['rusak']>0?'var(--warning)':'var(--success)' }}">{{ $ringkasan['rusak'] }}</div>
        </div></div>
    </div>
</div>

@if($ringkasan['jatuh_tempo'] > 0)
<div class="alert alert-warning mb-3 py-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <span><i class="bi bi-exclamation-triangle-fill me-2"></i><strong>{{ $ringkasan['jatuh_tempo'] }} aset</strong> memerlukan maintenance segera.</span>
    <a href="{{ route('maintenance.index') }}" class="btn btn-sm btn-warning">Lihat Detail</a>
</div>
@endif

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label mb-1" style="font-size:.7rem">Cari</label>
                <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Nama / kode / merk">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label mb-1" style="font-size:.7rem">Kategori</label>
                <select name="kategori_id" class="form-select form-select-sm">
                    <option value="">Semua Kategori</option>
                    @foreach($kategoris as $k)
                    <option value="{{ $k->id }}" {{ request('kategori_id')==$k->id?'selected':'' }}>{{ $k->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label mb-1" style="font-size:.7rem">Lokasi</label>
                <select name="lokasi" class="form-select form-select-sm">
                    <option value="">Semua Lokasi</option>
                    @foreach($lokasiList as $l)
                    <option value="{{ $l->nama }}" {{ request('lokasi')===$l->nama?'selected':'' }}>{{ $l->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 d-flex gap-1">
                <button class="btn btn-sm btn-primary-custom flex-fill"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('aset.index') }}" class="btn btn-sm btn-outline-secondary">✕</a>
            </div>
        </form>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mb-2">
    @if(auth()->user()->isAdmin())
    <a href="{{ route('aset.kategori.index') }}" class="btn btn-outline-secondary"><i class="bi bi-tags me-1"></i>Kategori</a>
    @endif
    <a href="{{ route('aset.create') }}" class="btn btn-primary-custom"><i class="bi bi-plus-circle me-1"></i>Tambah Aset</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Kode</th><th>Nama Aset</th><th>Kategori</th>
                        <th>Lokasi</th><th>Kondisi</th><th>Status Maintenance</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($asets as $a)
                    <tr style="cursor:pointer" onclick="window.location='{{ route('aset.show',$a) }}'">
                        <td class="ps-3 mono fw-bold" style="font-size:.78rem">{{ $a->kode_aset }}</td>
                        <td>
                            <div style="font-size:.84rem;font-weight:600">{{ $a->nama }}</div>
                            @if($a->merk)<div class="text-muted" style="font-size:.68rem">{{ $a->merk }} {{ $a->tipe_model }}</div>@endif
                        </td>
                        <td style="font-size:.78rem">{{ $a->kategori->nama ?? '–' }}</td>
                        <td style="font-size:.78rem">{{ $a->lokasi ?? '–' }}</td>
                        <td><span class="badge" style="background:{{ \App\Models\Aset::KONDISI_COLOR[$a->kondisi] }}">{{ $a->kondisi_label }}</span></td>
                        <td><span class="badge" style="background:{{ $a->status_maintenance_color }}">{{ $a->status_maintenance_label }}</span></td>
                        <td class="text-end pe-3" onclick="event.stopPropagation()">
                            <a href="{{ route('aset.show',$a) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-box-seam d-block fs-2 mb-1"></i>Belum ada data aset
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($asets->hasPages())
        <div class="px-3 py-2 border-top" style="border-color:var(--border)!important">{{ $asets->links() }}</div>
        @endif
    </div>
</div>
@endsection
