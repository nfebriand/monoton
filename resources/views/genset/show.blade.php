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
    @if(auth()->user()->isAdmin() || auth()->id()===$genset->user_id)
    <div class="d-flex gap-2 ms-auto">
        <a href="{{ route('genset.edit',$genset) }}" class="btn btn-sm btn-outline-warning">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        @if(auth()->user()->isAdmin())
        <form action="{{ route('genset.destroy',$genset) }}" method="POST" onsubmit="return confirm('Hapus log ini?')">
            @csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Hapus</button>
        </form>
        @endif
    </div>
    @endif
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
                            {{ substr($genset->jam_mulai,0,5) }}{{ $genset->jam_selesai ? ' – '.substr($genset->jam_selesai,0,5) : ' (berjalan)' }}
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
                        <div class="fw-bold">{{ $genset->gensetUnit->lokasi ?? '–' }}</div>
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

        {{-- HM & BBM --}}
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <div class="card">
                    <div class="card-header"><i class="bi bi-speedometer me-2 text-success"></i>Hour Meter</div>
                    <div class="card-body">
                        <div class="row g-2 text-center">
                            <div class="col-4">
                                <div class="text-muted" style="font-size:.65rem">HM AWAL</div>
                                <div class="mono fw-bold">{{ $genset->hm_awal !== null ? number_format($genset->hm_awal,1) : '–' }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted" style="font-size:.65rem">HM AKHIR</div>
                                <div class="mono fw-bold">{{ $genset->hm_akhir !== null ? number_format($genset->hm_akhir,1) : '–' }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted" style="font-size:.65rem">JAM OPERASI</div>
                                <div class="mono fw-bold" style="color:var(--primary)">
                                    {{ $genset->jam_operasi_hm !== null ? number_format($genset->jam_operasi_hm,1) : '–' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="card">
                    <div class="card-header"><i class="bi bi-fuel-pump me-2 text-warning"></i>Bahan Bakar</div>
                    <div class="card-body">
                        <div class="row g-2 text-center mb-2">
                            <div class="col-4">
                                <div class="text-muted" style="font-size:.65rem">AWAL</div>
                                <div class="mono fw-bold">{{ $genset->bbm_awal !== null ? number_format($genset->bbm_awal,1).'L' : '–' }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted" style="font-size:.65rem">ISI</div>
                                <div class="mono fw-bold" style="color:var(--success)">{{ $genset->bbm_isi ? '+'.number_format($genset->bbm_isi,1).'L' : '–' }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted" style="font-size:.65rem">AKHIR</div>
                                <div class="mono fw-bold">{{ $genset->bbm_akhir !== null ? number_format($genset->bbm_akhir,1).'L' : '–' }}</div>
                            </div>
                        </div>
                        @if($genset->pemakaian_bbm !== null)
                        <div class="text-center p-2 rounded" style="background:var(--bg)">
                            <div class="text-muted" style="font-size:.65rem">TOTAL PEMAKAIAN</div>
                            <div class="mono fw-bold" style="font-size:1.1rem;color:var(--warning)">{{ number_format($genset->pemakaian_bbm,1) }} L</div>
                            @if($genset->rasio_bbm_per_jam)
                            <div class="text-muted" style="font-size:.65rem">≈ {{ number_format($genset->rasio_bbm_per_jam,2) }} L/jam</div>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
            </div>
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
                        <div class="fw-bold">{{ $genset->gensetUnit->merk ?? '–' }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size:.7rem">Tipe</div>
                        <div class="fw-bold">{{ $genset->gensetUnit->tipe ?? '–' }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size:.7rem">Kapasitas</div>
                        <div class="fw-bold mono">{{ $genset->gensetUnit->kapasitas_kva ? $genset->gensetUnit->kapasitas_kva.' kVA' : '–' }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size:.7rem">Tangki BBM</div>
                        <div class="fw-bold mono">{{ $genset->gensetUnit->kapasitas_tangki_liter ? number_format($genset->gensetUnit->kapasitas_tangki_liter,0).' L' : '–' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
