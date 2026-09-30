@extends('layouts.app')
@section('title','Rekap Eviden')
@section('page-title','Rekap Laporan Eviden')

@section('content')
@php $user = auth()->user(); @endphp

{{-- Ringkasan per divisi --}}
<div class="row g-2 mb-3">
    @foreach($ringkasan as $kode => $r)
    <div class="col-12 col-md-4">
        <div class="card text-center">
            <div class="card-body py-2">
                <div class="badge mb-1"
                     style="background:{{ ['transmisi'=>'#0a3d62','studio'=>'#7b1fa2','sarana'=>'#10ac84'][$kode] ?? '#888' }}">
                    {{ $r['label'] }}
                </div>
                <div class="d-flex justify-content-center gap-4 mt-1">
                    <div>
                        <div style="font-size:.6rem;color:var(--muted)">BULAN INI</div>
                        <div class="mono fw-bold" style="font-size:1.2rem;color:var(--primary)">{{ $r['bulan_ini'] }}</div>
                    </div>
                    <div>
                        <div style="font-size:.6rem;color:var(--muted)">TOTAL</div>
                        <div class="mono fw-bold" style="font-size:1.2rem">{{ $r['total'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Filter --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            @if($user->isAdmin())
            <div class="col-6 col-md-2">
                <label class="form-label mb-1" style="font-size:.7rem">Divisi</label>
                <select name="divisi" class="form-select form-select-sm">
                    <option value="">Semua Divisi</option>
                    @foreach(\App\Models\User::DIVISI_LABEL as $val => $label)
                    <option value="{{ $val }}" {{ request('divisi')===$val?'selected':'' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-6 col-md-2">
                <label class="form-label mb-1" style="font-size:.7rem">Dari Tanggal</label>
                <input type="date" name="tanggal_dari" class="form-control form-control-sm" value="{{ request('tanggal_dari') }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1" style="font-size:.7rem">Sampai</label>
                <input type="date" name="tanggal_sampai" class="form-control form-control-sm" value="{{ request('tanggal_sampai') }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1" style="font-size:.7rem">Lokasi</label>
                <select name="lokasi" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach($lokasiList as $l)
                    <option value="{{ $l }}" {{ request('lokasi')===$l?'selected':'' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1" style="font-size:.7rem">Operator</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach($operators as $op)
                    <option value="{{ $op->id }}" {{ request('user_id')==$op->id?'selected':'' }}>{{ $op->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2 d-flex gap-1">
                <button class="btn btn-sm btn-primary-custom flex-fill"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('laporan.eviden-rekap') }}" class="btn btn-sm btn-outline-secondary">✕</a>
            </div>
        </form>
    </div>
</div>

{{-- Tombol Cetak --}}
<div class="d-flex justify-content-end mb-2">
    <a href="{{ route('laporan.eviden-rekap-pdf', request()->query()) }}"
       class="btn btn-danger" target="_blank">
        <i class="bi bi-file-earmark-pdf me-1"></i>Cetak Rekap PDF
    </a>
</div>

{{-- Tabel --}}
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Tanggal</th><th>Judul Kegiatan</th>
                        <th>Divisi</th><th>Lokasi</th>
                        <th>Operator</th><th>Foto</th><th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($evidens as $ev)
                    <tr>
                        <td class="ps-3">
                            <div class="mono fw-bold" style="font-size:.8rem">{{ $ev->tanggal->format('d/m/Y') }}</div>
                            <div class="text-muted" style="font-size:.7rem">{{ substr($ev->jam_mulai,0,5) }} – {{ substr($ev->jam_selesai,0,5) }}</div>
                        </td>
                        <td style="font-size:.83rem">{{ $ev->judul }}</td>
                        <td>
                            <span class="badge" style="background:{{ ['transmisi'=>'#0a3d62','studio'=>'#7b1fa2','sarana'=>'#10ac84'][$ev->divisi] ?? '#888' }};font-size:.65rem">
                                {{ \App\Models\User::DIVISI_LABEL[$ev->divisi] ?? $ev->divisi }}
                            </span>
                        </td>
                        <td style="font-size:.78rem">{{ $ev->lokasi ?? '–' }}</td>
                        <td style="font-size:.78rem">{{ $ev->user->name }}</td>
                        <td class="mono" style="font-size:.78rem">{{ $ev->fotos->count() }}</td>
                        <td class="text-end pe-3">
                            <a href="{{ route('eviden.show',$ev) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('eviden.cetak',$ev) }}" class="btn btn-sm btn-outline-danger" target="_blank"><i class="bi bi-file-pdf"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data eviden</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($evidens->hasPages())
        <div class="px-3 py-2 border-top">{{ $evidens->links() }}</div>
        @endif
    </div>
</div>
@endsection
