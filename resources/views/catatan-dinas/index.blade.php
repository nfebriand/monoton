@extends('layouts.app')
@section('title','Catatan Dinas')
@section('page-title','Catatan Dinas')

@section('content')
@php $u = auth()->user(); $kelola = $u->isAdmin() || $u->isAdminDivisi(); @endphp
@if(!$kelola && $u->lokasi_dinas)
<div class="alert alert-info py-2" style="font-size:.8rem"><i class="bi bi-people me-1"></i>Menampilkan catatan Anda dan catatan operator lain di lokasi dinas <strong>{{ $u->lokasi_dinas }}</strong> (untuk serah terima shift).</div>
@endif

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label mb-1">Dari</label>
                <input type="date" name="tanggal_dari" class="form-control form-control-sm" value="{{ request('tanggal_dari') }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1">Sampai</label>
                <input type="date" name="tanggal_sampai" class="form-control form-control-sm" value="{{ request('tanggal_sampai') }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1">Jenis</label>
                <select name="kategori" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach(\App\Models\CatatanDinas::KATEGORI as $k => $l)
                    <option value="{{ $k }}" {{ request('kategori') === $k ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            @if($kelola)
            <div class="col-6 col-md-2">
                <label class="form-label mb-1">Operator</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach($operators as $op)
                    <option value="{{ $op->id }}" {{ (string) request('user_id') === (string) $op->id ? 'selected' : '' }}>{{ $op->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-12 col-md">
                <label class="form-label mb-1">Cari kata</label>
                <input type="text" name="q" class="form-control form-control-sm" placeholder="judul, isi narasi, lokasi" value="{{ request('q') }}">
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <button class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Filter</button>
                <a href="{{ route('catatan-dinas.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                <a href="{{ route('catatan-dinas.create') }}" class="btn btn-primary-custom btn-sm"><i class="bi bi-plus-lg me-1"></i>Tulis Catatan</a>
            </div>
        </form>
    </div>
</div>

<div class="mb-2" style="font-size:.8rem;color:var(--muted)">Menampilkan <strong>{{ $catatan->total() }}</strong> catatan</div>

@forelse($catatan as $c)
<div class="card mb-2">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap align-items-start gap-2">
            <div class="flex-grow-1" style="min-width:0">
                <div class="mb-1">
                    <span class="badge" style="background:{{ $c->kategori_warna }}">{{ $c->kategori_label }}</span>
                    @if($c->fotos_count)<span class="badge bg-light text-dark border"><i class="bi bi-images me-1"></i>{{ $c->fotos_count }}</span>@endif
                </div>
                <a href="{{ route('catatan-dinas.show', $c) }}" class="fw-bold text-decoration-none" style="font-size:.95rem">{{ $c->judul }}</a>
                <div class="text-muted mb-1" style="font-size:.72rem">
                    <i class="bi bi-calendar3 me-1"></i>{{ $c->tanggal->format('d/m/Y') }}
                    @if($c->rentang_jam) &nbsp;<i class="bi bi-clock me-1"></i>{{ $c->rentang_jam }} @endif
                    @if($c->lokasi) &nbsp;<i class="bi bi-geo-alt me-1"></i>{{ $c->lokasi }} @endif
                    &nbsp;<i class="bi bi-person me-1"></i>{{ $c->user->name ?? '-' }}
                </div>
                <div style="font-size:.82rem;color:#555;white-space:pre-line;word-break:break-word">{{ \Illuminate\Support\Str::limit($c->narasi, 260) }}</div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('catatan-dinas.show', $c) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                @if($c->dapatDiubah($u))
                <a href="{{ route('catatan-dinas.edit', $c) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                @endif
            </div>
        </div>
    </div>
</div>
@empty
<div class="card"><div class="card-body text-center text-muted py-5">
    <i class="bi bi-journal-richtext" style="font-size:2rem"></i>
    <div class="mt-2">Belum ada catatan dinas.</div>
    <a href="{{ route('catatan-dinas.create') }}" class="btn btn-primary-custom btn-sm mt-3">Tulis Catatan Pertama</a>
</div></div>
@endforelse

<div class="mt-3">{{ $catatan->links() }}</div>
@endsection
