@extends('layouts.app')
@section('title', $eviden->judul)
@section('page-title','Detail Eviden')

@section('content')
<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    <a href="{{ route('eviden.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
    <span class="fw-bold text-truncate">{{ $eviden->judul }}</span>
    @if(auth()->user()->isAdmin() || auth()->id()===$eviden->user_id)
    <div class="d-flex gap-2 ms-auto">
        <a href="{{ route('eviden.edit',$eviden) }}" class="btn btn-sm btn-outline-warning">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        <form action="{{ route('eviden.destroy',$eviden) }}" method="POST"
              onsubmit="return confirm('Hapus eviden ini?')">
            @csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger">
                <i class="bi bi-trash me-1"></i>Hapus
            </button>
        </form>
    </div>
    @endif
</div>

<div class="row g-3">
    {{-- Kiri: Info + Foto --}}
    <div class="col-12 col-lg-8">

        {{-- Info Utama --}}
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-info-circle me-2 text-primary"></i>Informasi Kegiatan</div>
            <div class="card-body">
                <h5 class="fw-bold mb-3">{{ $eviden->judul }}</h5>
                <div class="row g-3 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="text-muted" style="font-size:.7rem">Tanggal</div>
                        <div class="fw-bold mono">{{ \Carbon\Carbon::parse($eviden->tanggal)->format('d/m/Y') }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted" style="font-size:.7rem">Jam Mulai</div>
                        <div class="fw-bold mono">{{ $eviden->jam_mulai }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted" style="font-size:.7rem">Jam Selesai</div>
                        <div class="fw-bold mono">{{ $eviden->jam_selesai }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted" style="font-size:.7rem">Durasi</div>
                        <div class="fw-bold">{{ $eviden->durasi }}</div>
                    </div>
                    @if($eviden->lokasi)
                    <div class="col-12">
                        <div class="text-muted" style="font-size:.7rem">Lokasi Kegiatan</div>
                        <div class="fw-bold">
                            <i class="bi bi-geo-alt me-1 text-danger"></i>{{ $eviden->lokasi }}
                        </div>
                    </div>
                    @endif
                </div>
                @if($eviden->deskripsi)
                <div class="p-3 rounded" style="background:#f8fafc;border:1px solid #eee;
                     font-size:.88rem;line-height:1.65;white-space:pre-line">{{ $eviden->deskripsi }}</div>
                @endif
            </div>
        </div>

        {{-- Foto Dokumentasi --}}
        @if($eviden->fotos->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <i class="bi bi-images me-2"></i>Foto Dokumentasi ({{ $eviden->fotos->count() }})
                <small class="text-muted fw-normal ms-1">— klik foto untuk preview besar</small>
            </div>
            <div class="card-body">
                {{-- Foto Utama --}}
                <div style="height:300px;border-radius:8px;overflow:hidden;margin-bottom:.75rem;
                     background:#f0f4f8;cursor:zoom-in;">
                    <img id="evidenFotoUtama"
                         src="{{ $eviden->fotos->first()->url }}"
                         onerror="this.parentElement.style.background='#eee'"
                         onclick="bukaLightbox(this.src, this.dataset.caption||'')"
                         data-caption="{{ $eviden->fotos->first()->keterangan }}"
                         style="width:100%;height:100%;object-fit:cover;display:block;transition:opacity .15s"
                         class="foto-lightbox-trigger"
                         data-src="{{ $eviden->fotos->first()->url }}">
                </div>

                {{-- Grid Thumbnail --}}
                @if($eviden->fotos->count() > 1)
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(90px,1fr));gap:.5rem">
                    @foreach($eviden->fotos as $i => $foto)
                    <div style="aspect-ratio:1;border-radius:7px;overflow:hidden;
                         border:2px solid {{ $i===0?'var(--primary)':'#dfe6e9' }};
                         cursor:zoom-in;position:relative;transition:border-color .15s"
                         id="thumb-{{ $i }}"
                         onclick="gantiEvidenFoto('{{ $foto->url }}','{{ addslashes($foto->keterangan??'') }}',{{ $i }})">
                        <img src="{{ $foto->url }}"
                             onerror="this.parentElement.style.background='#eee'"
                             style="width:100%;height:100%;object-fit:cover;display:block">
                        @if($foto->keterangan)
                        <div style="position:absolute;bottom:0;left:0;right:0;
                             background:rgba(0,0,0,.6);color:#fff;font-size:.6rem;
                             padding:.2rem .3rem;text-align:center;
                             white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            {{ $foto->keterangan }}
                        </div>
                        @endif
                        {{-- Nomor foto --}}
                        <div style="position:absolute;top:3px;left:4px;background:rgba(0,0,0,.55);
                             color:#fff;font-size:.58rem;border-radius:3px;padding:.05rem .3rem">
                            {{ $i+1 }}
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- Keterangan foto aktif --}}
                <div id="keteranganFoto" class="text-muted text-center mt-2"
                     style="font-size:.78rem;min-height:1.2rem">
                    {{ $eviden->fotos->first()->keterangan }}
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Kanan: Personil --}}
    <div class="col-12 col-lg-4">

        {{-- Dibuat Oleh --}}
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-person-check me-2 text-success"></i>Dibuat Oleh
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:40px;height:40px;min-width:40px;border-radius:50%;
                         background:var(--primary);color:#fff;display:flex;align-items:center;
                         justify-content:center;font-weight:700;font-size:.88rem">
                        {{ strtoupper(substr($eviden->user->name,0,2)) }}
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:.9rem">{{ $eviden->user->name }}</div>
                        <small class="text-muted">{{ $eviden->user->lokasi_dinas ?? 'Operator' }}</small>
                    </div>
                </div>
                <div class="text-muted mt-2" style="font-size:.73rem">
                    <i class="bi bi-clock me-1"></i>{{ $eviden->created_at->format('d/m/Y H:i') }}
                </div>
            </div>
        </div>

        {{-- Operator Terlibat --}}
        @if($eviden->operators->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-people me-2 text-primary"></i>
                Operator Terlibat ({{ $eviden->operators->count() }})
            </div>
            <div class="card-body p-0">
                @foreach($eviden->operators as $op)
                <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom">
                    <div style="width:32px;height:32px;min-width:32px;border-radius:50%;
                         background:var(--primary);color:#fff;display:flex;align-items:center;
                         justify-content:center;font-size:.7rem;font-weight:700">
                        {{ strtoupper(substr($op->name,0,2)) }}
                    </div>
                    <div>
                        <div style="font-size:.85rem;font-weight:600">{{ $op->name }}</div>
                        @if($op->lokasi_dinas)
                        <small class="text-muted">{{ $op->lokasi_dinas }}</small>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Supervisi --}}
        @if($eviden->supervisi)
        <div class="card">
            <div class="card-header">
                <i class="bi bi-person-badge me-2 text-warning"></i>Supervisi / Pengelola
            </div>
            <div class="card-body">
                <div class="fw-bold" style="font-size:.9rem">{{ $eviden->supervisi }}</div>
                <small class="text-muted">Hadir saat kegiatan berlangsung</small>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
