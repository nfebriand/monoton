@extends('layouts.app')
@section('title','Isi Data Susulan')
@section('page-title','Isi Log Operasional Susulan')

@section('content')
@php $user = auth()->user(); @endphp

<div class="alert alert-info d-flex gap-2">
    <i class="bi bi-info-circle fs-5 flex-shrink-0"></i>
    <div style="font-size:.85rem">
        <strong>Menu ini untuk melengkapi log operasional yang terlewat belum diisi.</strong><br>
        Data disimpan atas nama Anda sendiri (<strong>{{ $user->name }}</strong>). Isi nilai parameter
        sesuai catatan asli Anda (buku log, catatan WA, dll) — bukan perkiraan. Waktu pencatatan
        di bawah hanya isian awal yang bisa Anda ubah sesuai waktu sebenarnya.
    </div>
</div>

@if($user->isOperator() && !$user->lokasi_dinas)
<div class="alert alert-danger"><strong>Lokasi dinas Anda belum diset!</strong> Hubungi administrator.</div>
@elseif($pemancars->isEmpty())
<div class="alert alert-warning"><strong>Tidak ada pemancar di lokasi Anda.</strong></div>
@else

{{-- Step 1: pilih tanggal & shift --}}
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-calendar-event me-2"></i>Pilih Tanggal &amp; Shift</div>
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label">Tanggal</label>
                <input type="date" name="tanggal" class="form-control" max="{{ now()->toDateString() }}"
                       value="{{ $tanggal }}" required>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">Shift</label>
                <select name="shift" class="form-select" required>
                    <option value="">Pilih Shift</option>
                    <option value="1" {{ $shift=='1'?'selected':'' }}>Shift 1 (00:15–07:45)</option>
                    <option value="2" {{ $shift=='2'?'selected':'' }}>Shift 2 (07:45–15:45)</option>
                    <option value="3" {{ $shift=='3'?'selected':'' }}>Shift 3 (15:45–23:45)</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <button class="btn btn-primary-custom w-100"><i class="bi bi-arrow-right-circle me-1"></i>Tampilkan Form</button>
            </div>
        </form>
    </div>
</div>

@if($checkpoints)
<form action="{{ route('operasional.backfill.store') }}" method="POST">
@csrf
<input type="hidden" name="tanggal" value="{{ $tanggal }}">
<input type="hidden" name="shift" value="{{ $shift }}">

@foreach($checkpoints as $cpIdx => $jamDefault)
<div class="card mb-3">
    <div class="card-header d-flex align-items-center gap-2 flex-wrap">
        <i class="bi bi-clock-history me-1"></i>
        <strong>Checkpoint {{ $cpIdx+1 }}</strong>
        <span class="text-muted" style="font-size:.78rem">({{ $tanggal }})</span>
    </div>
    <div class="card-body">
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <label class="form-label">Jam Pencatatan</label>
                <input type="time" name="checkpoint[{{ $cpIdx }}][jam]" class="form-control mono"
                       value="{{ old("checkpoint.$cpIdx.jam", $jamDefault) }}" required>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">Suhu Ruangan (°C)</label>
                <input type="number" step="0.1" name="checkpoint[{{ $cpIdx }}][suhu_ruangan]"
                       class="form-control mono" value="{{ old("checkpoint.$cpIdx.suhu_ruangan") }}" required>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">Kelembaban (%)</label>
                <input type="number" step="0.1" min="0" max="100" name="checkpoint[{{ $cpIdx }}][kelembaban]"
                       class="form-control mono" value="{{ old("checkpoint.$cpIdx.kelembaban") }}">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle" style="font-size:.78rem">
                <thead class="table-light">
                    <tr>
                        <th style="min-width:140px">Pemancar</th>
                        <th>Final PA (W)</th>
                        <th>Driver (W)</th>
                        <th>Exciter (W)</th>
                        <th>Reflect (W)</th>
                        <th>Reject (W)</th>
                        <th>Suhu Pmc (°C)</th>
                        <th style="min-width:160px">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pemancars as $pemancar)
                    <tr>
                        <td>
                            <input type="hidden" name="checkpoint[{{ $cpIdx }}][pemancar][{{ $pemancar->id }}][id]" value="{{ $pemancar->id }}">
                            <strong>{{ $pemancar->nama_stasiun }}</strong><br>
                            <span class="text-muted" style="font-size:.7rem">maks {{ number_format($pemancar->kapasitas_output_final,0) }} W</span>
                        </td>
                        <td><input type="number" step="0.01" min="0" class="form-control form-control-sm mono"
                               name="checkpoint[{{ $cpIdx }}][pemancar][{{ $pemancar->id }}][output_final_pa]"></td>
                        <td><input type="number" step="0.01" min="0" class="form-control form-control-sm mono"
                               name="checkpoint[{{ $cpIdx }}][pemancar][{{ $pemancar->id }}][output_driver]"></td>
                        <td><input type="number" step="0.01" min="0" class="form-control form-control-sm mono"
                               name="checkpoint[{{ $cpIdx }}][pemancar][{{ $pemancar->id }}][output_exciter]"></td>
                        <td><input type="number" step="0.01" min="0" class="form-control form-control-sm mono"
                               name="checkpoint[{{ $cpIdx }}][pemancar][{{ $pemancar->id }}][reflect_final]"></td>
                        <td><input type="number" step="0.01" min="0" class="form-control form-control-sm mono"
                               name="checkpoint[{{ $cpIdx }}][pemancar][{{ $pemancar->id }}][reject_final]"></td>
                        <td><input type="number" step="0.1" class="form-control form-control-sm mono"
                               name="checkpoint[{{ $cpIdx }}][pemancar][{{ $pemancar->id }}][suhu_pemancar]"></td>
                        <td><input type="text" class="form-control form-control-sm"
                               name="checkpoint[{{ $cpIdx }}][pemancar][{{ $pemancar->id }}][keterangan]" maxlength="500"></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="text-muted" style="font-size:.72rem">
            Kosongkan baris pemancar yang memang tidak ada catatannya pada checkpoint ini — baris kosong tidak akan disimpan.
        </div>
    </div>
</div>
@endforeach

<div class="d-flex gap-2 mb-4">
    <button type="submit" class="btn btn-primary-custom">
        <i class="bi bi-save me-1"></i>Simpan Semua Log Susulan
    </button>
    <a href="{{ route('operasional.index') }}" class="btn btn-outline-secondary">Batal</a>
</div>
</form>
@endif

@endif
@endsection
