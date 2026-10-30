@extends('layouts.app')
@section('title','Detail Logbook Sarana')
@section('page-title','Detail Logbook Sarana & Prasarana')
@section('content')
<div class="row justify-content-center"><div class="col-12 col-lg-9">
@if(session('warning'))<div class="alert alert-warning py-2">{{ session('warning') }}</div>@endif
@if($errors->any())<div class="alert alert-danger py-2">@foreach($errors->all() as $e)<div style="font-size:.82rem">{{ $e }}</div>@endforeach</div>@endif

<div class="card mb-3">
    <div class="card-header d-flex align-items-start flex-wrap gap-2">
        <div class="flex-grow-1">
            <h6 class="mb-1"><i class="bi bi-journal-text me-2"></i>{{ $log->tanggal->translatedFormat('l, d F Y') }} &mdash; {{ $log->shift_label }}</h6>
            <div class="text-muted" style="font-size:.78rem">
                {{ substr($log->jam_mulai,0,5) }}{{ $log->jam_selesai ? ' - '.substr($log->jam_selesai,0,5) : '' }}
                &nbsp;|&nbsp; Petugas: <strong>{{ $log->user->name }}</strong>
                &nbsp;{!! $log->ada_kendala ? '<span class="badge bg-danger">Ada Kendala</span>' : '<span class="badge bg-success">Semua Normal</span>' !!}
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('sarana.logbook.cetak', $log) }}" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
            @if($bisaUbah)
            <a href="{{ route('sarana.logbook.edit', $log) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil me-1"></i>Edit</a>
            <form action="{{ route('sarana.logbook.destroy', $log) }}" method="POST" onsubmit="return confirm('Hapus logbook ini beserta fotonya?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
            @endif
            <a href="{{ route('sarana.logbook.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table mb-0" style="font-size:.83rem">
            <thead style="background:#f8f9fa"><tr><th class="ps-3" style="width:45%">Log Harian</th><th style="width:15%">Aksi</th><th>Keterangan</th></tr></thead>
            <tbody>
            @foreach($log->items as $it)
            <tr style="{{ $it->kondisi === 'kendala' ? 'background:#fff5f5' : '' }}">
                <td class="ps-3 py-2"><div class="fw-semibold">{{ $it->label }}</div>@if($it->deskripsi)<div class="text-muted" style="font-size:.72rem">*{{ $it->deskripsi }}</div>@endif</td>
                <td class="py-2" style="color:{{ $it->kondisi === 'kendala' ? '#e74c3c' : '#27ae60' }};font-weight:600">{{ $it->teks_kondisi }}</td>
                <td class="py-2" style="white-space:pre-wrap">{{ $it->keterangan ?: '-' }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>
    </div>
</div>

<div class="card mb-3" style="border-left:4px solid #f39c12">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Catatan Petugas Dinas <small class="text-muted">(info untuk petugas berikutnya)</small></h6></div>
    <div class="card-body"><div style="white-space:pre-wrap;word-break:break-word;font-size:.9rem;line-height:1.6">{{ $log->catatan_petugas ?: '-' }}</div></div>
</div>

@if($log->fotos->isNotEmpty())
<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-images me-2"></i>Foto ({{ $log->fotos->count() }})</h6></div>
    <div class="card-body"><div class="row g-3">
        @foreach($log->fotos as $f)
        <div class="col-6 col-md-4">
            <div style="border:1px solid #dee2e6;border-radius:8px;overflow:hidden;height:100%">
                <div style="aspect-ratio:4/3;background:#f4f6f8">
                    <img src="{{ $f->thumb_url }}" loading="lazy" alt="" style="width:100%;height:100%;object-fit:cover;cursor:zoom-in"
                         onclick="bukaLightbox('{{ $f->url }}', {{ json_encode((string) $f->keterangan) }})"
                         onerror="this.onerror=null;this.src='{{ $f->url }}'">
                </div>
                <div style="font-size:.78rem;padding:.4rem .55rem;color:#444">{{ $f->keterangan ?: 'Foto '.$loop->iteration }}</div>
            </div>
        </div>
        @endforeach
    </div></div>
</div>
@endif
</div></div>
@endsection
