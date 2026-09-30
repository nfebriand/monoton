@extends('layouts.app')
@section('title','Detail Logbook Studio')
@section('page-title','Detail Logbook Studio')

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-9">

{{-- Header info --}}
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h6 class="mb-0">
                <i class="bi bi-journal-text me-2"></i>
                Logbook {{ ucfirst($studioLog->shift) }}   {{ $studioLog->tanggal->translatedFormat('l, d F Y') }}
            </h6>
            <small class="text-muted">
                {{ substr($studioLog->jam_mulai,0,5) }}{{ $studioLog->jam_selesai?'   '.substr($studioLog->jam_selesai,0,5):'' }}
                &nbsp;|&nbsp; Petugas: <strong>{{ $studioLog->user->name }}</strong>
            </small>
        </div>
        @if($studioLog->ada_gangguan)
        <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>Ada Gangguan</span>
        @else
        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Semua Normal</span>
        @endif
    </div>
</div>

{{-- Checklist --}}
<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>Checklist Kondisi Perangkat</h6></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0" style="font-size:.83rem">
                <thead style="background:#f8f9fa">
                    <tr>
                        <th class="ps-3" style="width:42%">Item Pemeriksaan</th>
                        <th style="width:15%">Aksi</th>
                        <th>Deskripsi / Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(\App\Models\StudioLog::CHECKLIST_ITEMS as $key => $item)
                    @php $kondisi = $studioLog->$key; $ket = $studioLog->{$key.'_ket'}; @endphp
                    <tr style="{{ $kondisi==='gangguan'?'background:#fff5f5':'' }}">
                        <td class="ps-3 py-2">
                            <div class="fw-semibold">{{ $item['label'] }}</div>
                            @if($item['desc'])<div class="text-muted" style="font-size:.72rem">*{{ $item['desc'] }}</div>@endif
                        </td>
                        <td class="py-2">
                            @if($kondisi === 'baik')
                                <span style="color:#27ae60;font-weight:600">Baik</span>
                            @elseif($kondisi === 'gangguan')
                                <span style="color:#e74c3c;font-weight:600">Gangguan</span>
                            @else
                                <span style="color:#888">N/A</span>
                            @endif
                        </td>
                        <td class="py-2">{{ $ket ?: '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Catatan Petugas --}}
<div class="card mb-3" style="border-color:#f39c12">
    <div class="card-header" style="background:#fff3cd">
        <h6 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Catatan Petugas Dinas</h6>
    </div>
    <div class="card-body" style="font-size:.85rem">
        {{ $studioLog->catatan_petugas ?: '(Tidak ada catatan)' }}
    </div>
</div>

{{-- Foto Dokumentasi --}}
@if($studioLog->fotos->count())
<div class="card mb-3">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-camera me-2"></i>Foto Dokumentasi ({{ $studioLog->fotos->count() }} foto)</h6>
    </div>
    <div class="card-body">
        <div class="row g-2">
            @foreach($studioLog->fotos as $foto)
            <div class="col-6 col-md-3">
                <img src="{{ $foto->thumb_url }}"
                     class="img-fluid rounded"
                     style="width:100%;height:120px;object-fit:cover;cursor:pointer"
                     onclick="openLightbox('{{ $foto->url }}')"
                     onerror="this.src='{{ asset('images/no-image.png') }}'">
                @if($foto->keterangan)
                <div style="font-size:.68rem;color:#666;margin-top:2px">{{ $foto->keterangan }}</div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- Tombol Aksi --}}
<div class="d-flex gap-2">
    @if(auth()->user()->isAdmin() || auth()->user()->isAdminDivisi() || auth()->id()===$studioLog->user_id)
    <a href="{{ route('studio.logbook.edit',$studioLog) }}" class="btn btn-outline-warning"><i class="bi bi-pencil me-1"></i>Edit</a>
    <form method="POST" action="{{ route('studio.logbook.destroy',$studioLog) }}" onsubmit="return confirm('Hapus logbook ini?')">
        @csrf @method('DELETE')
        <button class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>Hapus</button>
    </form>
    @endif
    <a href="{{ route('studio.logbook.cetak-harian',['tanggal'=>$studioLog->tanggal->toDateString(),'user_id'=>$studioLog->user_id]) }}" class="btn btn-outline-secondary" target="_blank">
        <i class="bi bi-printer me-1"></i>Cetak PDF
    </a>
    <a href="{{ route('studio.logbook.index') }}" class="btn btn-outline-secondary ms-auto"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</div>

{{-- Lightbox --}}
<div id="foto-lightbox" onclick="this.style.display='none'"
     style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;
            background:rgba(0,0,0,.85);z-index:9999;align-items:center;justify-content:center;cursor:zoom-out">
    <img id="lightbox-img" src="" style="max-width:95%;max-height:90vh;border-radius:8px">
</div>

</div>
</div>
@push('scripts')
<script>
function openLightbox(src) {
    const lb = document.getElementById('foto-lightbox');
    document.getElementById('lightbox-img').src = src;
    lb.style.display = 'flex';
}
</script>
@endpush
@endsection
