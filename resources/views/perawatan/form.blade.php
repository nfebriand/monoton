@extends('layouts.app')
@section('title', $item->exists ? 'Edit Jadwal Perawatan' : 'Tambah Jadwal Perawatan')
@section('page-title', $item->exists ? 'Edit Jadwal Perawatan' : 'Tambah Jadwal Perawatan')

@section('content')
@php
    $basis = old('basis', $item->basis ?: 'tanggal');
    $bisaPemancar = $pemancars->isNotEmpty() || auth()->user()->can('perawatan.kelola', 'pemancar');
    $bisaGenset   = $units->isNotEmpty() || auth()->user()->can('perawatan.kelola', 'genset');
    $tipe = old('objek_tipe', $item->objek_tipe ?: ($bisaPemancar ? 'pemancar' : 'genset'));
    // objek bawaan harus yang boleh dipilih pengguna ini (mis. user Sarana tidak melihat pemancar)
    if ($tipe === 'pemancar' && !$bisaPemancar && $bisaGenset) $tipe = 'genset';
    if ($tipe === 'genset' && !$bisaGenset && $bisaPemancar) $tipe = 'pemancar';
    $nf = fn($v) => $v === null ? '' : rtrim(rtrim(number_format($v,1,'.',''),'0'),'.');
@endphp
<div class="row justify-content-center"><div class="col-12 col-lg-8">
<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-wrench-adjustable text-primary"></i>Jadwal Perawatan Rutin
        <a href="{{ route('perawatan.index') }}" class="btn btn-sm btn-outline-secondary ms-auto"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>
    <div class="card-body">
    <form method="POST" action="{{ $item->exists ? route('perawatan.update', $item) : route('perawatan.store') }}" id="formPerawatan">
        @csrf
        @if($item->exists) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Objek <span class="text-danger">*</span></label>
                @if($item->exists)
                    <input type="hidden" name="objek_tipe" value="{{ $item->objek_tipe }}">
                    <div class="form-control-plaintext fw-bold">{{ $item->objek_tipe === 'pemancar' ? 'Pemancar' : 'Genset' }}</div>
                @else
                <div class="d-flex gap-3">
                    @if($bisaPemancar)
                    <div class="form-check"><input class="form-check-input" type="radio" name="objek_tipe" id="t-pemancar" value="pemancar" {{ $tipe==='pemancar'?'checked':'' }}><label class="form-check-label" for="t-pemancar"><i class="bi bi-broadcast-pin me-1"></i>Pemancar</label></div>
                    @endif
                    @if($bisaGenset)
                    <div class="form-check"><input class="form-check-input" type="radio" name="objek_tipe" id="t-genset" value="genset" {{ $tipe==='genset'?'checked':'' }}><label class="form-check-label" for="t-genset"><i class="bi bi-battery-charging me-1"></i>Genset</label></div>
                    @endif
                </div>
                @endif
            </div>

            <div class="col-12 col-md-6" id="row-pemancar">
                <label class="form-label">Pemancar <span class="text-danger">*</span></label>
                <select name="pemancar_id" class="form-select">
                    <option value="">&mdash; Pilih Pemancar &mdash;</option>
                    @foreach($pemancars->groupBy('lokasi') as $lok => $grp)
                    <optgroup label="{{ $lok ?: 'Tanpa lokasi' }}">
                        @foreach($grp as $p)
                        <option value="{{ $p->id }}" {{ (string) old('pemancar_id', $item->pemancar_id) === (string) $p->id ? 'selected' : '' }}>{{ $p->nama_stasiun }}</option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-6" id="row-genset">
                <label class="form-label">Unit Genset <span class="text-danger">*</span></label>
                <select name="genset_unit_id" id="sel-unit" class="form-select">
                    <option value="">&mdash; Pilih Unit &mdash;</option>
                    @foreach($units as $u)
                    <option value="{{ $u->id }}" data-hm="{{ $hmMap[$u->id] ?? '' }}" {{ (string) old('genset_unit_id', $item->genset_unit_id) === (string) $u->id ? 'selected' : '' }}>{{ $u->nama_unit }}{{ $u->lokasi ? ' ('.$u->lokasi.')' : '' }}</option>
                    @endforeach
                </select>
                <div class="form-text" id="info-hm"></div>
            </div>

            <div class="col-12 col-md-5">
                <label class="form-label">Contoh tugas</label>
                <select id="sel-preset" class="form-select"><option value="">&mdash; pilih untuk mengisi otomatis &mdash;</option></select>
            </div>
            <div class="col-12 col-md-7">
                <label class="form-label">Nama Tugas <span class="text-danger">*</span></label>
                <input type="text" name="nama_tugas" id="in-nama" class="form-control" maxlength="150" value="{{ old('nama_tugas', $item->nama_tugas) }}" required>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label">Basis Pengingat <span class="text-danger">*</span></label>
                <select name="basis" id="sel-basis" class="form-select">
                    <option value="tanggal"  {{ $basis==='tanggal'?'selected':'' }}>Berdasarkan tanggal</option>
                    <option value="hm"       {{ $basis==='hm'?'selected':'' }} data-genset>Berdasarkan jam kerja (HM)</option>
                    <option value="keduanya" {{ $basis==='keduanya'?'selected':'' }} data-genset>Tanggal ATAU HM (mana lebih dulu)</option>
                </select>
            </div>
            <div class="col-6 col-md-3" id="row-hari">
                <label class="form-label">Interval (hari)</label>
                <input type="number" min="1" max="3650" name="interval_hari" id="in-hari" class="form-control mono" value="{{ old('interval_hari', $item->interval_hari) }}">
            </div>
            <div class="col-6 col-md-3" id="row-ivhm">
                <label class="form-label">Interval (jam HM)</label>
                <input type="number" min="1" step="0.1" name="interval_hm" id="in-ivhm" class="form-control mono" value="{{ old('interval_hm', $nf($item->interval_hm)) }}">
            </div>

            <div class="col-6 col-md-4">
                <label class="form-label">Terakhir dilakukan (tanggal)</label>
                <input type="date" name="terakhir_tanggal" class="form-control" max="{{ now()->toDateString() }}" value="{{ old('terakhir_tanggal', $item->terakhir_tanggal ? $item->terakhir_tanggal->format('Y-m-d') : '') }}">
                <div class="form-text">Kosongkan bila belum pernah / tidak diketahui.</div>
            </div>
            <div class="col-6 col-md-4" id="row-lasthm">
                <label class="form-label">HM saat terakhir dilakukan</label>
                <input type="number" min="0" step="0.1" name="terakhir_hm" id="in-lasthm" class="form-control mono" value="{{ old('terakhir_hm', $nf($item->terakhir_hm)) }}">
            </div>
            <div class="col-12 col-md-4 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    <input type="hidden" name="aktif" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" name="aktif" id="in-aktif" value="1" {{ old('aktif', $item->aktif ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="in-aktif">Jadwal aktif</label>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label">Catatan</label>
                <input type="text" name="catatan" class="form-control" maxlength="1000" value="{{ old('catatan', $item->catatan) }}">
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Simpan</button>
            <a href="{{ route('perawatan.index') }}" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
    </div>
</div>
</div></div>
@endsection

@push('scripts')
<script>
(function(){
    const PRESET = @json($preset);
    const $ = id => document.getElementById(id);
    const tipeEl = () => (document.querySelector('input[name=objek_tipe]:checked') || document.querySelector('input[name=objek_tipe][type=hidden]') || {}).value || @json($tipe);

    function aturTampilan(){
        const t = tipeEl(), b = $('sel-basis').value;
        $('row-pemancar').style.display = t === 'pemancar' ? '' : 'none';
        $('row-genset').style.display   = t === 'genset' ? '' : 'none';
        // basis HM hanya untuk genset
        Array.from($('sel-basis').options).forEach(o => { if (o.hasAttribute('data-genset')) o.disabled = (t !== 'genset'); });
        if (t !== 'genset' && b !== 'tanggal') $('sel-basis').value = 'tanggal';
        const bb = $('sel-basis').value;
        $('row-hari').style.display = (bb === 'tanggal' || bb === 'keduanya') ? '' : 'none';
        $('row-ivhm').style.display = (bb === 'hm' || bb === 'keduanya') ? '' : 'none';
        $('row-lasthm').style.display = (bb === 'hm' || bb === 'keduanya') ? '' : 'none';
        // opsi preset sesuai tipe
        const sp = $('sel-preset'); sp.length = 1;
        (PRESET[t] || []).forEach((p, i) => sp.add(new Option(p.nama, i)));
    }
    function infoHm(){
        const o = $('sel-unit').options[$('sel-unit').selectedIndex];
        $('info-hm').textContent = (o && o.dataset.hm) ? 'HM unit saat ini (dari log genset): ' + o.dataset.hm + ' jam' : '';
    }
    document.querySelectorAll('input[name=objek_tipe]').forEach(r => r.addEventListener('change', aturTampilan));
    $('sel-basis').addEventListener('change', aturTampilan);
    $('sel-unit').addEventListener('change', infoHm);
    $('sel-preset').addEventListener('change', function(){
        const p = (PRESET[tipeEl()] || [])[this.value]; if (!p) return;
        $('in-nama').value = p.nama; $('sel-basis').value = p.basis;
        $('in-hari').value = p.hari || ''; $('in-ivhm').value = p.hm || '';
        aturTampilan();
    });
    aturTampilan(); infoHm();
})();
</script>
@endpush
