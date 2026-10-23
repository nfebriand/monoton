@php $rv = fn($k) => $r[$k] ?? ''; @endphp
<div class="audio-row border rounded p-2 mb-2" style="background:var(--bg)">
    <div class="row g-2">
        <div class="col-6 col-md-2">
            <label class="form-label">Peran</label>
            <select name="audio[{{ $i }}][peran]" class="form-select form-select-sm">
                @foreach(\App\Models\PemancarAudioSumber::PERAN as $p)
                <option value="{{ $p }}" {{ $rv('peran') === $p ? 'selected' : '' }}>{{ $p }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label">Sumber</label>
            <select name="audio[{{ $i }}][sumber]" class="form-select form-select-sm">
                <option value="">— Pilih —</option>
                @foreach(\App\Models\PemancarSistem::AUDIO_SUMBER as $k => $lbl)
                <option value="{{ $k }}" {{ $rv('sumber') === $k ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label">Konektor Audio</label>
            <input type="text" name="audio[{{ $i }}][konektor]" class="form-control form-control-sm" maxlength="60" value="{{ $rv('konektor') }}" placeholder="XLR / BNC / RCA">
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Frekuensi Kerja STL (MHz)</label>
            <input type="number" step="0.001" min="0" name="audio[{{ $i }}][stl_frekuensi]" class="form-control form-control-sm mono" value="{{ $rv('stl_frekuensi') }}">
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Merk Perangkat</label>
            <input type="text" name="audio[{{ $i }}][merk]" class="form-control form-control-sm" maxlength="100" value="{{ $rv('merk') }}">
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Tipe Perangkat</label>
            <input type="text" name="audio[{{ $i }}][tipe]" class="form-control form-control-sm" maxlength="100" value="{{ $rv('tipe') }}">
        </div>
        <div class="col-10 col-md-3">
            <label class="form-label">Catatan</label>
            <input type="text" name="audio[{{ $i }}][catatan]" class="form-control form-control-sm" maxlength="200" value="{{ $rv('catatan') }}">
        </div>
        <div class="col-2 col-md-1 d-flex align-items-end">
            <button type="button" class="btn btn-sm btn-outline-danger btn-del-audio w-100" title="Hapus baris"><i class="bi bi-trash"></i></button>
        </div>
    </div>
</div>
