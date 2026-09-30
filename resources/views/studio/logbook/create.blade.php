@extends('layouts.app')
@section('title','Isi Logbook Studio')
@section('page-title','Isi Logbook Harian Studio')
@section('content')
<div class="row justify-content-center"><div class="col-12 col-lg-9">
@if($errors->any())<div class="alert alert-danger py-2 mb-3"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li style="font-size:.82rem">{{ $e }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('studio.logbook.store') }}" enctype="multipart/form-data">
@csrf
<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-clock me-2"></i>Informasi Shift</h6></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                <input type="date" name="tanggal" class="form-control" value="{{ old('tanggal', now()->toDateString()) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Shift <span class="text-danger">*</span></label>
                <select name="shift" class="form-select" id="sel-shift" required>
                    @foreach(\App\Models\StudioLog::SHIFT as $val=>$label)
                    <option value="{{ $val }}" {{ old('shift','pagi')==$val?'selected':'' }}>{{ ucfirst($val) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Jam Mulai <span class="text-danger">*</span></label>
                <input type="time" name="jam_mulai" id="inp-jam-mulai" class="form-control" value="{{ old('jam_mulai','04:45') }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Jam Selesai</label>
                <input type="time" name="jam_selesai" id="inp-jam-selesai" class="form-control" value="{{ old('jam_selesai','12:15') }}">
            </div>
        </div>
        <div class="mt-2" style="font-size:.78rem;color:var(--muted)">Petugas: <strong>{{ auth()->user()->name }}</strong></div>
    </div>
</div>
<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>Checklist Kondisi Perangkat</h6></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0" style="font-size:.83rem">
                <thead style="background:#4a90d9;color:#fff">
                    <tr>
                        <th style="width:42%" class="ps-3">Item Pemeriksaan</th>
                        <th style="width:18%;text-align:center">Aksi / Kondisi</th>
                        <th>Deskripsi (Bila Ada Masalah)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(\App\Models\StudioLog::CHECKLIST_ITEMS as $key => $item)
                    <tr class="checklist-row">
                        <td class="ps-3 py-3">
                            <div class="fw-semibold">{{ $item['label'] }}</div>
                            @if($item['desc'])<div class="text-muted" style="font-size:.72rem;margin-top:2px">*{{ $item['desc'] }}</div>@endif
                        </td>
                        <td class="py-3" style="text-align:center">
                            <select name="{{ $key }}" class="form-select form-select-sm kondisi-select" required>
                                @foreach(\App\Models\StudioLog::KONDISI_OPTIONS as $val=>$label)
                                <option value="{{ $val }}" {{ old($key,'baik')==$val?'selected':'' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="py-3">
                            <textarea name="{{ $key }}_ket" class="form-control form-control-sm" rows="2" placeholder="Isi bila ada gangguan...">{{ old($key.'_ket') }}</textarea>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="card mb-3" style="border-color:#f39c12">
    <div class="card-header" style="background:#fff3cd">
        <h6 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Catatan Petugas Dinas</h6>
        <small class="text-muted" style="font-size:.72rem">Catatan berfungsi untuk menginformasikan ke petugas berikutnya</small>
    </div>
    <div class="card-body">
        <textarea name="catatan_petugas" class="form-control" rows="3" placeholder="Tulis catatan penting untuk petugas shift berikutnya...">{{ old('catatan_petugas') }}</textarea>
    </div>
</div>
<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-camera me-2"></i>Foto Dokumentasi <small class="text-muted">(opsional)</small></h6></div>
    <div class="card-body">@include('components.foto-upload')</div>
</div>
<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Simpan Logbook</button>
    <a href="{{ route('studio.logbook.index') }}" class="btn btn-outline-secondary">Batal</a>
</div>
</form>
</div></div>
@push('scripts')
<script>
const shiftJam={pagi:{mulai:'04:45',selesai:'12:15'},siang:{mulai:'08:30',selesai:'16:00'},sore:{mulai:'11:30',selesai:'19:00'},malam:{mulai:'16:15',selesai:'23:45'},mcr:{mulai:'08:30',selesai:'16:00'}};
document.getElementById('sel-shift').addEventListener('change',function(){const j=shiftJam[this.value];if(j){document.getElementById('inp-jam-mulai').value=j.mulai;document.getElementById('inp-jam-selesai').value=j.selesai;}});
document.querySelectorAll('.kondisi-select').forEach(s=>{const h=()=>s.closest('tr').style.background=s.value==='gangguan'?'#fff5f5':'';s.addEventListener('change',h);h();});
</script>
@endpush
@endsection