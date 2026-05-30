@extends('layouts.app')
@section('title','Catatan Eviden')
@section('page-title','Catatan Eviden')

@section('content')

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-6 col-sm-3 col-md-2">
                <label class="form-label mb-1">Dari</label>
                <input type="date" name="tanggal_dari" class="form-control form-control-sm"
                       value="{{ request('tanggal_dari') }}">
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <label class="form-label mb-1">Sampai</label>
                <input type="date" name="tanggal_sampai" class="form-control form-control-sm"
                       value="{{ request('tanggal_sampai') }}">
            </div>
            @if(auth()->user()->isAdmin())
            <div class="col-12 col-sm-4 col-md-3">
                <label class="form-label mb-1">Operator</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">Semua Operator</option>
                    @foreach($operators as $op)
                    <option value="{{ $op->id }}" {{ request('user_id')==$op->id?'selected':'' }}>
                        {{ $op->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-12 col-md d-flex gap-2 flex-wrap align-items-end">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="{{ route('eviden.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                <a href="{{ route('eviden.create') }}" class="btn btn-success btn-sm ms-auto">
                    <i class="bi bi-plus-lg me-1"></i>Tambah Eviden
                </a>
            </div>
        </form>
    </div>
</div>

@if($evidens->isNotEmpty())
<div class="row g-3">
    @foreach($evidens as $ev)
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card h-100">
            {{-- Foto utama --}}
            @if($ev->fotos->isNotEmpty())
            <div style="height:160px;overflow:hidden;border-radius:10px 10px 0 0;
                 background:#f0f4f8;cursor:zoom-in;position:relative"
                 onclick="bukaLightbox('{{ $ev->fotos->first()->url }}','{{ addslashes($ev->judul) }}')">
                <img src="{{ $ev->fotos->first()->url }}"
                     onerror="this.onerror=null;this.parentElement.innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:100%;color:#aaa\'><i class=\'bi bi-image\' style=\'font-size:2rem\'></i></div>'"
                     style="width:100%;height:100%;object-fit:cover;display:block;transition:transform .2s"
                     onmouseover="this.style.transform='scale(1.03)'"
                     onmouseout="this.style.transform='scale(1)'">
                @if($ev->fotos->count()>1)
                <span style="position:absolute;bottom:6px;right:6px;
                      background:rgba(0,0,0,.6);color:#fff;font-size:.65rem;
                      border-radius:10px;padding:.15rem .5rem">
                    <i class="bi bi-images me-1"></i>{{ $ev->fotos->count() }}
                </span>
                @endif
            </div>
            @else
            <div style="height:80px;background:#f0f4f8;border-radius:10px 10px 0 0;
                 display:flex;align-items:center;justify-content:center;color:#ccc">
                <i class="bi bi-camera" style="font-size:2rem"></i>
            </div>
            @endif

            <div class="card-body pb-2">
                <div class="fw-bold mb-1" style="font-size:.9rem;line-height:1.3">{{ $ev->judul }}</div>
                <div class="d-flex flex-wrap gap-2 mb-2" style="font-size:.72rem;color:#636e72">
                    <span><i class="bi bi-calendar3 me-1"></i>{{ \Carbon\Carbon::parse($ev->tanggal)->format('d/m/Y') }}</span>
                    <span><i class="bi bi-clock me-1"></i>{{ $ev->jam_mulai }}–{{ $ev->jam_selesai }}</span>
                    <span class="badge bg-light text-dark border" style="font-size:.65rem">{{ $ev->durasi }}</span>
                </div>
                @if($ev->lokasi)
                <div style="font-size:.74rem;color:#636e72" class="mb-1">
                    <i class="bi bi-geo-alt me-1 text-danger"></i>{{ $ev->lokasi }}
                </div>
                @endif
                @if($ev->deskripsi)
                <div style="font-size:.78rem;color:#555;line-height:1.4" class="mb-2">
                    {{ Str::limit($ev->deskripsi, 75) }}
                </div>
                @endif
                @if($ev->operators->isNotEmpty())
                <div class="d-flex flex-wrap gap-1 mb-1">
                    @foreach($ev->operators->take(3) as $op)
                    <span class="badge" style="background:var(--primary);font-size:.6rem">
                        {{ $op->name }}
                    </span>
                    @endforeach
                    @if($ev->operators->count() > 3)
                    <span class="badge bg-secondary" style="font-size:.6rem">+{{ $ev->operators->count()-3 }}</span>
                    @endif
                </div>
                @endif
                @if($ev->supervisi)
                <div style="font-size:.7rem;color:#636e72">
                    <i class="bi bi-person-badge me-1"></i>{{ $ev->supervisi }}
                </div>
                @endif
            </div>

            <div class="card-footer d-flex gap-2 pt-2">
                <a href="{{ route('eviden.show',$ev) }}" class="btn btn-sm btn-outline-primary flex-fill">
                    <i class="bi bi-eye me-1"></i>Detail
                </a>
                @if(auth()->user()->isAdmin() || auth()->id()===$ev->user_id)
                <a href="{{ route('eviden.edit',$ev) }}" class="btn btn-sm btn-outline-warning">
                    <i class="bi bi-pencil"></i>
                </a>
                <form action="{{ route('eviden.destroy',$ev) }}" method="POST"
                      onsubmit="return confirm('Hapus eviden ini?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@if($evidens->hasPages())
<div class="mt-3 d-flex justify-content-center">{{ $evidens->links() }}</div>
@endif
@else
<div class="card text-center py-5 text-muted">
    <i class="bi bi-camera fs-1 d-block mb-2"></i>
    Belum ada catatan eviden.
    <div class="mt-2">
        <a href="{{ route('eviden.create') }}" class="btn btn-success btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Tambah Eviden Pertama
        </a>
    </div>
</div>
@endif
@endsection
