@extends('layouts.app')
@section('title','Logbook Sarana')
@section('page-title','Logbook Harian Sarana & Prasarana')
@section('content')
@php $tampil = $semua ? 'semua' : null; @endphp
<div class="card mb-3"><div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-6 col-md-2">
            <label class="form-label mb-1">Bulan</label>
            <input type="month" name="bulan" class="form-control form-control-sm" value="{{ $bulan }}">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label mb-1">Tampilkan</label>
            <select name="tampil" class="form-select form-select-sm">
                <option value="" {{ !$semua ? 'selected' : '' }}>Milik saya</option>
                <option value="semua" {{ $semua ? 'selected' : '' }}>Semua petugas</option>
            </select>
        </div>
        @if($semua)
        <div class="col-6 col-md-2">
            <label class="form-label mb-1">Petugas</label>
            <select name="user_id" class="form-select form-select-sm">
                <option value="">Semua</option>
                @foreach($petugas as $p)<option value="{{ $p->id }}" {{ (string) request('user_id') === (string) $p->id ? 'selected' : '' }}>{{ $p->name }}</option>@endforeach
            </select>
        </div>
        @endif
        <div class="col-6 col-md-2">
            <label class="form-label mb-1">Shift</label>
            <select name="shift" class="form-select form-select-sm">
                <option value="">Semua</option>
                @foreach(\App\Models\SaranaLog::shiftList() as $no => $s)<option value="{{ $no }}" {{ (string) request('shift') === (string) $no ? 'selected' : '' }}>{{ $s['label'] }}</option>@endforeach
            </select>
        </div>
        <div class="col-6 col-md-auto">
            <label class="form-check mb-1"><input type="checkbox" name="kendala" value="1" class="form-check-input" {{ request('kendala') === '1' ? 'checked' : '' }}><span class="form-check-label" style="font-size:.78rem">Hanya ada kendala</span></label>
        </div>
        <div class="col-12 col-md d-flex gap-2 flex-wrap">
            <button class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Filter</button>
            <a href="{{ route('sarana.logbook.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            <a href="{{ route('sarana.logbook.cetak-bulanan', ['bulan' => $bulan, 'tampil' => $tampil]) }}" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i>PDF Bulan Ini</a>
            <a href="{{ route('sarana.logbook.create') }}" class="btn btn-primary-custom btn-sm"><i class="bi bi-plus-lg me-1"></i>Isi Logbook</a>
        </div>
    </form>
</div></div>

@if($errors->any())<div class="alert alert-danger py-2">@foreach($errors->all() as $e)<div style="font-size:.82rem">{{ $e }}</div>@endforeach</div>@endif
<div class="mb-2" style="font-size:.8rem;color:var(--muted)">Menampilkan <strong>{{ $logs->total() }}</strong> logbook {{ $semua ? 'semua petugas' : 'milik Anda' }}</div>

@forelse($logs as $l)
<div class="card mb-2" style="{{ $l->ada_kendala ? 'border-left:4px solid #e74c3c' : '' }}">
    <div class="card-body py-2 d-flex flex-wrap align-items-center gap-2">
        <div class="flex-grow-1" style="min-width:0">
            <a href="{{ route('sarana.logbook.show', $l) }}" class="fw-bold text-decoration-none" style="font-size:.9rem">{{ $l->tanggal->translatedFormat('l, d F Y') }} &mdash; {{ $l->shift_label }}</a>
            <div class="text-muted" style="font-size:.72rem">
                {{ substr($l->jam_mulai,0,5) }}{{ $l->jam_selesai ? ' - '.substr($l->jam_selesai,0,5) : '' }}
                &bull; <i class="bi bi-person"></i> {{ $l->user->name ?? '-' }}
                @if($l->fotos_count) &bull; <i class="bi bi-images"></i> {{ $l->fotos_count }} @endif
                &nbsp;{!! $l->ada_kendala ? '<span class="badge bg-danger">Ada Kendala</span>' : '<span class="badge bg-success">Normal</span>' !!}
            </div>
            @if($l->catatan_petugas)<div style="font-size:.78rem;color:#666">{{ \Illuminate\Support\Str::limit($l->catatan_petugas, 120) }}</div>@endif
        </div>
        <a href="{{ route('sarana.logbook.show', $l) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
        @if(\App\Support\Akses::saranaLogUbah(auth()->user(), $l->user_id))
        <a href="{{ route('sarana.logbook.edit', $l) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
        <form action="{{ route('sarana.logbook.destroy', $l) }}" method="POST" onsubmit="return confirm('Hapus logbook ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
        @endif
    </div>
</div>
@empty
<div class="card"><div class="card-body text-center text-muted py-5">Belum ada logbook pada filter ini.</div></div>
@endforelse
<div class="mt-3">{{ $logs->links() }}</div>
@endsection
