@extends('layouts.app')
@section('title','Dashboard Studio')
@section('page-title','Dashboard Studio')

@section('content')
{{-- Statistik --}}
<div class="row g-2 mb-3">
    <div class="col-4">
        <div class="card text-center">
            <div class="card-body py-2">
                <div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Hari Ini</div>
                <div class="mono fw-bold" style="font-size:1.6rem;color:var(--primary)">{{ $statEviden['hari_ini'] }}</div>
                <div class="text-muted" style="font-size:.65rem">eviden</div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card text-center">
            <div class="card-body py-2">
                <div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Bulan Ini</div>
                <div class="mono fw-bold" style="font-size:1.6rem;color:var(--success)">{{ $statEviden['bulan_ini'] }}</div>
                <div class="text-muted" style="font-size:.65rem">eviden</div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card text-center">
            <div class="card-body py-2">
                <div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Total</div>
                <div class="mono fw-bold" style="font-size:1.6rem;color:var(--warning)">{{ $statEviden['total'] }}</div>
                <div class="text-muted" style="font-size:.65rem">eviden</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-camera me-2 text-primary"></i>Eviden Studio Terbaru</span>
                <a href="{{ route('eviden.create') }}" class="btn btn-sm btn-primary-custom">
                    <i class="bi bi-plus me-1"></i>Catat Eviden
                </a>
            </div>
            <div class="card-body p-0">
                @forelse($evidenTerbaru as $ev)
                <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom" style="border-color:var(--border)!important">
                    @if($ev->fotos->isNotEmpty())
                    <img src="{{ $ev->fotos->first()->url }}" style="width:44px;height:44px;border-radius:6px;object-fit:cover;flex-shrink:0"
                         onerror="this.style.display='none'">
                    @else
                    <div style="width:44px;height:44px;border-radius:6px;background:var(--bg);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--muted)">
                        <i class="bi bi-camera"></i>
                    </div>
                    @endif
                    <div style="min-width:0;flex:1">
                        <div class="fw-bold" style="font-size:.84rem">{{ Str::limit($ev->judul,50) }}</div>
                        <div class="text-muted" style="font-size:.7rem">
                            {{ $ev->tanggal->format('d/m/Y') }}
                            · {{ $ev->user->name }}
                            @if($ev->lokasi) · {{ $ev->lokasi }} @endif
                        </div>
                    </div>
                    <a href="{{ route('eviden.show',$ev) }}" class="btn btn-sm btn-outline-primary flex-shrink-0"><i class="bi bi-eye"></i></a>
                </div>
                @empty
                <div class="text-center text-muted py-5 small">
                    <i class="bi bi-camera d-block fs-1 mb-2"></i>
                    Belum ada eviden Studio. Mulai catat kegiatan!
                </div>
                @endforelse
            </div>
            <div class="card-footer py-2 text-center">
                <a href="{{ route('eviden.index') }}" style="font-size:.78rem">Lihat semua eviden Studio →</a>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        @if($jadwalHariIni->isNotEmpty())
        <div class="card">
            <div class="card-header"><i class="bi bi-calendar3 me-2 text-success"></i>Jadwal Shift Hari Ini</div>
            <div class="card-body p-0">
                @foreach($jadwalHariIni as $j)
                @php $aktif = now()->format('H:i') >= $j->jam_mulai && now()->format('H:i') < $j->jam_selesai; @endphp
                <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom" style="border-color:var(--border)!important">
                    <span class="badge" style="background:#7b1fa2">{{ $j->shift_label }}</span>
                    <span style="font-size:.84rem;font-weight:600">{{ $j->user->name }}</span>
                    @if($aktif)<span class="badge bg-success ms-auto" style="font-size:.6rem">AKTIF</span>@endif
                </div>
                @endforeach
            </div>
        </div>
        @else
        <div class="card">
            <div class="card-header"><i class="bi bi-calendar3 me-2"></i>Jadwal Hari Ini</div>
            <div class="card-body text-center text-muted py-4 small">
                <i class="bi bi-calendar-x d-block fs-2 mb-1"></i>
                Belum ada jadwal hari ini
            </div>
        </div>
        @endif

        {{-- Shortcut --}}
        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-lightning me-2 text-warning"></i>Aksi Cepat</div>
            <div class="card-body d-flex flex-column gap-2">
                <a href="{{ route('eviden.create') }}" class="btn btn-primary-custom">
                    <i class="bi bi-camera me-2"></i>Catat Eviden Baru
                </a>
                <a href="{{ route('laporan.eviden-rekap') }}" class="btn btn-outline-primary">
                    <i class="bi bi-file-earmark-pdf me-2"></i>Rekap Laporan Eviden
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
