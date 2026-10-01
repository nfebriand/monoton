{{--
    Komponen Upload Foto Multi — versi reliable tanpa DataTransfer API
    Usage: @include('components.foto-upload', ['existingFotos' => $model->fotos])

    Sumber foto diprioritaskan GALERI, kamera hanya opsi kedua (tombol terpisah)
    supaya operator tidak "terjebak" langsung ke kamera saat mau unggah foto
    yang sudah ada di galeri HP.
--}}
@php $existingFotos = $existingFotos ?? collect(); $maxMb = $maxMb ?? 8; @endphp

<div class="foto-upload-wrapper">
    {{-- Foto yang sudah ada --}}
    @if($existingFotos->count())
    <div class="mb-3">
        <div class="fw-semibold mb-2" style="font-size:.82rem">Foto Saat Ini:</div>
        <div class="row g-2">
            @foreach($existingFotos as $foto)
            <div class="col-6 col-md-3" id="foto-wrap-{{ $foto->id }}">
                <div class="position-relative">
                    <img src="{{ $foto->thumb_url ?? asset('storage/'.$foto->path) }}"
                         class="img-fluid rounded"
                         style="width:100%;height:110px;object-fit:cover;cursor:pointer"
                         onclick="openLightbox('{{ $foto->url ?? asset('storage/'.$foto->path) }}')"
                         onerror="this.src='{{ asset('storage/'.$foto->path) }}'">
                    <div class="form-check position-absolute top-0 end-0 m-1">
                        <input class="form-check-input" type="checkbox"
                               name="hapus_foto[]" value="{{ $foto->id }}"
                               id="hapus-{{ $foto->id }}"
                               onchange="this.closest('[id^=foto-wrap]').style.opacity=this.checked?'.4':'1'">
                        <label for="hapus-{{ $foto->id }}"
                               style="font-size:.65rem;background:rgba(255,255,255,.85);padding:1px 4px;border-radius:3px;color:#c0392b;font-weight:600">
                            Hapus
                        </label>
                    </div>
                    @if($foto->keterangan)
                    <div style="font-size:.68rem;color:#666;margin-top:2px">{{ $foto->keterangan }}</div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Area upload foto baru --}}
    <div id="foto-slots-container">
        {{-- Slot pertama selalu tampil --}}
        <div class="foto-slot mb-2" id="slot-0">
            <div class="d-flex gap-2 flex-wrap align-items-start">
                <label class="btn btn-primary btn-sm mb-0" style="cursor:pointer">
                    <i class="bi bi-images me-1"></i>Pilih dari Galeri
                    <input type="file" name="fotos[]" class="d-none foto-input-gallery"
                           accept="image/jpeg,image/png,image/jpg,image/webp"
                           onchange="previewSlot(this, 0)" data-slot="0">
                </label>
                <label class="btn btn-outline-secondary btn-sm mb-0" style="cursor:pointer">
                    <i class="bi bi-camera me-1"></i>Ambil Foto
                    <input type="file" name="fotos[]" class="d-none foto-input-camera"
                           accept="image/jpeg,image/png,image/jpg,image/webp"
                           capture="environment"
                           onchange="previewSlot(this, 0)" data-slot="0">
                </label>
                <button type="button" class="btn btn-outline-secondary btn-sm"
                        onclick="clearSlot(0)" title="Hapus">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <div id="preview-0" class="mt-1" style="display:none">
                <img src="" id="img-preview-0"
                     style="height:80px;border-radius:6px;object-fit:cover;border:1px solid #ddd">
                <input type="text" name="foto_keterangan[0]" class="form-control form-control-sm mt-1"
                       placeholder="Keterangan foto (opsional)" style="font-size:.75rem">
            </div>
        </div>
    </div>

    <button type="button" class="btn btn-outline-secondary btn-sm w-100 mt-1" onclick="addFotoSlot()">
        <i class="bi bi-plus-circle me-1"></i>Tambah Foto Lain
    </button>
    <div style="font-size:.72rem;color:#aaa;margin-top:4px">
        <i class="bi bi-info-circle me-1"></i>Max {{ $maxMb }}MB per foto • JPG, PNG, WEBP
    </div>
