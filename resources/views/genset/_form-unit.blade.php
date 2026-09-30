@php $lokasiList = \App\Models\Lokasi::where('divisi','sarana')->orWhere('divisi','transmisi')->orderBy('nama')->get(); @endphp
<div class="row g-3">
    <div class="col-12">
        <label class="form-label">Nama Unit <span class="text-danger">*</span></label>
        <input type="text" name="nama_unit" class="form-control" value="{{ $u->nama_unit ?? '' }}" required placeholder="Genset 1 - Gedung Air">
    </div>
    <div class="col-12">
        <label class="form-label">Lokasi</label>
        <select name="lokasi" id="selectLokasiUnit{{ $u->id ?? 'baru' }}" class="form-select"
                onchange="toggleLokasiCustomUnit(this, '{{ $u->id ?? 'baru' }}')">
            <option value="">— Pilih Lokasi —</option>
            @foreach($lokasiList as $lok)
            <option value="{{ $lok->nama }}" {{ ($u->lokasi ?? '')===$lok->nama?'selected':'' }}>{{ $lok->nama }}</option>
            @endforeach
            @php $isCustomUnit = !empty($u->lokasi) && !$lokasiList->pluck('nama')->contains($u->lokasi); @endphp
            <option value="__custom__" {{ $isCustomUnit?'selected':'' }}>Lainnya (isi manual)</option>
        </select>
        <input type="text" id="inputLokasiCustomUnit{{ $u->id ?? 'baru' }}" class="form-control mt-2"
               style="display:{{ $isCustomUnit?'block':'none' }}"
               value="{{ $isCustomUnit?$u->lokasi:'' }}" placeholder="Ketik lokasi...">
    </div>
    <div class="col-6">
        <label class="form-label">Merk</label>
        <input type="text" name="merk" class="form-control" value="{{ $u->merk ?? '' }}">
    </div>
    <div class="col-6">
        <label class="form-label">Tipe</label>
        <input type="text" name="tipe" class="form-control" value="{{ $u->tipe ?? '' }}">
    </div>
    <div class="col-6">
        <label class="form-label">Kapasitas (kVA)</label>
        <input type="number" name="kapasitas_kva" class="form-control mono" value="{{ $u->kapasitas_kva ?? '' }}">
    </div>
    <div class="col-6">
        <label class="form-label">Kapasitas Tangki (L)</label>
        <input type="number" step="0.1" name="kapasitas_tangki_liter" class="form-control mono" value="{{ $u->kapasitas_tangki_liter ?? '' }}">
    </div>
    <div class="col-6">
        <label class="form-label">Tahun Pembuatan</label>
        <input type="number" name="tahun_pembuatan" class="form-control mono" value="{{ $u->tahun_pembuatan ?? '' }}">
    </div>
    <div class="col-6">
        <label class="form-label d-block">Status</label>
        <div class="form-check form-switch mt-2">
            <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ ($u->is_active ?? true)?'checked':'' }}>
            <label class="form-check-label">Aktif</label>
        </div>
    </div>
</div>
<script>
function toggleLokasiCustomUnit(sel, id){
    const c = document.getElementById('inputLokasiCustomUnit'+id);
    c.style.display = sel.value==='__custom__'?'block':'none';
    if(sel.value==='__custom__') c.focus();
}
</script>
