@extends('layouts.app')
@section('title', $t->kode)
@section('page-title','Titik Grounding')
@section('content')
@php
    $L = \App\Models\GroundingUkur::STATUS_LABEL; $s = $t->status_ukur;
    $fmt = fn($v) => $v === null ? '-' : rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
    $spek = [
        'Peruntukan' => $t->peruntukan, 'Tipe grounding' => $t->tipe, 'Jenis bahan' => $t->bahan,
        'Kedalaman' => $t->kedalaman_m !== null ? $fmt($t->kedalaman_m).' m' : null,
        'Diameter rod' => $t->diameter_mm !== null ? $fmt($t->diameter_mm).' mm' : null,
        'Panjang kabel' => $t->panjang_kabel_m !== null ? $fmt($t->panjang_kabel_m).' m' : null,
        'Ukuran kabel' => $t->ukuran_kabel_mm2 !== null ? $fmt($t->ukuran_kabel_mm2)." mm\u{00B2}" : null,
        'Jumlah elektroda' => $t->jumlah_elektroda, 'Tahun pasang' => $t->tahun_pasang,
        'Batas tahanan' => $fmt($t->batas_ohm)." \u{03A9}",
        'Koordinat' => $t->latitude !== null ? $t->latitude.', '.$t->longitude : null,
    ];
@endphp
@if(session('warning'))<div class="alert alert-warning py-2">{{ session('warning') }}</div>@endif
@if($errors->any())<div class="alert alert-danger py-2">@foreach($errors->all() as $e)<div style="font-size:.82rem">{{ $e }}</div>@endforeach</div>@endif

<div class="d-flex align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h5 class="mb-0">{{ $t->kode }} &mdash; {{ $t->nama }}</h5>
        <div class="text-muted" style="font-size:.78rem"><a href="{{ route('grounding.wilayah.show', $t->wilayah) }}">{{ $t->wilayah->nama }}</a>
            &bull; <span class="badge" style="background:{{ \App\Models\GroundingTitik::warna($s) }}">{{ $L[$s] }}</span>
            @if($t->status !== 'aktif') <span class="badge bg-secondary">{{ \App\Models\GroundingTitik::STATUS[$t->status] }}</span>@endif</div>
    </div>
    <div class="ms-auto d-flex gap-2 flex-wrap">
        <a href="{{ route('grounding.titik.kartu', $t) }}" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf me-1"></i>Kartu PDF</a>
        <a href="{{ route('grounding.ukur.create', $t) }}" class="btn btn-sm btn-success"><i class="bi bi-plus-circle me-1"></i>Catat Pengukuran</a>
        @if($bisaKelola)
        <a href="{{ route('grounding.titik.edit', $t) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
        <form action="{{ route('grounding.titik.destroy', $t) }}" method="POST" onsubmit="return confirm('Hapus titik ini beserta seluruh riwayat pengukurannya?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
        @endif
        <a href="{{ route('grounding.wilayah.show', $t->wilayah) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    </div>
</div>

@if(!empty($jadwal) && $jadwalHasil)
<div class="alert py-2 mb-3 d-flex align-items-center gap-2 flex-wrap" style="font-size:.82rem;background:#f4f8fb;border:1px solid #d6e4ee">
    <i class="bi bi-wrench-adjustable"></i>
    <span>Jadwal ukur: <strong>{{ $jadwal->interval_hari }} hari</strong> &mdash; <span class="badge" style="background:{{ \App\Support\PerawatanHitung::WARNA[$jadwalHasil['status']] }}">{{ \App\Support\PerawatanHitung::LABEL[$jadwalHasil['status']] }}</span> {{ $jadwalHasil['ringkas'] }}</span>
    <a href="{{ route('perawatan.show', $jadwal) }}" class="ms-auto">Lihat jadwal</a>
</div>
@endif
<div class="row g-3 mb-3">
    <div class="col-12 col-lg-5"><div class="card h-100"><div class="card-header"><h6 class="mb-0">Spesifikasi</h6></div>
        <div class="card-body p-0"><table class="table table-sm mb-0" style="font-size:.82rem">
            @foreach($spek as $k => $v)@if($v !== null && $v !== '')<tr><td class="ps-3 text-muted" style="width:45%">{{ $k }}</td><td>{{ $v }}</td></tr>@endif @endforeach
        </table>@if($t->catatan)<div class="p-3 border-top" style="font-size:.8rem;white-space:pre-wrap">{{ $t->catatan }}</div>@endif</div></div></div>
    <div class="col-12 col-lg-7"><div class="card h-100"><div class="card-header"><h6 class="mb-0">Tren Tahanan (&Omega;)</h6></div>
        <div class="card-body">@if($seri->count())<div style="position:relative;height:240px"><canvas id="gchart"></canvas></div>@else<div class="text-center text-muted py-5">Belum ada pengukuran.</div>@endif</div></div></div>
