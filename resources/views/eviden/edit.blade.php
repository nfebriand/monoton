@extends('layouts.app')
@section('title','Edit Eviden')
@section('page-title','Edit Catatan Eviden')

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-xl-9">
<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-pencil-square text-warning"></i>
        Edit: <strong>{{ $eviden->judul }}</strong>
        <a href="{{ route('eviden.show',$eviden) }}" class="btn btn-sm btn-outline-secondary ms-auto">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
    <div class="card-body">
    <form action="{{ route('eviden.update',$eviden) }}" method="POST" enctype="multipart/form-data">
    @csrf @method('PUT')

    <h6 class="section-title mb-3">📋 Informasi Kegiatan</h6>
    <div class="row g-3 mb-4">
        <div class="col-12">
            <label class="form-label">Judul Kegiatan <span class="text-danger">*</span></label>
            <input type="text" name="judul" class="form-control"
                   value="{{ old('judul',$eviden->judul) }}" required>
        </div>
        <div class="col-12">
            <label class="form-label">Deskripsi / Uraian Pekerjaan</label>
            <textarea name="deskripsi" class="form-control" rows="4">{{ old('deskripsi',$eviden->deskripsi) }}</textarea>
        </div>
    </div>

    <h6 class="section-title mb-3">⏰ Waktu & Lokasi</h6>
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <label class="form-label">Tanggal <span class="text-danger">*</span></label>
            <input type="date" name="tanggal" class="form-control"
                   value="{{ old('tanggal',$eviden->tanggal->format('Y-m-d')) }}" required>
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Jam Mulai <span class="text-danger">*</span></label>
            <input type="time" name="jam_mulai" class="form-control mono"
                   value="{{ old('jam_mulai',$eviden->jam_mulai) }}" required>
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Jam Selesai <span class="text-danger">*</span></label>
            <input type="time" name="jam_selesai" class="form-control mono"
                   value="{{ old('jam_selesai',$eviden->jam_selesai) }}" required>
        </div>
        <div class="col-12">
            <label class="form-label">Lokasi Kegiatan</label>
            <input type="text" name="lokasi" class="form-control"
                   value="{{ old('lokasi',$eviden->lokasi) }}">
        </div>
    </div>

    <h6 class="section-title mb-3">👥 Personil Terlibat</h6>
    <div class="row g-3 mb-4">
        <div class="col-12">
            <label class="form-label">Operator Terlibat</label>
            <div class="row g-2">
                @foreach($operators as $op)
                @php $checked = $eviden->operators->contains('id',$op->id); @endphp
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="form-check p-2 rounded"
                         style="background:#f8fafc;border:1px solid {{ $checked?'var(--primary)':'#eee' }};transition:border-color .15s">
                        <input type="checkbox" name="operator_ids[]" value="{{ $op->id }}"
                               id="op{{ $op->id }}" class="form-check-input"
                               {{ $checked || collect(old('operator_ids',[])) ->contains($op->id) ? 'checked' : '' }}
                               onchange="this.closest('.form-check').style.borderColor=this.checked?'var(--primary)':'#eee'">
                        <label for="op{{ $op->id }}" class="form-check-label" style="font-size:.8rem">
                            <div class="fw-600">{{ $op->name }}</div>
                            @if($op->lokasi_dinas)
                            <small class="text-muted">{{ $op->lokasi_dinas }}</small>
                            @endif
                        </label>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        <div class="col-12">
            <label class="form-label">Supervisi / Pengelola / Koordinator yang Hadir</label>
            <input type="text" name="supervisi" class="form-control"
                   value="{{ old('supervisi',$eviden->supervisi) }}"
                   placeholder="Nama orang yang hadir sebagai supervisi (opsional)">
        </div>
    </div>

    {{-- Foto Existing --}}
    @if($eviden->fotos->isNotEmpty())
    <h6 class="section-title mb-3">📷 Foto Saat Ini ({{ $eviden->fotos->count() }}) — klik untuk preview besar</h6>
    <div class="foto-edit-grid mb-4">
        @foreach($eviden->fotos as $foto)
        <div class="foto-edit-item" id="fei-{{ $foto->id }}">
            <img src="{{ $foto->url }}"
                 onerror="this.parentElement.style.background='#eee'"
                 onclick="bukaLightbox('{{ $foto->url }}','{{ addslashes($foto->keterangan??$eviden->judul) }}')"
                 style="width:100%;height:100%;object-fit:cover;cursor:zoom-in;display:block;transition:opacity .15s"
                 onmouseover="this.style.opacity='.88'"
                 onmouseout="this.style.opacity='1'">
            <div class="foto-edit-actions">
                <label class="d-flex align-items-center gap-1 text-white"
                       style="font-size:.68rem;cursor:pointer">
                    <input type="checkbox" name="hapus_foto[]" value="{{ $foto->id }}"
                           class="form-check-input mt-0"
                           onchange="this.closest('.foto-edit-item').classList.toggle('akan-dihapus',this.checked)">
                    Hapus
                </label>
            </div>
            @if($foto->keterangan)
            <div style="position:absolute;bottom:24px;left:0;right:0;
                 background:rgba(0,0,0,.55);color:#fff;font-size:.6rem;
                 padding:.15rem .3rem;text-align:center;
                 white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                {{ $foto->keterangan }}
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    <h6 class="section-title mb-3">📷 Tambah Foto Baru</h6>
    <div class="mb-4">
        <input type="file" name="fotos[]" id="inputFoto" class="form-control"
               multiple accept="image/jpeg,image/png,image/jpg,image/webp">
        <div class="form-text">Format: JPG, PNG, WEBP. Maks 8MB per foto.</div>
        <div id="previewFoto" class="foto-edit-grid mt-3"></div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        <button type="submit" class="btn btn-warning fw-bold">
            <i class="bi bi-save me-1"></i>Simpan Perubahan
        </button>
        <a href="{{ route('eviden.show',$eviden) }}" class="btn btn-outline-secondary">Batal</a>
    </div>
    </form>
    </div>
</div>
</div>
</div>
@endsection

@push('styles')
<style>
.foto-edit-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:.75rem;}
.foto-edit-item{position:relative;aspect-ratio:1;border-radius:8px;overflow:hidden;border:2px solid #dfe6e9;}
.foto-edit-actions{position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,.65);padding:.3rem .5rem;}
.akan-dihapus{opacity:.28;border-color:#ee5a24!important;}
</style>
@endpush

@push('scripts')
<script>
document.getElementById('inputFoto').addEventListener('change',function(){
    const c=document.getElementById('previewFoto');
    c.innerHTML='';
    Array.from(this.files).forEach((f,i)=>{
        const r=new FileReader();
        r.onload=e=>{
            const d=document.createElement('div');
            d.className='foto-edit-item';
            const src=e.target.result;
            d.innerHTML=`<img src="${src}"
                style="width:100%;height:100%;object-fit:cover;cursor:zoom-in;display:block"
                onclick="bukaLightbox('${src}','Preview foto baru ${i+1}')">
                <div class="foto-edit-actions">
                    <span style="font-size:.65rem;color:#fff">Foto baru ${i+1}</span>
                </div>`;
            c.appendChild(d);
        };
        r.readAsDataURL(f);
    });
});
</script>
@endpush