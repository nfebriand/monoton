@extends('layouts.app')
@section('title','Detail Maintenance Studio')
@section('page-title','Detail Maintenance Perangkat Studio')

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-8">
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-tools me-2"></i>{{ $studioMaintenance->perangkat->nama }}</h6>
        <span class="badge {{ $studioMaintenance->hasil=='selesai'?'bg-success':($studioMaintenance->hasil=='sebagian'?'bg-warning':'bg-secondary') }}">{{ $studioMaintenance->hasil_label }}</span>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-5 col-md-4" style="font-size:.82rem">Tanggal</dt>
            <dd class="col-7 col-md-8 mono" style="font-size:.82rem">{{ $studioMaintenance->tanggal->format('d/m/Y') }}</dd>

            <dt class="col-5 col-md-4" style="font-size:.82rem">Jenis</dt>
            <dd class="col-7 col-md-8" style="font-size:.82rem"><span class="badge bg-info">{{ $studioMaintenance->jenis_label }}</span></dd>

            <dt class="col-5 col-md-4" style="font-size:.82rem">Uraian Pekerjaan</dt>
            <dd class="col-7 col-md-8" style="font-size:.82rem">{{ $studioMaintenance->uraian_pekerjaan }}</dd>

            <dt class="col-5 col-md-4" style="font-size:.82rem">Hasil</dt>
            <dd class="col-7 col-md-8" style="font-size:.82rem">{{ $studioMaintenance->hasil_label }}</dd>

            <dt class="col-5 col-md-4" style="font-size:.82rem">Biaya</dt>
            <dd class="col-7 col-md-8 mono" style="font-size:.82rem">{{ $studioMaintenance->biaya ? 'Rp '.number_format($studioMaintenance->biaya,0,',','.') : '-' }}</dd>

            @if($studioMaintenance->sparepart_terpakai)
            <dt class="col-5 col-md-4" style="font-size:.82rem">Sparepart</dt>
            <dd class="col-7 col-md-8" style="font-size:.82rem">
                <ul class="ps-3 mb-0">
                    @foreach($studioMaintenance->sparepart_terpakai as $sp)
                    <li>{{ $sp['nama'] }} — {{ $sp['jumlah'] }} {{ $sp['satuan'] }}</li>
                    @endforeach
                </ul>
            </dd>
            @endif

            <dt class="col-5 col-md-4" style="font-size:.82rem">Rencana Berikutnya</dt>
            <dd class="col-7 col-md-8 mono" style="font-size:.82rem">{{ $studioMaintenance->rencana_maintenance_berikutnya ? $studioMaintenance->rencana_maintenance_berikutnya->format('d/m/Y') : '-' }}</dd>

            <dt class="col-5 col-md-4" style="font-size:.82rem">Teknisi</dt>
            <dd class="col-7 col-md-8" style="font-size:.82rem">{{ $studioMaintenance->user->name }}</dd>

            @if($studioMaintenance->keterangan)
            <dt class="col-5 col-md-4" style="font-size:.82rem">Keterangan</dt>
            <dd class="col-7 col-md-8" style="font-size:.82rem">{{ $studioMaintenance->keterangan }}</dd>
            @endif
        </dl>
    </div>
    <div class="card-footer d-flex gap-2">
        @if(auth()->user()->isAdmin() || (auth()->user()->isAdminDivisi() && auth()->user()->isDivisi('studio')))
        <form method="POST" action="{{ route('studio.maintenance.destroy',$studioMaintenance) }}" onsubmit="return confirm('Hapus log ini?')">
            @csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Hapus</button>
        </form>
        @endif
        <a href="{{ route('studio.maintenance.index') }}" class="btn btn-sm btn-outline-secondary ms-auto"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
    </div>
</div>
</div>
</div>
@endsection
