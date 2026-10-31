@extends('layouts.app')
@section('title', $w->nama)
@section('page-title','Wilayah Grounding')
@section('content')
@php $L = \App\Models\GroundingUkur::STATUS_LABEL; @endphp
@if(session('warning'))<div class="alert alert-warning py-2">{{ session('warning') }}</div>@endif
@if($errors->any())<div class="alert alert-danger py-2">@foreach($errors->all() as $e)<div style="font-size:.82rem">{{ $e }}</div>@endforeach</div>@endif

<div class="d-flex align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h5 class="mb-0">{{ $w->nama }}</h5>
        <div class="text-muted" style="font-size:.78rem">{{ $w->lokasi ?: '-' }} &bull; {{ $w->titiks->count() }} titik</div>
        @if($w->keterangan)<div style="font-size:.8rem">{{ $w->keterangan }}</div>@endif
    </div>
    <div class="ms-auto d-flex gap-2 flex-wrap">
        <a href="{{ route('grounding.ronde', $w) }}" class="btn btn-sm btn-success"><i class="bi bi-clipboard2-pulse me-1"></i>Ukur Satu Ronde</a>
        @if($bisaKelola)
        <a href="{{ route('grounding.titik.create', ['wilayah' => $w->id]) }}" class="btn btn-sm btn-primary-custom"><i class="bi bi-geo-alt me-1"></i>Tambah Titik</a>
        <a href="{{ route('grounding.wilayah.edit', $w) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
        <form action="{{ route('grounding.wilayah.destroy', $w) }}" method="POST" onsubmit="return confirm('Hapus wilayah ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
        @endif
        <a href="{{ route('grounding.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    </div>
</div>

@php $adaGps = $w->titiks->contains(fn($x) => $x->latitude !== null && $x->longitude !== null); @endphp
@if($w->peta_url && $adaGps)
<ul class="nav nav-pills mb-2" style="font-size:.8rem">
    <li class="nav-item"><button type="button" class="nav-link active py-1" id="tab-denah" onclick="gTab('denah')">Denah</button></li>
    <li class="nav-item"><button type="button" class="nav-link py-1" id="tab-online" onclick="gTab('online')">Peta Online (GPS)</button></li>
</ul>
@endif
@if($w->peta_url)
<div class="card mb-3" id="box-denah"><div class="card-body p-2">
    <div style="position:relative;display:inline-block;max-width:100%;line-height:0">
        <img src="{{ $w->peta_url }}" alt="Peta {{ $w->nama }}" style="max-width:100%;height:auto;display:block;border-radius:6px">
        @foreach($w->titiks as $t)
            @if($t->pos_x !== null && $t->pos_y !== null)
            <a href="{{ route('grounding.titik.show', $t) }}" title="{{ $t->kode }} - {{ $t->nama }}"
               style="position:absolute;left:{{ $t->pos_x }}%;top:{{ $t->pos_y }}%;transform:translate(-50%,-100%);text-decoration:none;{{ $t->status === 'aktif' ? '' : 'opacity:.45' }}">
                <span style="display:inline-block;background:{{ \App\Models\GroundingTitik::warna($t->status_ukur) }};color:#fff;font-size:.62rem;line-height:1;font-weight:700;padding:4px 6px;border-radius:10px;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.5);white-space:nowrap">{{ $t->kode }}</span>
            </a>
            @endif
        @endforeach
    </div>
    <div class="d-flex gap-3 flex-wrap mt-2" style="font-size:.72rem">
        @foreach(['baik','waspada','melebihi','belum'] as $s)<span><i class="bi bi-circle-fill" style="color:{{ \App\Models\GroundingTitik::warna($s) }}"></i> {{ $L[$s] }}</span>@endforeach
    </div>
</div></div>
@endif

@if($adaGps)
<div class="card mb-3" id="box-online" style="{{ $w->peta_url ? 'display:none' : '' }}"><div class="card-body p-2">
    <div id="leaflet-map" style="height:380px;border-radius:6px"></div>
    <div class="text-muted mt-1" style="font-size:.7rem">Butuh internet untuk memuat peta dasar (OpenStreetMap). Hanya titik berkoordinat yang tampil.</div>
</div></div>
@endif

<div class="card"><div class="card-body p-0"><div class="table-responsive">
<table class="table table-hover mb-0" style="font-size:.82rem">
    <thead style="background:#f8f9fa"><tr><th class="ps-3">Kode</th><th>Nama</th><th>Peruntukan</th><th class="text-end">Terakhir (&Omega;)</th><th class="text-end">Batas (&Omega;)</th><th>Status</th><th>Tanggal ukur</th></tr></thead>
    <tbody>
    @forelse($w->titiks as $t)
    @php $u = $t->ukurTerakhir; $s = $t->status_ukur; @endphp
    <tr style="{{ $t->status === 'aktif' ? '' : 'opacity:.55' }}">
        <td class="ps-3"><a href="{{ route('grounding.titik.show', $t) }}" class="fw-semibold text-decoration-none">{{ $t->kode }}</a></td>
        <td>{{ $t->nama }}@if($t->status !== 'aktif') <span class="badge bg-secondary">{{ \App\Models\GroundingTitik::STATUS[$t->status] }}</span>@endif</td>
        <td>{{ $t->peruntukan ?: '-' }}</td>
        <td class="text-end mono">{{ $u ? rtrim(rtrim(number_format($u->nilai_ohm, 2, ',', '.'), '0'), ',') : '-' }}</td>
        <td class="text-end mono">{{ rtrim(rtrim(number_format($t->batas_ohm, 2, ',', '.'), '0'), ',') }}</td>
        <td><span class="badge" style="background:{{ \App\Models\GroundingTitik::warna($s) }}">{{ $L[$s] }}</span></td>
        <td>{{ $u ? $u->tanggal->format('d/m/Y') : '-' }}</td>
    </tr>
    @empty
    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada titik grounding.</td></tr>
    @endforelse
    </tbody>
