@extends('layouts.app')
@section('title',$pemancar->nama_stasiun)
@section('page-title','Detail Pemancar')

@section('content')
<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    <a href="{{ route('pemancar.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
    <span class="fw-bold">{{ $pemancar->nama_stasiun }}</span>
    @if(auth()->user()->isAdmin())
    <a href="{{ route('pemancar.edit',$pemancar) }}" class="btn btn-sm btn-outline-warning ms-auto">
        <i class="bi bi-pencil me-1"></i>Edit
    </a>
    @endif
</div>

<div class="row g-3">
    <div class="col-12 col-lg-8">
        {{-- Info --}}
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center gap-2 flex-wrap">
                <i class="bi bi-broadcast-pin text-primary"></i>
                <strong>{{ $pemancar->nama_stasiun }}</strong>
                <span class="badge {{ $pemancar->modulasi==='FM'?'bg-primary':'bg-warning text-dark' }}">{{ $pemancar->modulasi }}</span>
                @if($pemancar->lokasi)
                <span class="badge bg-secondary"><i class="bi bi-geo-alt me-1"></i>{{ $pemancar->lokasi }}</span>
                @endif
                @if(!$pemancar->is_active)<span class="badge bg-danger">Nonaktif</span>@endif
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach([
                        ['Merk',$pemancar->merk],
                        ['Tipe Unit',$pemancar->tipe_unit],
                        ['Komponen',$pemancar->tipe_komponen_label],
                        ['Frekuensi',($pemancar->frekuensi??'–').($pemancar->frekuensi?' '.($pemancar->modulasi==='FM'?'MHz':'kHz'):'')],
                        ['Output Final',number_format($pemancar->kapasitas_output_final,0).' W'],
                        ['Tipe Exciter',$pemancar->tipe_exciter??'–'],
                        ['Tipe Driver',$pemancar->tipe_driver??'–'],
                        ['No. Izin',$pemancar->nomor_izin??'–'],
                        ['Tgl. Instalasi',$pemancar->tanggal_instalasi?->format('d/m/Y')??'–'],
                        ['Nama Lokasi',$pemancar->lokasi??'–'],
                    ] as [$label,$val])
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="text-muted" style="font-size:.7rem">{{ $label }}</div>
                        <div class="fw-600" style="font-size:.85rem">{{ $val }}</div>
                    </div>
                    @endforeach
                    @if($pemancar->alamat_lokasi)
                    <div class="col-12">
                        <div class="text-muted" style="font-size:.7rem">Alamat</div>
                        <div style="font-size:.85rem">{{ $pemancar->alamat_lokasi }}</div>
                    </div>
                    @endif
                    @if($pemancar->keterangan)
                    <div class="col-12">
                        <div class="text-muted" style="font-size:.7rem">Keterangan</div>
                        <div style="font-size:.85rem">{{ $pemancar->keterangan }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Peta --}}
        @if($pemancar->latitude && $pemancar->longitude)
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-map me-2"></i>Lokasi di Peta</div>
            <div class="card-body p-2">
                <div id="map" style="height:240px;border-radius:8px"></div>
            </div>
        </div>
        @endif

        {{-- Log Terakhir --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-journal-text me-1"></i>Log Operasional Terakhir</span>
                <a href="{{ route('operasional.create') }}" class="btn btn-sm btn-success">
                    <i class="bi bi-plus"></i><span class="d-none d-sm-inline ms-1">Catat</span>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Waktu</th>
                            <th>Operator</th>
                            <th class="text-end">Out Final</th>
                            <th class="text-center">VSWR</th>
                            <th class="text-end d-none d-md-table-cell">Suhu Rmg</th>
                            <th class="text-end d-none d-md-table-cell">RH%</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pemancar->operasionalLogs as $log)
                        <tr>
                            <td class="ps-3 mono" style="font-size:.76rem">{{ $log->dicatat_pada->format('d/m H:i') }}</td>
                            <td style="font-size:.8rem">{{ $log->user->name }}</td>
                            <td class="text-end mono" style="font-size:.78rem">{{ $log->output_final_pa ? number_format($log->output_final_pa,1).' W':'–' }}</td>
                            <td class="text-center">
                                @if($log->vswr_final)
                                    @php $v=$log->vswr_final;$cls=$v<=1.5?'vswr-baik':($v<=2?'vswr-sedang':'vswr-buruk'); @endphp
                                    <span class="mono {{ $cls }}" style="font-size:.78rem">{{ number_format($v,3) }}</span>
                                @else <span class="text-muted">–</span> @endif
                            </td>
                            <td class="text-end mono d-none d-md-table-cell" style="font-size:.78rem">{{ $log->suhu_ruangan!==null?$log->suhu_ruangan.'°C':'–' }}</td>
                            <td class="text-end mono d-none d-md-table-cell" style="font-size:.78rem">{{ $log->kelembaban!==null?$log->kelembaban.'%':'–' }}</td>
                            <td>
                                <a href="{{ route('operasional.show',$log) }}"
                                   class="btn btn-sm btn-outline-primary" style="padding:.12rem .38rem;font-size:.7rem">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted py-3 small">Belum ada log</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- FOTO --}}
    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-images me-2"></i>Foto Pemancar ({{ $pemancar->fotos->count() }})</div>
            <div class="card-body">
                @if($pemancar->fotos->isNotEmpty())
                {{-- Foto Utama --}}
                <div style="height:200px;border-radius:8px;overflow:hidden;margin-bottom:.6rem;
                     background:#f0f4f8;cursor:zoom-in;" class="foto-utama-wrap">
                    <img id="fotoUtama"
                         src="{{ $pemancar->fotos->first()->url }}"
                         data-src="{{ $pemancar->fotos->first()->url }}"
                         data-caption="{{ $pemancar->fotos->first()->keterangan }}"
                         class="foto-lightbox-trigger"
                         onerror="this.parentElement.innerHTML='<div class=\'d-flex align-items-center justify-content-center h-100 text-muted\'><i class=\'bi bi-image fs-1\'></i></div>'"
                         style="width:100%;height:100%;object-fit:cover">
                </div>
                {{-- Thumbnails --}}
                @if($pemancar->fotos->count() > 1)
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(72px,1fr));gap:.4rem">
                    @foreach($pemancar->fotos as $foto)
                    <div style="aspect-ratio:1;border-radius:6px;overflow:hidden;
                         border:2px solid #dfe6e9;cursor:zoom-in;position:relative"
                         onclick="gantiDanBuka('{{ $foto->url }}','{{ $foto->keterangan }}')">
                        <img src="{{ $foto->url }}"
                             onerror="this.parentElement.style.background='#eee'"
                             style="width:100%;height:100%;object-fit:cover;display:block">
                        @if($foto->keterangan)
                        <div style="position:absolute;bottom:0;left:0;right:0;
                             background:rgba(0,0,0,.55);color:#fff;font-size:.58rem;
                             padding:.15rem .3rem;text-align:center;white-space:nowrap;
                             overflow:hidden;text-overflow:ellipsis">{{ $foto->keterangan }}</div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
                @else
                <div class="text-center text-muted py-4">
                    <i class="bi bi-image fs-2 d-block mb-2"></i>Belum ada foto
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
.foto-utama-wrap img{transition:opacity .15s;}
.foto-utama-wrap img:hover{opacity:.92;}
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
@if($pemancar->latitude && $pemancar->longitude)
const map=L.map('map').setView([{{ $pemancar->latitude }},{{ $pemancar->longitude }}],14);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
L.marker([{{ $pemancar->latitude }},{{ $pemancar->longitude }}]).addTo(map)
  .bindPopup('<b>{{ $pemancar->nama_stasiun }}</b>{{ $pemancar->lokasi?"<br>".$pemancar->lokasi:"" }}')
  .openPopup();
@endif

// Ganti foto utama dan langsung buka lightbox
function gantiDanBuka(src, caption){
    const img = document.getElementById('fotoUtama');
    img.src = src;
    img.dataset.src = src;
    img.dataset.caption = caption||'';
    bukaLightbox(src, caption||'');
}
</script>
@endpush
