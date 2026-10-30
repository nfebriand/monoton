@extends('layouts.app')
@section('title','Detail Catatan Dinas')
@section('page-title','Detail Catatan Dinas')

@section('content')
<div class="row justify-content-center"><div class="col-12 col-lg-9">
@if(session('warning'))
<div class="alert alert-warning py-2">{{ session('warning') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger py-2">@foreach($errors->all() as $e)<div style="font-size:.82rem">{{ $e }}</div>@endforeach</div>
@endif

<div class="card mb-3">
    <div class="card-header d-flex align-items-start flex-wrap gap-2">
        <div class="flex-grow-1">
            <span class="badge" style="background:{{ $c->kategori_warna }}">{{ $c->kategori_label }}</span>
            <h5 class="mb-1 mt-1">{{ $c->judul }}</h5>
            <div class="text-muted" style="font-size:.78rem">
                <i class="bi bi-calendar3 me-1"></i>{{ $c->tanggal->translatedFormat('l, d F Y') }}
                @if($c->rentang_jam) &nbsp;|&nbsp; <i class="bi bi-clock me-1"></i>{{ $c->rentang_jam }} @endif
                @if($c->lokasi) &nbsp;|&nbsp; <i class="bi bi-geo-alt me-1 text-danger"></i>{{ $c->lokasi }} @endif
                &nbsp;|&nbsp; <i class="bi bi-person me-1"></i>{{ $c->user->name }}
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('catatan-dinas.cetak', $c) }}" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
            @if($c->dapatDiubah(auth()->user()))
            <a href="{{ route('catatan-dinas.edit', $c) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil me-1"></i>Edit</a>
            <form action="{{ route('catatan-dinas.destroy', $c) }}" method="POST" onsubmit="return confirm('Hapus catatan dinas ini beserta fotonya?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
            @endif
            <a href="{{ route('catatan-dinas.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        </div>
    </div>
    <div class="card-body">
        <div style="white-space:pre-wrap;word-break:break-word;font-size:.92rem;line-height:1.65">{{ $c->narasi }}</div>
    </div>
</div>

@if($c->fotos->isNotEmpty())
<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-images me-2"></i>Foto ({{ $c->fotos->count() }})</h6></div>
    <div class="card-body">
        <div class="row g-3">
            @foreach($c->fotos as $f)
            <div class="col-6 col-md-4">
                <div style="border:1px solid #dee2e6;border-radius:8px;overflow:hidden;height:100%">
                    <div style="aspect-ratio:4/3;background:#f4f6f8">
                        <img src="{{ $f->thumb_url }}" loading="lazy" alt=""
                             style="width:100%;height:100%;object-fit:cover;cursor:zoom-in"
                             onclick="bukaLightbox('{{ $f->url }}', {{ json_encode((string) $f->keterangan) }})"
                             onerror="this.onerror=null;this.src='{{ $f->url }}'">
                    </div>
                    <div style="font-size:.78rem;padding:.4rem .55rem;color:#444">{{ $f->keterangan ?: 'Foto '.$loop->iteration }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif
</div></div>
@endsection
