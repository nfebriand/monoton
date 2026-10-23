@php
    /** @var \App\Models\PemancarSistem|null $sistem */
    $sistem = $sistem ?? null;
    $sv = fn(string $f) => old('sistem.'.$f, $sistem?->{$f});
    $nf = fn(string $f, int $d = 2) => ($v = $sv($f)) === null || $v === '' ? '' : \App\Models\PemancarSistem::num($v, $d);
    $apVal = old('sistem.ap_terpasang', $sistem && $sistem->ap_terpasang !== null ? (int) $sistem->ap_terpasang : '');
@endphp

<h6 class="section-title mb-3">📡 Antena</h6>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <label class="form-label">Merk</label>
        <input type="text" name="sistem[antena_merk]" class="form-control" value="{{ $sv('antena_merk') }}" maxlength="100">
    </div>
    <div class="col-6 col-md-3">
        <label class="form-label">Jenis</label>
        <input type="text" name="sistem[antena_jenis]" class="form-control" value="{{ $sv('antena_jenis') }}" maxlength="100" placeholder="panel / dipole / circular">
    </div>
    <div class="col-6 col-md-3">
        <label class="form-label">Jumlah Bay</label>
        <input type="number" min="1" name="sistem[antena_bay]" class="form-control mono" value="{{ $sv('antena_bay') }}">
    </div>
    <div class="col-6 col-md-3">
        <label class="form-label">Daya Maks. (W)</label>
        <input type="number" step="0.01" min="0" name="sistem[antena_max_power]" class="form-control mono" value="{{ $sv('antena_max_power') }}">
    </div>
    <div class="col-6 col-md-3">
        <label class="form-label">Gain (dBd)</label>
        <input type="number" step="0.01" name="sistem[antena_gain]" class="form-control mono" value="{{ $sv('antena_gain') }}">
    </div>
    <div class="col-6 col-md-3">
        <label class="form-label">Tinggi Antena (m)</label>
        <input type="number" step="0.01" min="0" name="sistem[antena_tinggi]" class="form-control mono" value="{{ $sv('antena_tinggi') }}">
    </div>
</div>

<h6 class="section-title mb-3">🔌 Kabel &amp; Konektor</h6>
<div class="row g-3 mb-4">
    <div class="col-12 col-md-8">
        <label class="form-label">Jenis Kabel (pemancar → antena)</label>
        <input type="text" name="sistem[kabel_jenis]" class="form-control" value="{{ $sv('kabel_jenis') }}" maxlength="100" placeholder='mis. Heliax 7/8"'>
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label">Panjang Kabel (m)</label>
        <input type="number" step="0.01" min="0" name="sistem[kabel_panjang]" class="form-control mono" value="{{ $sv('kabel_panjang') }}">
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label">Konektor Exciter → Driver</label>
        <input type="text" name="sistem[konektor_exciter_driver]" class="form-control" value="{{ $sv('konektor_exciter_driver') }}" maxlength="60" placeholder="mis. BNC / N-type">
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label">Konektor Driver → Final</label>
        <input type="text" name="sistem[konektor_driver_final]" class="form-control" value="{{ $sv('konektor_driver_final') }}" maxlength="60">
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label">Konektor Final → Antena</label>
        <input type="text" name="sistem[konektor_final_antena]" class="form-control" value="{{ $sv('konektor_final_antena') }}" maxlength="60" placeholder="mis. 7/16 DIN / 1-5/8 EIA">
    </div>
</div>

<h6 class="section-title mb-2">🎙️ Sumber Audio</h6>
@php
    $audioRows = old('audio', isset($audioRows) ? collect($audioRows)->map(fn($r) => [
        'peran' => $r->peran, 'sumber' => $r->sumber, 'konektor' => $r->konektor, 'merk' => $r->merk,
        'tipe' => $r->tipe, 'stl_frekuensi' => $r->stl_frekuensi !== null ? rtrim(rtrim((string) $r->stl_frekuensi, '0'), '.') : '', 'catatan' => $r->catatan,
    ])->all() : []);
    if (empty($audioRows)) $audioRows = [[]];
@endphp
<div class="text-muted mb-2" style="font-size:.72rem">Tambahkan lebih dari satu bila ada sumber utama dan cadangan.</div>
<div id="audioRows" class="mb-2">
@foreach(array_values($audioRows) as $ai => $ar)
    @include('pemancar._audio-row', ['i' => $ai, 'r' => $ar])
@endforeach
</div>
<button type="button" class="btn btn-sm btn-outline-primary mb-4" id="btnAddAudio"><i class="bi bi-plus-circle me-1"></i>Tambah Sumber Audio</button>
<template id="audioRowTpl">
    @include('pemancar._audio-row', ['i' => '__I__', 'r' => []])
</template>

<h6 class="section-title mb-3">🎛️ Audio Processor</h6>
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <label class="form-label">Status</label>
        <select name="sistem[ap_terpasang]" id="apTerpasang" class="form-select">
            <option value="" {{ $apVal === '' ? 'selected' : '' }}>— Belum diisi —</option>
            <option value="1" {{ (string) $apVal === '1' ? 'selected' : '' }}>Terpasang</option>
            <option value="0" {{ (string) $apVal === '0' ? 'selected' : '' }}>Tidak terpasang</option>
        </select>
    </div>
    <div class="col-6 col-md-4 ap-field">
        <label class="form-label">Merk</label>
        <input type="text" name="sistem[ap_merk]" class="form-control" value="{{ $sv('ap_merk') }}" maxlength="100">
    </div>
    <div class="col-6 col-md-4 ap-field">
        <label class="form-label">Tipe</label>
        <input type="text" name="sistem[ap_tipe]" class="form-control" value="{{ $sv('ap_tipe') }}" maxlength="100">
    </div>
    <div class="col-12 ap-field">
        <label class="form-label">Spesifikasi</label>
        <textarea name="sistem[ap_spek]" class="form-control" rows="2" maxlength="2000" placeholder="mis. multiband 5 band, preset, input AES/analog">{{ $sv('ap_spek') }}</textarea>
    </div>
    <div class="col-12">
        <label class="form-label">Catatan Sistem</label>
        <textarea name="sistem[catatan]" class="form-control" rows="2" maxlength="2000">{{ $sv('catatan') }}</textarea>
    </div>
</div>

<script>
(function () {
    var wrap = document.getElementById('audioRows'), tpl = document.getElementById('audioRowTpl'), btn = document.getElementById('btnAddAudio');
    if (!wrap || !tpl) return;
    var n = wrap.querySelectorAll('.audio-row').length;
    btn.addEventListener('click', function () {
        if (wrap.querySelectorAll('.audio-row').length >= 8) return;
        var html = tpl.innerHTML.replace(/__I__/g, String(n++));
        wrap.insertAdjacentHTML('beforeend', html);
    });
    wrap.addEventListener('click', function (e) {
        var b = e.target.closest('.btn-del-audio');
        if (!b) return;
        var row = b.closest('.audio-row');
        if (wrap.querySelectorAll('.audio-row').length > 1) row.remove();
        else row.querySelectorAll('input,select').forEach(function (el) { el.value = ''; });
    });
})();
</script>
<script>
(function () {
    var sel = document.getElementById('apTerpasang');
    if (!sel) return;
    function sync() {
        var off = sel.value === '0';
        document.querySelectorAll('.ap-field').forEach(function (el) {
            el.style.display = off ? 'none' : '';
        });
    }
    sel.addEventListener('change', sync); sync();
})();
</script>
