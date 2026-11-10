@extends('layouts.app')
@section('title','Detail Operasional Genset')
@section('page-title','Detail Operasional Genset')

@section('content')
<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    <a href="{{ route('genset.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
    <span class="fw-bold">{{ $genset->gensetUnit->nama_unit }}</span>
    <span class="badge {{ $genset->kondisi=='normal'?'bg-success':'bg-danger' }}">
        {{ $genset->kondisi=='normal'?'Normal':'Gangguan' }}
    </span>
    @can('genset.log.ubah', $genset)
    <div class="d-flex gap-2 ms-auto">
        <a href="{{ route('genset.edit',$genset) }}" class="btn btn-sm btn-outline-warning">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        @can('genset.log.hapus')
        <form action="{{ route('genset.destroy',$genset) }}" method="POST" onsubmit="return confirm('Hapus log ini?')">
            @csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Hapus</button>
        </form>
        @endcan
    </div>
    @endcan
</div>

<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-info-circle me-2 text-primary"></i>Informasi Operasional</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6 col-md-4">
                        <div class="text-muted" style="font-size:.7rem">Tanggal</div>
                        <div class="fw-bold mono">{{ $genset->tanggal->translatedFormat('d F Y') }}</div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="text-muted" style="font-size:.7rem">Jam Operasi</div>
                        <div class="fw-bold mono">
                            {{ substr($genset->jam_mulai,0,5) }}{{ $genset->jam_selesai ? ' '."\u{2013}".' '.substr($genset->jam_selesai,0,5) : ' (berjalan)' }}
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="text-muted" style="font-size:.7rem">Alasan</div>
                        <div class="fw-bold"><span class="badge" style="background:#7b1fa2">{{ $genset->alasan_label }}</span></div>
                    </div>
                    @if($genset->durasi)
                    <div class="col-6 col-md-4">
                        <div class="text-muted" style="font-size:.7rem">Durasi</div>
                        <div class="fw-bold mono">{{ $genset->durasi }}</div>
                    </div>
                    @endif
                    <div class="col-6 col-md-4">
                        <div class="text-muted" style="font-size:.7rem">Lokasi</div>
                        <div class="fw-bold">{{ $genset->gensetUnit->lokasi ?? ''."\u{2013}".'' }}</div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="text-muted" style="font-size:.7rem">Dicatat Oleh</div>
                        <div class="fw-bold">{{ $genset->user->name }}</div>
                    </div>
                </div>

                @if($genset->keterangan)
                <div class="mt-3 p-3 rounded" style="background:var(--bg);border:1px solid var(--border);
                     border-left:4px solid {{ $genset->kondisi=='gangguan'?'var(--danger)':'var(--primary)' }};font-size:.85rem;line-height:1.6">
                    {{ $genset->keterangan }}
                </div>
                @endif
            </div>
        </div>

        {{-- Pengisian BBM (data HM / BBM awal-akhir lama tetap tampil bila ada) --}}
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <div class="card">
                    <div class="card-header"><i class="bi bi-fuel-pump me-2 text-warning"></i>Pengisian BBM</div>
                    <div class="card-body text-center">
                        <div class="text-muted" style="font-size:.65rem">PENGISIAN</div>
                        <div class="mono fw-bold" style="font-size:1.3rem;color:var(--success)">{{ $genset->bbm_isi ? '+'.number_format($genset->bbm_isi,1).' L' : "\u{2013}" }}</div>
                    </div>
                </div>
            </div>
            @if($genset->hm_awal !== null || $genset->hm_akhir !== null || $genset->bbm_awal !== null || $genset->bbm_akhir !== null)
            <div class="col-12 col-md-6">
                <div class="card">
                    <div class="card-header"><i class="bi bi-archive me-2 text-muted"></i>Data Lama (arsip)</div>
                    <div class="card-body" style="font-size:.78rem">
                        @if($genset->hm_awal !== null || $genset->hm_akhir !== null)
                        <div>HM: {{ $genset->hm_awal !== null ? number_format($genset->hm_awal,1) : "\u{2013}" }} &rarr; {{ $genset->hm_akhir !== null ? number_format($genset->hm_akhir,1) : "\u{2013}" }}</div>
                        @endif
                        @if($genset->bbm_awal !== null || $genset->bbm_akhir !== null)
                        <div>BBM: {{ $genset->bbm_awal !== null ? number_format($genset->bbm_awal,1).' L' : "\u{2013}" }} &rarr; {{ $genset->bbm_akhir !== null ? number_format($genset->bbm_akhir,1).' L' : "\u{2013}" }}</div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Info Unit --}}
    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-battery-charging me-2 text-primary"></i>Unit Genset</div>
            <div class="card-body">
                <h6 class="fw-bold">{{ $genset->gensetUnit->nama_unit }}</h6>
                <div class="row g-2 mt-2">
                    <div class="col-6">
                        <div class="text-muted" style="font-size:.7rem">Merk</div>
                        <div class="fw-bold">{{ $genset->gensetUnit->merk ?? ''."\u{2013}".'' }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size:.7rem">Tipe</div>
                        <div class="fw-bold">{{ $genset->gensetUnit->tipe ?? ''."\u{2013}".'' }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size:.7rem">Kapasitas</div>
                        <div class="fw-bold mono">{{ $genset->gensetUnit->kapasitas_kva ? $genset->gensetUnit->kapasitas_kva.' kVA' : ''."\u{2013}".'' }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size:.7rem">Tangki BBM</div>
                        <div class="fw-bold mono">{{ $genset->gensetUnit->kapasitas_tangki_liter ? number_format($genset->gensetUnit->kapasitas_tangki_liter,0).' L' : ''."\u{2013}".'' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