</div>

@if($t->fotos->isNotEmpty())
<div class="card mb-3"><div class="card-header"><h6 class="mb-0"><i class="bi bi-images me-2"></i>Foto Grounding ({{ $t->fotos->count() }})</h6></div>
<div class="card-body"><div class="row g-3">
    @foreach($t->fotos as $f)
    <div class="col-6 col-md-3"><div style="border:1px solid #dee2e6;border-radius:8px;overflow:hidden;height:100%">
        <div style="aspect-ratio:4/3;background:#f4f6f8"><img src="{{ $f->thumb_url }}" loading="lazy" alt="" style="width:100%;height:100%;object-fit:cover;cursor:zoom-in" onclick="bukaLightbox('{{ $f->url }}', {{ json_encode((string) $f->keterangan) }})" onerror="this.onerror=null;this.src='{{ $f->url }}'"></div>
        <div style="font-size:.76rem;padding:.35rem .5rem;color:#444">{{ $f->keterangan ?: 'Foto '.$loop->iteration }}</div></div></div>
    @endforeach
</div></div></div>
@endif

<div class="card"><div class="card-header"><h6 class="mb-0">Riwayat Pengukuran</h6></div>
<div class="card-body p-0"><div class="table-responsive"><table class="table mb-0" style="font-size:.82rem">
    <thead style="background:#f8f9fa"><tr><th class="ps-3">Tanggal</th><th class="text-end">Nilai (&Omega;)</th><th>Status</th><th>Tanah</th><th>Alat / metode</th><th>Petugas</th><th>Keterangan</th><th></th></tr></thead>
    <tbody>
    @forelse($t->ukurs as $u)
    <tr>
        <td class="ps-3">{{ $u->tanggal->format('d/m/Y') }}{{ $u->jam ? ' '.substr($u->jam,0,5) : '' }}</td>
        <td class="text-end mono fw-bold">{{ $fmt($u->nilai_ohm) }}</td>
        <td><span class="badge" style="background:{{ \App\Models\GroundingTitik::warna($u->status) }}">{{ $L[$u->status] }}</span></td>
        <td>{{ \App\Models\GroundingUkur::KONDISI[$u->kondisi_tanah] ?? '-' }}</td>
        <td>{{ trim($u->alat.' '.($u->metode ? '('.$u->metode.')' : '')) ?: '-' }}</td>
        <td>{{ $u->user->name ?? '-' }}</td>
        <td style="white-space:pre-wrap">{{ $u->keterangan }}@foreach($u->fotos as $f) <a href="#" onclick="bukaLightbox('{{ $f->url }}', {{ json_encode((string) $f->keterangan) }});return false" title="{{ $f->keterangan }}"><i class="bi bi-image"></i></a>@endforeach</td>
        <td class="text-nowrap">@if(\App\Support\Akses::groundingUkurHapus(auth()->user(), $u->user_id))<a href="{{ route('grounding.ukur.edit', $u) }}" class="btn btn-sm btn-outline-warning py-0"><i class="bi bi-pencil"></i></a> <form style="display:inline" action="{{ route('grounding.ukur.destroy', $u) }}" method="POST" onsubmit="return confirm('Hapus pengukuran ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger py-0"><i class="bi bi-trash"></i></button></form>@endif</td>
    </tr>
    @empty
    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada pengukuran.</td></tr>
    @endforelse
    </tbody></table></div></div></div>
@endsection

@if($seri->count())
@push('scripts')
<script>
(function(){
    const el = document.getElementById('gchart'); if (!el || typeof Chart === 'undefined') return;
    const labels = @json($seri->map(fn($u) => $u->tanggal->format('d/m/y'))->values());
    const nilai  = @json($seri->map(fn($u) => (float) $u->nilai_ohm)->values());
    const batas  = {{ (float) $t->batas_ohm }};
    new Chart(el, {type: 'line', data: {labels: labels, datasets: [
        {label: 'Tahanan (\u03A9)', data: nilai, borderColor: '#0a3d62', backgroundColor: '#0a3d62', tension: .2, pointRadius: 4},
        {label: 'Batas (\u03A9)', data: nilai.map(() => batas), borderColor: '#e74c3c', borderDash: [6, 4], pointRadius: 0}
    ]}, options: {responsive: true, maintainAspectRatio: false, scales: {y: {beginAtZero: true, title: {display: true, text: '\u03A9'}}}}});
})();
</script>
@endpush
@endif
