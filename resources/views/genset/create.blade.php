@extends('layouts.app')
@section('title','Catat Operasional Genset')
@section('page-title','Catat Operasional Genset')

@section('content')
@include('partials.offline-notice')
<div class="row justify-content-center">
<div class="col-12 col-lg-8">
<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-battery-charging text-primary"></i>Form Operasional Genset
        <a href="{{ route('genset.index') }}" class="btn btn-sm btn-outline-secondary ms-auto">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
    <div class="card-body">
    <form action="{{ route('genset.store') }}" method="POST" id="formGenset" data-offline-queue="genset" data-offline-label="Log Genset">
    @csrf

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <label class="form-label">Unit Genset <span class="text-danger">*</span></label>
            <select name="genset_unit_id" id="genset_unit_id" class="form-select" required onchange="autoFillLast()">
                <option value="">&#x2014; Pilih Unit &#x2014;</option>
                @foreach($units as $u)
                <option value="{{ $u->id }}" {{ old('genset_unit_id')==$u->id?'selected':'' }}
                    data-tangki="{{ $u->kapasitas_tangki_liter ?? '' }}">
                    {{ $u->nama_unit }}{{ $u->lokasi ? ' '."\u{2014}".' '.$u->lokasi : '' }}
                </option>
                @endforeach
            </select>
            <div id="info-unit" class="form-text" style="display:none"></div>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Alasan Penyalaan <span class="text-danger">*</span></label>
            <select name="alasan" class="form-select" required>
                <option value="pln_mati" {{ old('alasan')=='pln_mati'?'selected':'' }}>PLN Padam</option>
                <option value="maintenance" {{ old('alasan')=='maintenance'?'selected':'' }}>Maintenance / Servis</option>
                <option value="test_rutin" {{ old('alasan')=='test_rutin'?'selected':'' }}>Test Rutin</option>
                <option value="lainnya" {{ old('alasan')=='lainnya'?'selected':'' }}>Lainnya</option>
            </select>
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label">Tanggal <span class="text-danger">*</span></label>
            <input type="date" name="tanggal" class="form-control" value="{{ old('tanggal',now()->toDateString()) }}" required>
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Jam Mulai <span class="text-danger">*</span></label>
            <input type="time" name="jam_mulai" class="form-control mono" value="{{ old('jam_mulai',now()->format('H:i')) }}" required>
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Jam Selesai</label>
            <input type="time" name="jam_selesai" class="form-control mono" value="{{ old('jam_selesai') }}">
            <div class="form-text">Kosongkan jika genset masih berjalan</div>
        </div>
    </div>

    <h6 class="section-title mb-3">&#x26FD; Pengisian BBM</h6>
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <label class="form-label">Pengisian BBM (Liter)</label>
            <input type="number" step="0.1" min="0" inputmode="decimal" name="bbm_isi" class="form-control mono" value="{{ old('bbm_isi') }}" placeholder="0">
            <div class="form-text">Isi hanya jika ada pengisian BBM pada saat ini. Rekap tersedia di menu <em>Rekap Pengisian BBM</em>.</div>
        </div>
    </div>

    <h6 class="section-title mb-3">&#x1F4CB; Kondisi & Keterangan</h6>
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <label class="form-label">Kondisi Mesin <span class="text-danger">*</span></label>
            <select name="kondisi" class="form-select" required>
                <option value="normal" {{ old('kondisi')=='normal'?'selected':'' }}>Normal</option>
                <option value="gangguan" {{ old('kondisi')=='gangguan'?'selected':'' }}>Ada Gangguan</option>
            </select>
        </div>
        <div class="col-12 col-md-8">
            <label class="form-label">Keterangan</label>
            <input type="text" name="keterangan" class="form-control" value="{{ old('keterangan') }}"
                   placeholder="Catatan tambahan, kondisi gangguan, dll">
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Simpan</button>
        <a href="{{ route('genset.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
    </form>
    </div>
</div>
</div>
</div>
@endsection

@push('scripts')
<script>
function autoFillLast(){
    const sel = document.getElementById('genset_unit_id');
    const opt = sel.options[sel.selectedIndex];
    const tangki = opt ? opt.dataset.tangki : '';
    const info = document.getElementById('info-unit');
    if(tangki){
        info.style.display='block';
        info.innerHTML = '<i class="bi bi-info-circle me-1"></i>Kapasitas tangki: '+tangki+' L';
    } else {
        info.style.display='none';
    }
}
document.addEventListener('DOMContentLoaded', autoFillLast);

// Peringatan dini (tidak memblokir): pengisian melebihi kapasitas tangki
(function(){
    const f = document.getElementById('formGenset');
    const el = f.querySelector('[name="bbm_isi"]');
    const sel = document.getElementById('genset_unit_id');
    function cek(){
        const opt = sel.options[sel.selectedIndex] || {dataset:{}};
        const tangki = parseFloat(opt.dataset.tangki), isi = parseFloat(el.value);
        let w = el.parentElement.querySelector('.cek-warn');
        if(!w){ w = document.createElement('div'); w.className = 'cek-warn form-text text-warning'; el.parentElement.appendChild(w); }
        const msg = (!isNaN(tangki) && !isNaN(isi) && isi > tangki) ? 'Melebihi kapasitas tangki (' + tangki + ' L) \u2014 periksa' : '';
        w.textContent = msg; w.style.display = msg ? '' : 'none';
        el.classList.toggle('border-warning', !!msg);
    }
    el.addEventListener('input', cek); sel.addEventListener('change', cek);
})();
</script>
@endpush
