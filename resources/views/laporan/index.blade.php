@extends('layouts.app')
@section('title','Generate Laporan')
@section('page-title','Generate Laporan Operasional')

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-xl-9">

<div class="card">
    <div class="card-header">
        <i class="bi bi-file-earmark-pdf text-danger me-2"></i>
        <strong>Generate Laporan Operasional Pemancar</strong>
    </div>
    <div class="card-body">
    <form action="{{ route('laporan.generate') }}" method="POST" target="_blank">
    @csrf

    <h6 class="section-title mb-3">📅 Periode Laporan</h6>
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <label class="form-label">Dari Tanggal <span class="text-danger">*</span></label>
            <input type="date" name="tanggal_dari" class="form-control"
                   value="{{ old('tanggal_dari', now()->startOfMonth()->toDateString()) }}" required>
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Sampai Tanggal <span class="text-danger">*</span></label>
            <input type="date" name="tanggal_sampai" class="form-control"
                   value="{{ old('tanggal_sampai', now()->toDateString()) }}" required>
        </div>
    </div>

    <h6 class="section-title mb-3">🔍 Filter Data</h6>
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <label class="form-label">Lokasi Pemancar</label>
            <select name="lokasi" class="form-select" id="selLokasi">
                <option value="">Semua Lokasi</option>
                @foreach($semuaLokasi as $lok)
                <option value="{{ $lok }}" {{ old('lokasi')===$lok?'selected':'' }}>{{ $lok }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label">Pemancar</label>
            <select name="pemancar_id" class="form-select">
                <option value="">Semua Pemancar</option>
                @foreach($pemancars as $p)
                <option value="{{ $p->id }}" data-lokasi="{{ $p->lokasi }}"
                    {{ old('pemancar_id')==$p->id?'selected':'' }}>
                    {{ $p->nama_stasiun }}{{ $p->lokasi ? ' ('.$p->lokasi.')':'' }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label">Operator</label>
            <select name="user_id" class="form-select">
                <option value="">Semua Operator</option>
                @foreach($operators as $op)
                <option value="{{ $op->id }}" {{ old('user_id')==$op->id?'selected':'' }}>
                    {{ $op->name }}{{ $op->nip ? ' ('.$op->nip.')':'' }}
                </option>
                @endforeach
            </select>
        </div>
    </div>

    <h6 class="section-title mb-3">✍️ Penandatangan</h6>
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <label class="form-label">Dibuat Oleh (Operator)</label>
            <input type="text" name="generated_by_custom" class="form-control"
                   value="{{ old('generated_by_custom', auth()->user()->name) }}"
                   placeholder="Nama operator yang membuat laporan">
            <div class="form-text">Nama yang akan tertera di kolom "Dibuat Oleh"</div>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Mengetahui (Pengelola / Koordinator)</label>
            <input type="text" name="pengelola" class="form-control"
                   value="{{ old('pengelola') }}"
                   placeholder="Nama pengelola/koordinator">
            <div class="form-text">Nama yang akan tertera di kolom "Mengetahui"</div>
        </div>
    </div>

    <h6 class="section-title mb-3">📄 Format Output</h6>
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="d-flex gap-4 flex-wrap">
                <div class="form-check">
                    <input type="radio" name="format" value="pdf" id="fmtPdf" class="form-check-input" checked>
                    <label for="fmtPdf" class="form-check-label">
                        <i class="bi bi-file-earmark-pdf text-danger me-1"></i>PDF (Download)
                    </label>
                </div>
                <div class="form-check">
                    <input type="radio" name="format" value="view" id="fmtView" class="form-check-input">
                    <label for="fmtView" class="form-check-label">
                        <i class="bi bi-eye me-1"></i>Tampilkan di Browser
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        <button type="submit" class="btn btn-danger">
            <i class="bi bi-file-earmark-pdf me-1"></i>Generate Laporan
        </button>
        <a href="{{ route('laporan.suhu') }}" class="btn btn-outline-primary">
            <i class="bi bi-thermometer-half me-1"></i>Laporan Suhu Bulanan
        </a>
    </div>

    </form>
    </div>
</div>

{{-- Shortcut periode --}}
<div class="card mt-3">
    <div class="card-header"><i class="bi bi-lightning me-2"></i>Shortcut Periode</div>
    <div class="card-body d-flex flex-wrap gap-2">
        @php
        $presets = [
            'Hari Ini'         => [now()->toDateString(), now()->toDateString()],
            'Minggu Ini'       => [now()->startOfWeek()->toDateString(), now()->toDateString()],
            'Bulan Ini'        => [now()->startOfMonth()->toDateString(), now()->toDateString()],
            'Bulan Lalu'       => [now()->subMonth()->startOfMonth()->toDateString(), now()->subMonth()->endOfMonth()->toDateString()],
            '3 Bulan Terakhir' => [now()->subMonths(3)->startOfMonth()->toDateString(), now()->toDateString()],
        ];
        @endphp
        @foreach($presets as $label => [$dari,$sampai])
        <button type="button" class="btn btn-sm btn-outline-secondary"
                onclick="setPreset('{{ $dari }}','{{ $sampai }}')">{{ $label }}</button>
        @endforeach
    </div>
</div>

</div>
</div>
@endsection

@push('styles')
<style>.section-title{font-weight:700;color:#0a3d62;border-left:4px solid #0a3d62;padding-left:.65rem;font-size:.85rem;}</style>
@endpush

@push('scripts')
<script>
function setPreset(dari, sampai){
    document.querySelector('[name=tanggal_dari]').value  = dari;
    document.querySelector('[name=tanggal_sampai]').value = sampai;
}

// Filter dropdown pemancar saat lokasi dipilih
document.getElementById('selLokasi').addEventListener('change', function(){
    const lok = this.value;
    document.querySelectorAll('[name=pemancar_id] option').forEach(opt => {
        if (!opt.value) return;
        opt.style.display = (!lok || opt.dataset.lokasi === lok) ? '' : 'none';
    });
    document.querySelector('[name=pemancar_id]').value = '';
});
</script>
@endpush
