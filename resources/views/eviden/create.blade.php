@extends('layouts.app')
@section('title','Tambah Eviden')
@section('page-title','Tambah Catatan Eviden')

@section('content')

{{-- Offline notice --}}
<div id="offline-notice" class="alert alert-warning d-flex align-items-center gap-2 py-2 mb-3" style="display:none!important">
    <i class="bi bi-cloud-slash fs-5 flex-shrink-0"></i>
    <div><strong>Mode Offline</strong> — Data & foto disimpan lokal, sync otomatis saat online.</div>
</div>

<div class="row justify-content-center">
<div class="col-12 col-xl-9">
<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-camera text-primary"></i> Form Catatan Eviden Baru
        <a href="{{ route('eviden.index') }}" class="btn btn-sm btn-outline-secondary ms-auto">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
    <div class="card-body">
    <form action="{{ route('eviden.store') }}" method="POST" enctype="multipart/form-data" id="formEviden">
    @csrf

    <h6 class="section-title mb-3">📋 Informasi Kegiatan</h6>
    <div class="row g-3 mb-4">
        <div class="col-12">
            <label class="form-label">Judul Kegiatan <span class="text-danger">*</span></label>
            <input type="text" name="judul" id="ev_judul" class="form-control"
                   value="{{ old('judul') }}" required>
        </div>
        <div class="col-12">
            <label class="form-label">Deskripsi / Uraian Pekerjaan</label>
            <textarea name="deskripsi" id="ev_deskripsi" class="form-control" rows="4">{{ old('deskripsi') }}</textarea>
        </div>
    </div>

    <h6 class="section-title mb-3">⏰ Waktu & Lokasi</h6>
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <label class="form-label">Tanggal <span class="text-danger">*</span></label>
            <input type="date" name="tanggal" id="ev_tanggal" class="form-control"
                   value="{{ old('tanggal', now()->toDateString()) }}" required>
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Jam Mulai <span class="text-danger">*</span></label>
            <input type="time" name="jam_mulai" id="ev_jam_mulai" class="form-control mono"
                   value="{{ old('jam_mulai', now()->format('H:i')) }}" required>
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Jam Selesai <span class="text-danger">*</span></label>
            <input type="time" name="jam_selesai" id="ev_jam_selesai" class="form-control mono"
                   value="{{ old('jam_selesai') }}" required>
        </div>
        <div class="col-12">
            <label class="form-label">Lokasi Kegiatan</label>
            <input type="text" name="lokasi" id="ev_lokasi" class="form-control" value="{{ old('lokasi') }}">
        </div>
    </div>

    <h6 class="section-title mb-3">👥 Personil Terlibat</h6>
    <div class="row g-3 mb-4">
        <div class="col-12">
            <label class="form-label">Operator Terlibat</label>
            <div class="row g-2">
                @foreach($operators as $op)
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="form-check p-2 rounded"
                         style="background:#f8fafc;border:1px solid #eee;transition:border-color .15s"
                         id="opWrap{{ $op->id }}">
                        <input type="checkbox" name="operator_ids[]" value="{{ $op->id }}"
                               id="op{{ $op->id }}" class="form-check-input ev-operator"
                               onchange="document.getElementById('opWrap{{ $op->id }}').style.borderColor=this.checked?'var(--primary)':'#eee'">
                        <label for="op{{ $op->id }}" class="form-check-label" style="font-size:.8rem">
                            <div class="fw-600">{{ $op->name }}</div>
                            @if($op->lokasi_dinas)<small class="text-muted">{{ $op->lokasi_dinas }}</small>@endif
                        </label>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        <div class="col-12">
            <label class="form-label">Supervisi / Pengelola yang Hadir</label>
            <input type="text" name="supervisi" id="ev_supervisi" class="form-control" value="{{ old('supervisi') }}">
        </div>
    </div>

    {{-- FOTO dengan kompresi otomatis --}}
    <h6 class="section-title mb-3">📷 Foto Dokumentasi</h6>
    <div class="mb-4">
        <input type="file" id="inputFoto" class="form-control"
               multiple accept="image/jpeg,image/png,image/jpg,image/webp">
        {{-- Progress kompresi --}}
        <div id="kompresProgress" class="mt-2" style="display:none">
            <div class="d-flex align-items-center gap-2">
                <div class="spinner-border spinner-border-sm text-primary"></div>
                <span id="kompresLabel" style="font-size:.78rem">Memproses foto...</span>
            </div>
            <div class="progress mt-1" style="height:4px">
                <div class="progress-bar" id="kompresBar" style="width:0%"></div>
            </div>
        </div>
        <div class="form-text">Format: JPG, PNG, WEBP. Foto dikompres otomatis (maks ~800KB/foto, kualitas terjaga).</div>
        {{-- Preview grid --}}
        <div id="previewFoto" class="foto-preview-grid mt-3"></div>
        {{-- Info kompresi --}}
        <div id="infoKompresi" class="mt-2" style="display:none">
            <small class="text-success"><i class="bi bi-check-circle me-1"></i><span id="infoKompresiTeks"></span></small>
        </div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        <button type="submit" id="btnSubmitOnline" class="btn btn-primary-custom">
            <i class="bi bi-save me-1"></i>Simpan Eviden
        </button>
        <button type="button" id="btnSimpanOffline" class="btn btn-warning fw-bold" style="display:none"
                onclick="simpanEvidenOffline()">
            <i class="bi bi-cloud-arrow-down me-1"></i>Simpan Offline
        </button>
        <a href="{{ route('eviden.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
    </form>
    </div>