</div>

{{-- Lightbox --}}
<div id="foto-lightbox" onclick="this.style.display='none'"
     style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;
            background:rgba(0,0,0,.85);z-index:9999;align-items:center;justify-content:center">
    <img id="lightbox-img" src="" style="max-width:95%;max-height:90vh;border-radius:8px">
</div>

@once
@push('scripts')
<script>
let slotCount = 1;

// input = elemen <input type=file> yang baru saja dipilih (galeri ATAU kamera).
// Kalau slot yang sama punya input lain yang sudah terisi, kosongkan supaya
// tidak terkirim dua foto dari satu slot.
function previewSlot(input, idx) {
    const slot = document.getElementById('slot-' + idx);
    const siblingSelector = input.classList.contains('foto-input-gallery')
        ? '.foto-input-camera' : '.foto-input-gallery';
    const sibling = slot ? slot.querySelector(siblingSelector) : null;

    const file = input.files[0];
    if (!file) { clearSlot(idx); return; }
    if (sibling) sibling.value = '';

    const reader = new FileReader();
    reader.onload = e => {
        const prev = document.getElementById('preview-' + idx);
        const img  = document.getElementById('img-preview-' + idx);
        if (prev && img) {
            img.src = e.target.result;
            prev.style.display = 'block';
        }
    };
    reader.readAsDataURL(file);
}

function clearSlot(idx) {
    const slot = document.getElementById('slot-' + idx);
    if (!slot) return;
    slot.querySelectorAll('input[type=file]').forEach(inp => inp.value = '');
    const prev = document.getElementById('preview-' + idx);
    if (prev) prev.style.display = 'none';
}

function addFotoSlot() {
    const idx = slotCount++;
    const container = document.getElementById('foto-slots-container');
    const div = document.createElement('div');
    div.className = 'foto-slot mb-2';
    div.id = 'slot-' + idx;
    div.innerHTML = `
        <div class="d-flex gap-2 flex-wrap align-items-start">
            <label class="btn btn-primary btn-sm mb-0" style="cursor:pointer">
                <i class="bi bi-images me-1"></i>Pilih dari Galeri
                <input type="file" name="fotos[]" class="d-none foto-input-gallery"
                       accept="image/jpeg,image/png,image/jpg,image/webp"
                       onchange="previewSlot(this, ${idx})" data-slot="${idx}">
            </label>
            <label class="btn btn-outline-secondary btn-sm mb-0" style="cursor:pointer">
                <i class="bi bi-camera me-1"></i>Ambil Foto
                <input type="file" name="fotos[]" class="d-none foto-input-camera"
                       accept="image/jpeg,image/png,image/jpg,image/webp"
                       capture="environment"
                       onchange="previewSlot(this, ${idx})" data-slot="${idx}">
            </label>
            <button type="button" class="btn btn-outline-danger btn-sm"
                    onclick="removeSlot(${idx})" title="Hapus baris">
                <i class="bi bi-trash"></i>
            </button>
        </div>
        <div id="preview-${idx}" class="mt-1" style="display:none">
            <img src="" id="img-preview-${idx}"
                 style="height:80px;border-radius:6px;object-fit:cover;border:1px solid #ddd">
            <input type="text" name="foto_keterangan[${idx}]"
                   class="form-control form-control-sm mt-1"
                   placeholder="Keterangan foto (opsional)" style="font-size:.75rem">
        </div>
    `;
    container.appendChild(div);
}

function removeSlot(idx) {
    const slot = document.getElementById('slot-' + idx);
    if (slot) slot.remove();
}

function openLightbox(src) {
    const lb = document.getElementById('foto-lightbox');
    document.getElementById('lightbox-img').src = src;
    lb.style.display = 'flex';
}
</script>
@endpush
@endonce