</table></div></div></div>


@if(true)
<div class="row g-3 mt-1 mb-4">
    <div class="col-12 col-lg-6"><div class="card h-100"><div class="card-header"><h6 class="mb-0"><i class="bi bi-file-earmark-pdf me-1"></i>Laporan Pengukuran (PDF)</h6></div>
    <div class="card-body"><form method="GET" action="{{ route('grounding.laporan', $w) }}" class="row g-2 align-items-end">
        <div class="col-6"><label class="form-label mb-1" style="font-size:.74rem">Dari</label><input type="date" name="dari" class="form-control form-control-sm" value="{{ now()->startOfYear()->toDateString() }}"></div>
        <div class="col-6"><label class="form-label mb-1" style="font-size:.74rem">Sampai</label><input type="date" name="sampai" class="form-control form-control-sm" value="{{ now()->toDateString() }}"></div>
        <div class="col-12"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-download me-1"></i>Unduh Laporan</button></div>
    </form></div></div></div>
    @if($bisaKelola)
    <div class="col-12 col-lg-6"><div class="card h-100"><div class="card-header"><h6 class="mb-0"><i class="bi bi-wrench-adjustable me-1"></i>Jadwal Ukur Berkala</h6></div>
    <div class="card-body"><form method="POST" action="{{ route('grounding.jadwal', $w) }}" class="row g-2 align-items-end">@csrf
        <div class="col-12 col-md-5"><label class="form-label mb-1" style="font-size:.74rem">Interval (hari)</label><input type="number" name="interval_hari" min="1" max="3650" class="form-control form-control-sm" value="180" required></div>
        <div class="col-12 col-md-7"><label class="form-label mb-1" style="font-size:.74rem">Nama tugas</label><input type="text" name="nama_tugas" maxlength="150" class="form-control form-control-sm" placeholder="Pengukuran tahanan grounding"></div>
        <div class="col-12"><button class="btn btn-sm btn-outline-primary">Buat jadwal untuk semua titik aktif</button>
        <div class="form-text">Titik yang sudah berjadwal dilewati. Pengingat muncul di menu Perawatan Rutin dan dashboard; setiap pengukuran memajukan jadwalnya otomatis.</div></div>
    </form></div></div></div>
    @endif
</div>
@endif
@endsection

@push('scripts')
<script>
function gTab(t){
    const d = document.getElementById('box-denah'), o = document.getElementById('box-online');
    if (d) d.style.display = t === 'denah' ? '' : 'none';
    if (o) o.style.display = t === 'online' ? '' : 'none';
    document.getElementById('tab-denah').classList.toggle('active', t === 'denah');
    document.getElementById('tab-online').classList.toggle('active', t === 'online');
    if (t === 'online' && window.__gmap) setTimeout(function(){ window.__gmap.invalidateSize(); }, 50);
}
</script>
@if($adaGps)
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function(){
    if (typeof L === 'undefined') { const b = document.getElementById('leaflet-map'); if (b) b.innerHTML = '<div class="p-4 text-center text-muted">Peta online tidak dapat dimuat (tidak ada internet).</div>'; return; }
    const pts = @json($w->titiks->filter(fn($x) => $x->latitude !== null && $x->longitude !== null)->map(fn($x) => [
        'lat' => (float) $x->latitude, 'lng' => (float) $x->longitude, 'kode' => $x->kode, 'nama' => $x->nama,
        'warna' => \App\Models\GroundingTitik::warna($x->status_ukur), 'url' => route('grounding.titik.show', $x),
    ])->values());
    const map = L.map('leaflet-map'); window.__gmap = map;
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '&copy; OpenStreetMap'}).addTo(map);
    const grp = [];
    pts.forEach(function(p){
        const m = L.circleMarker([p.lat, p.lng], {radius: 9, color: '#fff', weight: 2, fillColor: p.warna, fillOpacity: .95}).addTo(map);
        m.bindPopup('<strong>' + p.kode + '</strong><br>' + p.nama.replace(/</g, '&lt;') + '<br><a href="' + p.url + '">Buka titik</a>');
        grp.push([p.lat, p.lng]);
    });
    if (grp.length === 1) map.setView(grp[0], 17); else map.fitBounds(grp, {padding: [30, 30]});
})();
</script>
@endif
@endpush
