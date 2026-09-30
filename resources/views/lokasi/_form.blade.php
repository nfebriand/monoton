<div class="row g-3">
    <div class="col-12">
        <label class="form-label">Nama Lokasi <span class="text-danger">*</span></label>
        <input type="text" name="nama" class="form-control"
               value="{{ old('nama', $lok->nama ?? '') }}" required
               placeholder="Gedung Air / Sukarame / Studio A / Gudang Sarana">
    </div>
    <div class="col-12">
        <label class="form-label">Divisi <span class="text-danger">*</span></label>
        <select name="divisi" class="form-select" required>
            @foreach(\App\Models\Lokasi::DIVISI_LABEL as $val => $label)
            <option value="{{ $val }}" {{ old('divisi', $lok->divisi ?? 'transmisi')===$val?'selected':'' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label class="form-label">Alamat</label>
        <input type="text" name="alamat" class="form-control" value="{{ old('alamat', $lok->alamat ?? '') }}" placeholder="Opsional">
    </div>
    <div class="col-12">
        <label class="form-label">Keterangan</label>
        <input type="text" name="keterangan" class="form-control" value="{{ old('keterangan', $lok->keterangan ?? '') }}" placeholder="Opsional">
    </div>
    <div class="col-12">
        <div class="form-check form-switch">
            <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ ($lok->is_active ?? true) ? 'checked':'' }}>
            <label class="form-check-label">Aktif</label>
        </div>
    </div>
</div>