let activeThumb = 0;
const totalFotos = {{ $eviden->fotos->count() }};

function gantiEvidenFoto(src, caption, idx){
    // Update foto utama
    const img = document.getElementById('evidenFotoUtama');
    img.src = src;
    img.dataset.src = src;
    img.dataset.caption = caption;

    // Update keterangan
    document.getElementById('keteranganFoto').textContent = caption || '';

    // Update border thumbnail
    if(activeThumb !== undefined){
        const prev = document.getElementById('thumb-'+activeThumb);
        if(prev) prev.style.borderColor = '#dfe6e9';
    }
    const curr = document.getElementById('thumb-'+idx);
    if(curr) curr.style.borderColor = 'var(--primary)';
    activeThumb = idx;

    // Langsung buka lightbox
    bukaLightbox(src, caption||'');
}

// Keyboard navigation di lightbox
document.addEventListener('keydown', function(e){
    const modal = document.getElementById('globalLightbox');
    if(!modal.classList.contains('show')) return;
    if(e.key === 'ArrowRight') navigateFoto(1);
    if(e.key === 'ArrowLeft')  navigateFoto(-1);
});

function navigateFoto(dir){
    const next = (activeThumb + dir + totalFotos) % totalFotos;
    const thumb = document.getElementById('thumb-'+next);
    if(thumb) thumb.click();
}
</script>
@endpush