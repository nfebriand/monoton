@extends('layouts.app')
@section('title','Perawatan Rutin')
@section('page-title','Detail Perawatan Rutin')

@section('content')
@php
    $label = \App\Support\PerawatanHitung::LABEL;
    $warna = \App\Support\PerawatanHitung::WARNA;
    $fmt = fn($v) => $v === null ? '-' : rtrim(rtrim(number_format($v,1,'.',''),'0'),'.');
@endphp
<div class="row g-3">
    <div class="col-12 col-lg-5">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi {{ ['pemancar' => 'bi-broadcast-pin', 'grounding' => 'bi-lightning-charge'][$item->objek_tipe] ?? 'bi-battery-charging' }} text-primary"></i>
                <strong style="font-size:.9rem">{{ $item->nama_objek }}</strong>
                @if($item->objek_tipe === 'grounding' && $item->groundingTitik)<a href="{{ route('grounding.titik.show', $item->groundingTitik) }}" class="small ms-1">buka titik</a>@endif
                <span class="badge ms-auto" style="background:{{ $warna[$hasil['status']] }}">{{ $label[$hasil['status']] }}</span>
            </div>
            <div class="card-body" style="font-size:.85rem">
                <div class="fw-bold mb-2">{{ $item->nama_tugas }}</div>
                <table class="table table-sm mb-2">
                    <tr><td class="text-muted" style="width:42%">Lokasi</td><td>{{ $item->lokasi_objek ?: '-' }}</td></tr>
                    <tr><td class="text-muted">Basis</td><td>{{ \App\Models\PerawatanRutin::BASIS_LABEL[$item->basis] ?? $item->basis }}</td></tr>
                    <tr><td class="text-muted">Interval</td><td class="mono">
                        @if($item->interval_hari){{ $item->interval_hari }} hari @endif
                        @if($item->interval_hari && $item->interval_hm) / @endif
                        @if($item->interval_hm){{ $fmt($item->interval_hm) }} jam HM @endif
                    </td></tr>
                    <tr><td class="text-muted">Terakhir dilakukan</td><td class="mono">{{ $item->terakhir_tanggal ? $item->terakhir_tanggal->format('d/m/Y') : '-' }}@if($item->terakhir_hm !== null) &middot; HM {{ $fmt($item->terakhir_hm) }}@endif</td></tr>
                    @if($item->objek_tipe==='genset')
                    <tr><td class="text-muted">HM unit sekarang</td><td class="mono">{{ $hmSekarang !== null ? $fmt($hmSekarang) : 'belum tercatat' }}</td></tr>
                    @endif
                    <tr><td class="text-muted">Jatuh tempo</td><td>{{ $hasil['ringkas'] }}</td></tr>
                    @if($item->catatan)<tr><td class="text-muted">Catatan</td><td>{{ $item->catatan }}</td></tr>@endif
                </table>
                <div class="d-flex gap-2">
                    <a href="{{ route('perawatan.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
                    @can('perawatan.kelola', $item)
                    <a href="{{ route('perawatan.edit', $item) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil me-1"></i>Edit</a>
                    <form method="POST" action="{{ route('perawatan.destroy', $item) }}" onsubmit="return confirm('Hapus jadwal ini beserta riwayat pelaksanaannya?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Hapus</button>
                    </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-7">
        @can('perawatan.catat', $item)
        <div class="card mb-3" id="catat">
            <div class="card-header"><i class="bi bi-check2-square me-2 text-success"></i>Catat Pelaksanaan</div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-3 p-2 rounded" style="background:#f4f8fb;font-size:.78rem">
                    <span class="badge bg-secondary">1</span> Lakukan perawatan
                    <i class="bi bi-chevron-right text-muted"></i>
                    <span class="badge {{ $evidenDipilih ? 'bg-success' : 'bg-secondary' }}">2</span> Buat eviden (bukti laporan)
                    <i class="bi bi-chevron-right text-muted"></i>
                    <span class="badge bg-secondary">3</span> Catat di sini
                    <a href="{{ route('eviden.create', ['perawatan' => $item->id]) }}" class="btn btn-sm btn-outline-primary ms-auto" style="font-size:.72rem">
                        <i class="bi bi-camera me-1"></i>Buat Eviden Baru
                    </a>
                </div>
                @if($evidenDipilih)
                <div class="alert alert-success py-2" style="font-size:.8rem">
                    <i class="bi bi-check-circle me-1"></i>Eviden <strong>{{ $evidenDipilih->judul }}</strong> sudah terpilih. Lengkapi dan simpan pelaksanaan di bawah ini.
                </div>
                @endif
                <form method="POST" action="{{ route('perawatan.selesai', $item) }}" class="row g-2">
                    @csrf
                    <div class="col-6 col-md-4">
                        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" class="form-control" max="{{ now()->toDateString() }}" value="{{ old('tanggal', ($evidenDipilih && $evidenDipilih->tanggal->lte(now())) ? $evidenDipilih->tanggal->toDateString() : now()->toDateString()) }}" required>
                    </div>
                    @if($item->basis !== 'tanggal')
                    <div class="col-6 col-md-4">
                        <label class="form-label">HM saat dilakukan <span class="text-danger">*</span></label>
                        <input type="number" step="0.1" min="0" name="hm" class="form-control mono" value="{{ old('hm', $hmSekarang) }}" required>
                    </div>
                    @endif
                    <div class="col-12">
                        <label class="form-label">Catatan (opsional)</label>
                        <input type="text" name="catatan" class="form-control" maxlength="1000" value="{{ old('catatan') }}" placeholder="Mis. oli diganti 12 liter, filter baru, kondisi baik">
                    </div>
                    <div class="col-12">
                        <label class="form-label"><i class="bi bi-link-45deg me-1"></i>Kaitkan dengan Eviden (bukti laporan)</label>
                        <select name="eviden_id" class="form-select">
                            <option value="">&mdash; Tanpa eviden &mdash;</option>
                            @foreach($kandidat as $ev)
                            <option value="{{ $ev->id }}" {{ (string) old('eviden_id', $evidenDipilih->id ?? '') === (string) $ev->id ? 'selected' : '' }}>
                                {{ $ev->tanggal->format('d/m/Y') }} &middot; {{ \Illuminate\Support\Str::limit($ev->judul, 70) }}{{ $ev->lokasi ? ' ('.$ev->lokasi.')' : '' }}
                            </option>
                            @endforeach
                        </select>
                        <div class="form-text">Menampilkan eviden Anda dalam 120 hari terakhir yang belum dipakai jadwal ini. Belum ada? Klik <em>Buat Eviden Baru</em> di atas.</div>
                    </div>
                    <div class="col-12"><button class="btn btn-success"><i class="bi bi-check-circle me-1"></i>Simpan Pelaksanaan</button></div>
                </form>
            </div>
        </div>
        @endcan

        <div class="card">
            <div class="card-header"><i class="bi bi-clock-history me-2"></i>Riwayat Pelaksanaan</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th class="ps-3">Tanggal</th><th>HM</th><th>Petugas</th><th>Catatan</th><th>Eviden</th></tr></thead>
                        <tbody>
                            @forelse($item->logs as $l)
                            <tr>
                                <td class="ps-3 mono" style="font-size:.78rem">{{ $l->tanggal->format('d/m/Y') }}</td>
                                <td class="mono" style="font-size:.78rem">{{ $l->hm !== null ? $fmt($l->hm) : '-' }}</td>
                                <td style="font-size:.78rem">{{ $l->user->name ?? '-' }}</td>
                                <td style="font-size:.78rem">{{ $l->catatan ?: '-' }}</td>
                                <td style="font-size:.78rem">
                                    @if($l->eviden_id && in_array($l->eviden_id, $evidenBoleh))
                                    <a href="{{ route('eviden.show', $l->eviden_id) }}" title="{{ $l->eviden->judul ?? '' }}"><i class="bi bi-camera me-1"></i>Lihat</a>
                                    @elseif($l->eviden_id)
                                    <span class="text-muted" title="Eviden milik pengguna lain"><i class="bi bi-camera me-1"></i>Ada</span>
                                    @else
                                    <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">Belum ada riwayat</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
