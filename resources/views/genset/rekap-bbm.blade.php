@extends('layouts.app')
@section('title','Rekap Pengisian BBM Genset')
@section('page-title','Rekap Pengisian BBM Genset')
@section('content')
@php $fmt = fn($v) => rtrim(rtrim(number_format((float) $v, 1, ',', '.'), '0'), ','); @endphp
@if($errors->any())<div class="alert alert-danger py-2">@foreach($errors->all() as $e)<div style="font-size:.82rem">{{ $e }}</div>@endforeach</div>@endif

<div class="card mb-3"><div class="card-body py-3">
<form method="GET" action="{{ route('genset.rekap-bbm') }}" class="row g-2 align-items-end">
    <div class="col-6 col-md-2"><label class="form-label mb-1" style="font-size:.75rem">Dari</label><input type="date" name="dari" class="form-control form-control-sm" value="{{ $dari }}"></div>
    <div class="col-6 col-md-2"><label class="form-label mb-1" style="font-size:.75rem">Sampai</label><input type="date" name="sampai" class="form-control form-control-sm" value="{{ $sampai }}"></div>
    <div class="col-6 col-md-3"><label class="form-label mb-1" style="font-size:.75rem">Unit</label>
        <select name="genset_unit_id" class="form-select form-select-sm"><option value="">Semua unit</option>
        @foreach($units as $u)<option value="{{ $u->id }}" {{ (string) $filterUnit === (string) $u->id ? 'selected' : '' }}>{{ $u->nama_unit }}</option>@endforeach</select></div>
    <div class="col-6 col-md-2"><label class="form-label mb-1" style="font-size:.75rem">Lokasi</label>
        <select name="lokasi" class="form-select form-select-sm"><option value="">Semua</option>
        @foreach($lokasis as $l)<option {{ $filterLokasi === $l ? 'selected' : '' }}>{{ $l }}</option>@endforeach</select></div>
    <div class="col-12 col-md-3 d-flex gap-2">
        <button class="btn btn-sm btn-primary-custom flex-fill"><i class="bi bi-funnel me-1"></i>Tampilkan</button>
        <a href="{{ route('genset.rekap-bbm.cetak', request()->query()) }}" class="btn btn-sm btn-danger" target="_blank"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
        <a href="{{ route('genset.index') }}" class="btn btn-sm btn-outline-secondary">Kembali</a>
    </div>
</form></div></div>

<div class="row g-2 mb-3">
    <div class="col-6 col-md-3"><div class="card text-center"><div class="card-body py-2"><div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Total Pengisian</div><div class="mono fw-bold" style="font-size:1.6rem;color:var(--warning)">{{ $fmt($total) }} L</div></div></div></div>
    <div class="col-6 col-md-3"><div class="card text-center"><div class="card-body py-2"><div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Jumlah Pengisian</div><div class="mono fw-bold" style="font-size:1.6rem;color:var(--primary)">{{ $logs->count() }}</div></div></div></div>
    <div class="col-6 col-md-3"><div class="card text-center"><div class="card-body py-2"><div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Rata-rata / Pengisian</div><div class="mono fw-bold" style="font-size:1.6rem">{{ $logs->count() ? $fmt($total / $logs->count()) : '0' }} L</div></div></div></div>
    <div class="col-6 col-md-3"><div class="card text-center"><div class="card-body py-2"><div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Unit Terisi</div><div class="mono fw-bold" style="font-size:1.6rem;color:var(--success)">{{ $perUnit->count() }}</div></div></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-lg-7"><div class="card h-100"><div class="card-header"><h6 class="mb-0">Rekap per Unit</h6></div>
    <div class="card-body p-0"><div class="table-responsive"><table class="table mb-0" style="font-size:.82rem">
        <thead style="background:#f8f9fa"><tr><th class="ps-3">Unit</th><th class="text-end">Jml isi</th><th class="text-end">Total (L)</th><th class="text-end">Rata-rata (L)</th><th class="pe-3">Terakhir isi</th></tr></thead>
        <tbody>
        @forelse($perUnit as $r)
        <tr><td class="ps-3">{{ $r['unit']->nama_unit ?? '-' }}@if($r['unit'] && $r['unit']->lokasi)<div class="text-muted" style="font-size:.68rem">{{ $r['unit']->lokasi }}</div>@endif</td>
            <td class="text-end">{{ $r['jumlah'] }}</td><td class="text-end mono fw-bold">{{ $fmt($r['total']) }}</td><td class="text-end mono">{{ $fmt($r['rata']) }}</td><td class="pe-3">{{ $r['terakhir'] ? $r['terakhir']->format('d/m/Y') : '-' }}</td></tr>
        @empty
        <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada pengisian BBM pada periode ini.</td></tr>
        @endforelse
        </tbody></table></div></div></div></div>
    <div class="col-12 col-lg-5"><div class="card h-100"><div class="card-header"><h6 class="mb-0">Total per Bulan (L)</h6></div>
    <div class="card-body">@if($perBulan->isNotEmpty())<div style="position:relative;height:220px"><canvas id="chartBbm"></canvas></div>@else<div class="text-center text-muted py-4">Belum ada data.</div>@endif</div></div></div>
</div>

<div class="card"><div class="card-header"><h6 class="mb-0">Rincian Pengisian</h6></div>
<div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0" style="font-size:.82rem">
    <thead style="background:#f8f9fa"><tr><th class="ps-3">Tanggal</th><th>Unit</th><th class="text-end">Liter</th><th>Alasan</th><th>Petugas</th><th>Keterangan</th><th></th></tr></thead>
    <tbody>
    @forelse($logs->sortByDesc(fn($l) => $l->tanggal->format('Y-m-d').$l->jam_mulai) as $l)
    <tr><td class="ps-3">{{ $l->tanggal->format('d/m/Y') }} <span class="text-muted">{{ substr($l->jam_mulai, 0, 5) }}</span></td>
        <td>{{ $l->gensetUnit->nama_unit ?? '-' }}</td><td class="text-end mono fw-bold">{{ $fmt($l->bbm_isi) }}</td>
        <td>{{ $l->alasan_label }}</td><td>{{ $l->user->name ?? '-' }}</td><td>{{ $l->keterangan }}</td>
        <td><a href="{{ route('genset.show', $l) }}" class="btn btn-sm btn-outline-primary py-0"><i class="bi bi-eye"></i></a></td></tr>
    @empty
    <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data.</td></tr>
    @endforelse
    </tbody></table></div></div></div>
@endsection

@push('scripts')
@if($perBulan->isNotEmpty())
<script>
(function(){
    if (typeof Chart === 'undefined') return;
    const labels = {!! json_encode($perBulan->keys()->values()) !!};
    const data = {!! json_encode($perBulan->values()) !!};
    new Chart(document.getElementById('chartBbm'), {type: 'bar',
        data: {labels: labels, datasets: [{label: 'Liter', data: data, backgroundColor: '#f39c12'}]},
        options: {responsive: true, maintainAspectRatio: false, plugins: {legend: {display: false}}, scales: {y: {beginAtZero: true}}}});
})();
</script>
@endif
@endpush
