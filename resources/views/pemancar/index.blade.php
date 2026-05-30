@extends('layouts.app')
@section('title','Data Pemancar')
@section('page-title','Data Pemancar')

@section('content')
<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    @if($isFiltered && $lokasiDinas)
    <div class="d-flex align-items-center gap-2 px-3 py-2 rounded"
         style="background:#e8f4fd;border:1px solid #b3d7f0;font-size:.8rem">
        <i class="bi bi-geo-alt-fill text-primary"></i>
        Lokasi Dinas: <strong>{{ $lokasiDinas }}</strong>
    </div>
    @else
    <span class="text-muted small">{{ $pemancars->total() }} pemancar terdaftar</span>
    @endif
    @if(auth()->user()->isAdmin())
    <a href="{{ route('pemancar.create') }}" class="btn btn-primary-custom ms-auto">
        <i class="bi bi-plus-lg me-1"></i>Tambah Pemancar
    </a>
    @endif
</div>

<div class="row g-3">
    @forelse($pemancars as $p)
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card h-100">
            {{-- Foto --}}
            <div style="height:175px;overflow:hidden;border-radius:10px 10px 0 0;
                 background:#f0f4f8;position:relative;cursor:{{ $p->fotos->isNotEmpty()?'zoom-in':'default' }}">
                @if($p->fotos->isNotEmpty())
                <img src="{{ $p->fotos->first()->url }}"
                     onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex'"
                     onclick="bukaLightbox('{{ $p->fotos->first()->url }}','{{ addslashes($p->nama_stasiun) }}')"
                     style="width:100%;height:100%;object-fit:cover;display:block;transition:transform .2s"
                     onmouseover="this.style.transform='scale(1.03)'"
                     onmouseout="this.style.transform='scale(1)'">
                <div style="display:none;width:100%;height:100%;align-items:center;justify-content:center;color:#aaa">
                    <i class="bi bi-broadcast" style="font-size:2.5rem"></i>
                </div>
                @else
                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#ccc">
                    <i class="bi bi-broadcast" style="font-size:2.5rem"></i>
                </div>
                @endif
                <span class="badge position-absolute top-0 end-0 m-2
                    {{ $p->modulasi==='FM'?'bg-primary':'bg-warning text-dark' }}">
                    {{ $p->modulasi }}
                </span>
                @if($p->lokasi)
                <span class="badge bg-dark position-absolute bottom-0 start-0 m-2"
                      style="font-size:.63rem">
                    <i class="bi bi-geo-alt me-1"></i>{{ $p->lokasi }}
                </span>
                @endif
                @if(!$p->is_active)
                <span class="badge bg-danger position-absolute top-0 start-0 m-2">Nonaktif</span>
                @endif
            </div>

            <div class="card-body pb-2">
                <h6 class="fw-bold mb-1" style="font-size:.9rem">{{ $p->nama_stasiun }}</h6>
                <div class="text-muted mb-3" style="font-size:.78rem">
                    {{ $p->merk }} {{ $p->tipe_unit }} — {{ ucfirst(str_replace('_',' ',$p->tipe_komponen)) }}
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <div class="p-2 rounded" style="background:#f8fafc;border:1px solid #eee">
                            <div style="font-size:.6rem;color:#636e72">OUTPUT FINAL</div>
                            <div class="mono fw-bold" style="font-size:.9rem">
                                {{ number_format($p->kapasitas_output_final,0) }} W
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 rounded" style="background:#f8fafc;border:1px solid #eee">
                            <div style="font-size:.6rem;color:#636e72">FREKUENSI</div>
                            <div class="mono fw-bold" style="font-size:.9rem">
                                {{ $p->frekuensi ?? '–' }} {{ $p->modulasi==='FM'?'MHz':'kHz' }}
                            </div>
                        </div>
                    </div>
                </div>
                @if($p->alamat_lokasi)
                <div class="text-muted mt-2" style="font-size:.73rem">
                    <i class="bi bi-map me-1"></i>{{ Str::limit($p->alamat_lokasi,55) }}
                </div>
                @endif
            </div>

            <div class="card-footer d-flex gap-2 pt-2">
                <a href="{{ route('pemancar.show',$p) }}" class="btn btn-sm btn-outline-primary flex-fill">
                    <i class="bi bi-eye me-1"></i>Detail
                </a>
                @if(auth()->user()->isAdmin())
                <a href="{{ route('pemancar.edit',$p) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-pencil"></i>
                </a>
                <form action="{{ route('pemancar.destroy',$p) }}" method="POST"
                      onsubmit="return confirm('Hapus pemancar {{ $p->nama_stasiun }}?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="card text-center py-5 text-muted">
            <i class="bi bi-broadcast fs-1 d-block mb-2"></i>
            @if($isFiltered && $lokasiDinas)
                Belum ada pemancar di lokasi <strong>{{ $lokasiDinas }}</strong>.
            @else
                Belum ada data pemancar.
            @endif
            @if(auth()->user()->isAdmin())
            <div class="mt-2">
                <a href="{{ route('pemancar.create') }}" class="btn btn-primary-custom">Tambah Pemancar</a>
            </div>
            @endif
        </div>
    </div>
    @endforelse
</div>
@if($pemancars->hasPages())
<div class="mt-3 d-flex justify-content-center">{{ $pemancars->links() }}</div>
@endif
@endsection
@push('styles')
<style>
.btn-primary-custom{background:var(--primary);color:#fff;border:none;border-radius:7px;padding:.42rem 1.05rem;font-weight:600;font-size:.82rem;}
.btn-primary-custom:hover{opacity:.88;color:#fff;}
</style>
@endpush
