@extends('layouts.app')
@section('title','Edit Operasional Genset')
@section('page-title','Edit Operasional Genset')

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-8">
<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-pencil-square text-warning"></i>Edit Operasional Genset
        <a href="{{ route('genset.show',$genset) }}" class="btn btn-sm btn-outline-secondary ms-auto">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
    <div class="card-body">
    <form action="{{ route('genset.update',$genset) }}" method="POST">
    @csrf @method('PUT')

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <label class="form-label">Unit Genset <span class="text-danger">*</span></label>
            <select name="genset_unit_id" class="form-select" required>
                @foreach($units as $u)
                <option value="{{ $u->id }}" {{ old('genset_unit_id',$genset->genset_unit_id)==$u->id?'selected':'' }}>
                    {{ $u->nama_unit }}{{ $u->lokasi ? ' — '.$u->lokasi : '' }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Alasan Penyalaan <span class="text-danger">*</span></label>
            <select name="alasan" class="form-select" required>
                @foreach(\App\Models\GensetLog::ALASAN_LABEL as $val=>$label)
                <option value="{{ $val }}" {{ old('alasan',$genset->alasan)==$val?'selected':'' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label">Tanggal <span class="text-danger">*</span></label>
            <input type="date" name="tanggal" class="form-control" value="{{ old('tanggal',$genset->tanggal->format('Y-m-d')) }}" required>
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Jam Mulai <span class="text-danger">*</span></label>
            <input type="time" name="jam_mulai" class="form-control mono" value="{{ old('jam_mulai',substr($genset->jam_mulai,0,5)) }}" required>
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Jam Selesai</label>
            <input type="time" name="jam_selesai" class="form-control mono" value="{{ old('jam_selesai',$genset->jam_selesai?substr($genset->jam_selesai,0,5):'') }}">
        </div>
    </div>

    <h6 class="section-title mb-3">⏱️ Hour Meter (HM)</h6>
    <div class="row g-3 mb-4">
        <div class="col-6">
            <label class="form-label">HM Awal (jam)</label>
            <input type="number" step="0.1" name="hm_awal" class="form-control mono" value="{{ old('hm_awal',$genset->hm_awal) }}">
        </div>
        <div class="col-6">
            <label class="form-label">HM Akhir (jam)</label>
            <input type="number" step="0.1" name="hm_akhir" class="form-control mono" value="{{ old('hm_akhir',$genset->hm_akhir) }}">
        </div>
    </div>

    <h6 class="section-title mb-3">⛽ Bahan Bakar (Liter)</h6>
    <div class="row g-3 mb-4">
        <div class="col-4">
            <label class="form-label">BBM Awal</label>
            <input type="number" step="0.1" name="bbm_awal" class="form-control mono" value="{{ old('bbm_awal',$genset->bbm_awal) }}">
        </div>
        <div class="col-4">
            <label class="form-label">Pengisian BBM</label>
            <input type="number" step="0.1" name="bbm_isi" class="form-control mono" value="{{ old('bbm_isi',$genset->bbm_isi) }}">
        </div>
        <div class="col-4">
            <label class="form-label">BBM Akhir</label>
            <input type="number" step="0.1" name="bbm_akhir" class="form-control mono" value="{{ old('bbm_akhir',$genset->bbm_akhir) }}">
        </div>
    </div>

    <h6 class="section-title mb-3">📋 Kondisi & Keterangan</h6>
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <label class="form-label">Kondisi Mesin <span class="text-danger">*</span></label>
            <select name="kondisi" class="form-select" required>
                <option value="normal" {{ old('kondisi',$genset->kondisi)=='normal'?'selected':'' }}>Normal</option>
                <option value="gangguan" {{ old('kondisi',$genset->kondisi)=='gangguan'?'selected':'' }}>Ada Gangguan</option>
            </select>
        </div>
        <div class="col-12 col-md-8">
            <label class="form-label">Keterangan</label>
            <input type="text" name="keterangan" class="form-control" value="{{ old('keterangan',$genset->keterangan) }}">
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-save me-1"></i>Simpan Perubahan</button>
        <a href="{{ route('genset.show',$genset) }}" class="btn btn-outline-secondary">Batal</a>
    </div>
    </form>
    </div>
</div>
</div>
</div>
@endsection
