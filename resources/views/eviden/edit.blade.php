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
    <form action="{{ route('eviden.update',$eviden) }}" method="POST" enctype="multipart/form-data" id="formEvidenEdit">
    @csrf @method('PUT')

    @include('eviden._form-fields')

    {{-- Foto existing --}}
    @if($eviden->fotos->isNotEmpty())
    <h6 class="section-title mb-3">📷 Foto Saat Ini ({{ $eviden->fotos->count() }})</h6>
    <div class="foto-grid mb-4">
        @foreach($eviden->fotos as $foto)
        <div class="foto-item {{ 'akan-hapus-'.$foto->id }}" id="fei{{ $foto->id }}">
            <img src="{{ $foto->url }}" onclick="bukaLightbox('{{ $foto->url }}')"
                 style="cursor:zoom-in;width:100%;height:100%;object-fit:cover"
                 onerror="this.parentElement.style.background='var(--bg)'">
            <label style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,.65);color:#fff;font-size:.65rem;padding:.3rem;display:flex;align-items:center;gap:.4rem;cursor:pointer">
                <input type="checkbox" name="hapus_foto[]" value="{{ $foto->id }}" class="form-check-input mt-0"
                       onchange="document.getElementById('fei{{ $foto->id }}').style.opacity=this.checked?'.3':'1'">
                Hapus
            </label>
        </div>
        @endforeach
    </div>
    @endif

    <h6 class="section-title mb-3">📷 Tambah Foto Baru</h6>
    <div class="mb-4">
        <input type="file" name="fotos[]" id="inputFotoEdit" class="form-control" multiple accept="image/jpeg,image/png,image/jpg,image/webp">
        <div class="form-text">Format: JPG, PNG, WEBP. Dikompres otomatis.</div>
        <div id="previewFotoEdit" class="foto-grid mt-3"></div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-save me-1"></i>Simpan Perubahan</button>
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
.foto-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:.65rem;}
.foto-item{position:relative;aspect-ratio:1;border-radius:8px;overflow:hidden;border:2px solid var(--border);}
</style>
@endpush

@push('scripts')
<script>
function toggleLokasiCustom(sel){
    const c=document.getElementById('inputLokasiCustom');
    c.style.display=sel.value==='__custom__'?'block':'none';
    if(sel.value==='__custom__') c.focus();
}
document.getElementById('formEvidenEdit').addEventListener('submit', function(){
    const sel=document.getElementById('selectLokasi');
    const c=document.getElementById('inputLokasiCustom');
    if(sel.value==='__custom__'&&c.value){
        const o=document.createElement('option');o.value=c.value;o.selected=true;sel.appendChild(o);sel.value=c.value;
    }
},{capture:true});

document.getElementById('inputFotoEdit').addEventListener('change', async function(){
    const c=document.getElementById('previewFotoEdit'); c.innerHTML='';
    for(let i=0;i<this.files.length;i++){
        let src;
        if(typeof FotoCompress!=='undefined'){
            const r=await FotoCompress.compress(this.files[i]); src=r.dataUrl;
        } else {
            src=await new Promise(res=>{const fr=new FileReader();fr.onload=e=>res(e.target.result);fr.readAsDataURL(this.files[i]);});
        }
        const d=document.createElement('div');d.className='foto-item';
        d.innerHTML=`<img src="${src}" style="width:100%;height:100%;object-fit:cover;cursor:zoom-in" onclick="bukaLightbox('${src}')">`;
        c.appendChild(d);
    }
});
</script>
@endpush
