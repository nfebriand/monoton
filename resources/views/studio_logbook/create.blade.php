@extends('layouts.app')
@section('title','Isi Logbook Studio')
@section('page-title','Isi Logbook Harian Studio')

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-9">

@if($errors->any())
<div class="alert alert-danger py-2 mb-3"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li style="font-size:.82rem">{{ $e }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ route('studio.logbook.store') }}">
@csrf

{{-- Info Shift --}}
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
                    <option value="{{ $val }}" {{ old('shift','pagi')==$val?'selected':'' }} data-jam="{{ explode('(',$label)[1]??'' }}">
                        {{ ucfirst($val) }}
                    </option>
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
        <div class="mt-2" style="font-size:.78rem;color:var(--muted)">
            Petugas: <strong>{{ auth()->user()->name }}</strong>
        </div>
    </div>
</div>

{{-- Checklist Kondisi --}}
<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>Checklist Kondisi Perangkat</h6></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0" style="font-size:.83rem">
                <thead style="background:#f8f9fa">
                    <tr>
                        <th style="width:40%" class="ps-3">Item Pemeriksaan</th>
                        <th style="width:20%">Aksi / Kondisi</th>
                        <th style="width:40%">Deskripsi (Bila Ada Masalah)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(\App\Models\StudioLog::CHECKLIST_ITEMS as $key => $item)
                    <tr>
                        <td class="ps-3 py-3">
                            <div class="fw-semibold">{{ $item['label'] }}</div>
                            @if($item['desc'])
                            <div class="text-muted" style="font-size:.72rem;margin-top:2px">*{{ $item['desc'] }}</div>
                            @endif
                        </td>
                        <td class="py-3">
                            <select name="{{ $key }}" class="form-select form-select-sm kondisi-select" data-key="{{ $key }}" required>
                                @foreach(\App\Models\StudioLog::KONDISI_OPTIONS as $val=>$label)
                                <option value="{{ $val }}" {{ old($key,'baik')==$val?'selected':'' }}
                                    style="{{ $val=='baik'?'color:#27ae60':($val=='gangguan'?'color:#e74c3c':'color:#888') }}">
                                    {{ $label }}
                                </option>
                                @endforeach
                            </select>
                        </td>
                        <td class="py-3">
                            <textarea name="{{ $key }}_ket" class="form-control form-control-sm ket-input" rows="2"
                                id="ket-{{ $key }}"
                                placeholder="Isi bila ada gangguan/catatan...">{{ old($key.'_ket') }}</textarea>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Catatan Petugas --}}
<div class="card mb-3">
    <div class="card-header" style="background:#fff3cd">
        <h6 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Catatan Petugas Dinas</h6>
        <small class="text-muted">Catatan berfungsi untuk menginformasikan ke petugas berikutnya</small>
    </div>
    <div class="card-body">
        <textarea name="catatan_petugas" class="form-control" rows="4"
            placeholder="Tulis catatan penting untuk petugas shift berikutnya...">{{ old('catatan_petugas') }}</textarea>
    </div>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Simpan Logbook</button>
    <a href="{{ route('studio.logbook.index') }}" class="btn btn-outline-secondary">Batal</a>
</div>

</form>
</div>
</div>

@push('scripts')
<script>
// Auto-isi jam berdasarkan shift
const shiftJam = {
    pagi:  {mulai:'04:45', selesai:'12:15'},
    siang: {mulai:'12:00', selesai:'19:30'},
    malam: {mulai:'19:15', selesai:'04:45'},
};
document.getElementById('sel-shift').addEventListener('change', function(){
    const jam = shiftJam[this.value];
    if (jam) {
        document.getElementById('inp-jam-mulai').value  = jam.mulai;
        document.getElementById('inp-jam-selesai').value = jam.selesai;
    }
});

// Highlight row jika gangguan
document.querySelectorAll('.kondisi-select').forEach(sel => {
    sel.addEventListener('change', function(){
        const row = this.closest('tr');
        row.style.background = this.value === 'gangguan' ? '#fff5f5' : '';
    });
    // Init
    if (sel.value === 'gangguan') sel.closest('tr').style.background = '#fff5f5';
});
</script>
@endpush
@endsection