</div>
</div>
</div>
@endsection

@push('styles')
<style>
.foto-preview-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:.65rem;}
.foto-preview-item{position:relative;aspect-ratio:1;border-radius:8px;overflow:hidden;
    border:2px solid #dfe6e9;cursor:zoom-in;}
.foto-preview-item img{width:100%;height:100%;object-fit:cover;display:block;}
.foto-preview-item .fi-info{position:absolute;bottom:0;left:0;right:0;
    background:rgba(0,0,0,.6);color:#fff;font-size:.58rem;padding:.2rem .3rem;text-align:center;}
.foto-preview-item .fi-rm{position:absolute;top:3px;right:3px;background:rgba(220,53,69,.85);
    color:#fff;border:none;border-radius:50%;width:20px;height:20px;font-size:.65rem;
    cursor:pointer;display:flex;align-items:center;justify-content:center;z-index:2;}
.btn-primary-custom{background:var(--primary);color:#fff;border:none;border-radius:7px;padding:.42rem 1.05rem;font-weight:600;font-size:.82rem;}
.btn-primary-custom:hover{opacity:.88;color:#fff;}
</style>
@endpush

@push('scripts')
<script>
// ── Foto processing dengan FotoCompress ──
let processedFotos = []; // [{blob, dataUrl, name, info}]

document.getElementById('inputFoto').addEventListener('change', async function(){
    const files = Array.from(this.files);
    if(!files.length) return;

    const progress = document.getElementById('kompresProgress');
    const bar      = document.getElementById('kompresBar');
    const label    = document.getElementById('kompresLabel');
    const preview  = document.getElementById('previewFoto');
    const infoBox  = document.getElementById('infoKompresi');
    const infoTeks = document.getElementById('infoKompresiTeks');

    progress.style.display = 'block';
    preview.innerHTML = '';
    processedFotos = [];
    let totalOrigKB = 0, totalCompKB = 0;

    const results = await FotoCompress.compressAll(files, {}, (done, total, r) => {
        const pct = Math.round((done / total) * 100);
        bar.style.width   = pct + '%';
        label.textContent = `Memproses foto ${done}/${total}... ${FotoCompress.info(r)}`;
    });

    results.forEach((r, i) => {
        processedFotos.push(r);
        totalOrigKB += r.originalKB;
        totalCompKB += r.compressedKB;

        // Buat preview item
        const div = document.createElement('div');
        div.className = 'foto-preview-item';
        div.innerHTML = `
            <img src="${r.dataUrl}" onclick="bukaLightbox('${r.dataUrl}','${r.name}')">
            <div class="fi-info">${r.compressedKB}KB | ${r.width}×${r.height}</div>
            <button class="fi-rm" onclick="hapusFoto(${i})" title="Hapus">✕</button>
        `;
        preview.appendChild(div);
    });

    progress.style.display = 'none';

    // Info total kompresi
    const saved = totalOrigKB - totalCompKB;
    const pct   = Math.round((saved / totalOrigKB) * 100);
    infoBox.style.display = 'block';
    infoTeks.textContent  = `${results.length} foto: ${totalOrigKB}KB → ${totalCompKB}KB (hemat ${saved}KB / ${pct}%)`;
});

function hapusFoto(idx){
    processedFotos.splice(idx, 1);
    // Re-render preview
    const preview = document.getElementById('previewFoto');
    preview.innerHTML = '';
    processedFotos.forEach((r,i)=>{
        const div=document.createElement('div');div.className='foto-preview-item';
        div.innerHTML=`<img src="${r.dataUrl}" onclick="bukaLightbox('${r.dataUrl}','${r.name}')"><div class="fi-info">${r.compressedKB}KB</div><button class="fi-rm" onclick="hapusFoto(${i})">✕</button>`;
        preview.appendChild(div);
    });
}

// ── Submit Online: inject compressed blobs ke form ──
document.getElementById('formEviden').addEventListener('submit', async function(e){
    if(!navigator.onLine){ e.preventDefault(); return; }
    // Ganti file input dengan blob yang sudah dikompres
    if(processedFotos.length > 0){
        e.preventDefault();
        const fd = new FormData(this);
        // Hapus file input lama
        fd.delete('fotos[]');
        // Tambah blob hasil kompresi
        processedFotos.forEach((r, i) => {
            fd.append('fotos[]', r.blob, r.name || `foto_${i}.jpg`);
        });
        // Submit via fetch
        const res = await fetch(this.action, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        if(res.ok || res.redirected) {
            window.location.href = '{{ route("eviden.index") }}';
        } else {
            alert('Gagal menyimpan. Coba lagi.');
        }
    }
    // Jika tidak ada foto, biarkan form submit normal
});

// ── Offline mode ──
function updateOfflineUI(){
    const isOnline = navigator.onLine;
    document.getElementById('offline-notice')?.style.setProperty('display', isOnline?'none':'flex','important');
    document.getElementById('btnSubmitOnline').style.display  = isOnline ? '' : 'none';
    document.getElementById('btnSimpanOffline').style.display = isOnline ? 'none' : '';
}
window.addEventListener('online',  updateOfflineUI);
window.addEventListener('offline', updateOfflineUI);
document.addEventListener('DOMContentLoaded', updateOfflineUI);

async function simpanEvidenOffline(){
    const judul     = document.getElementById('ev_judul').value;
    const deskripsi = document.getElementById('ev_deskripsi').value;
    const tanggal   = document.getElementById('ev_tanggal').value;
    const jam_mulai = document.getElementById('ev_jam_mulai').value;
    const jam_selesai= document.getElementById('ev_jam_selesai').value;
    const lokasi    = document.getElementById('ev_lokasi').value;
    const supervisi = document.getElementById('ev_supervisi').value;

    if(!judul || !tanggal || !jam_mulai || !jam_selesai){
        alert('Judul, tanggal, jam mulai dan jam selesai wajib diisi!');
        return;
    }

    const operator_ids = Array.from(document.querySelectorAll('.ev-operator:checked')).map(el=>el.value);

    // Simpan foto sebagai blob ke IndexedDB
    const fotosData = processedFotos.map(r => ({
        blob: r.blob,
        keterangan: r.name || '',
    }));

    const data = { judul, deskripsi, tanggal, jam_mulai, jam_selesai, lokasi, supervisi, operator_ids };

    await OfflineDB.add('pending_evidens', { data, fotos: fotosData });
    await SyncManager.updateBadge();
    SyncManager.showToast('✅ Eviden disimpan offline! Foto akan diupload saat online.', 'success');

    setTimeout(()=>{ window.location.href = '{{ route("eviden.index") }}'; }, 1500);
}
</script>
@endpush
