@extends('layouts.app')
@section('title','Edit Logbook Studio')
@section('page-title','Edit Logbook Studio')

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-9">

@if($errors->any())
<div class="alert alert-danger py-2 mb-3"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li style="font-size:.82rem">{{ $e }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ route('studio.logbook.update',$studioLog) }}">
@csrf @method('PUT')

<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-clock me-2"></i>Informasi Shift</h6></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                <input type="date" name="tanggal" class="form-control" value="{{ old('tanggal', $studioLog->tanggal->format('Y-m-d')) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Shift <span class="text-danger">*</span></label>
                <select name="shift" class="form-select" required>
                    @foreach(\App\Models\StudioLog::SHIFT as $val=>$label)
                    <option value="{{ $val }}" {{ old('shift',$studioLog->shift)==$val?'selected':'' }}>{{ ucfirst($val) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Jam Mulai <span class="text-danger">*</span></label>
                <input type="time" name="jam_mulai" class="form-control" value="{{ old('jam_mulai', substr($studioLog->jam_mulai,0,5)) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Jam Selesai</label>
                <input type="time" name="jam_selesai" class="form-control" value="{{ old('jam_selesai', $studioLog->jam_selesai ? substr($studioLog->jam_selesai,0,5) : '') }}">
            </div>
        </div>
    </div>
</div>

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
                    @php $currentKondisi = old($key, $studioLog->$key); @endphp
                    <tr style="{{ $currentKondisi==='gangguan'?'background:#fff5f5':'' }}">
                        <td class="ps-3 py-3">
                            <div class="fw-semibold">{{ $item['label'] }}</div>
                            @if($item['desc'])<div class="text-muted" style="font-size:.72rem">*{{ $item['desc'] }}</div>@endif
                        </td>
                        <td class="py-3">
                            <select name="{{ $key }}" class="form-select form-select-sm kondisi-select" required>
                                @foreach(\App\Models\StudioLog::KONDISI_OPTIONS as $val=>$label)
                                <option value="{{ $val }}" {{ $currentKondisi==$val?'selected':'' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="py-3">
                            <textarea name="{{ $key }}_ket" class="form-control form-control-sm" rows="2"
                                placeholder="Isi bila ada gangguan/catatan...">{{ old($key.'_ket', $studioLog->{$key.'_ket'}) }}</textarea>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header" style="background:#fff3cd">
        <h6 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Catatan Petugas Dinas</h6>
    </div>
    <div class="card-body">
        <textarea name="catatan_petugas" class="form-control" rows="4">{{ old('catatan_petugas', $studioLog->catatan_petugas) }}</textarea>
    </div>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Simpan Perubahan</button>
    <a href="{{ route('studio.logbook.show',$studioLog) }}" class="btn btn-outline-secondary">Batal</a>
</div>

</form>
</div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.kondisi-select').forEach(sel => {
    sel.addEventListener('change', function(){
        this.closest('tr').style.background = this.value === 'gangguan' ? '#fff5f5' : '';
    });
});
</script>
@endpush
@endsection
