@extends('layouts.app')
@section('title','Tambah Eviden')
@section('page-title','Tambah Catatan Eviden')

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-xl-9">
<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-camera text-primary"></i>Form Catatan Eviden Baru
        <a href="{{ route('eviden.index') }}" class="btn btn-sm btn-outline-secondary ms-auto">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
    <div class="card-body">
    <form action="{{ route('eviden.store') }}" method="POST" enctype="multipart/form-data" id="formEviden">
    @csrf

    @include('eviden._form-fields')

    <h6 class="section-title mb-3">📷 Foto Dokumentasi</h6>
    <div class="mb-4">
        <input type="file" id="inputFoto" class="form-control" multiple accept="image/jpeg,image/png,image/jpg,image/webp">
        <div class="form-text">Format: JPG, PNG, WEBP. Maks 8MB/foto. Dikompres otomatis.</div>
        <div id="previewFoto" class="foto-grid mt-3"></div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Simpan Eviden</button>
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
.foto-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:.65rem;}
.foto-item{position:relative;aspect-ratio:1;border-radius:8px;overflow:hidden;border:2px solid var(--border);}
.foto-item img{width:100%;height:100%;object-fit:cover;display:block;}
.foto-item .fi-info{position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,.6);color:#fff;font-size:.58rem;padding:.2rem .3rem;text-align:center;}
.foto-item .fi-rm{position:absolute;top:3px;right:3px;background:rgba(220,53,69,.85);color:#fff;border:none;border-radius:50%;width:20px;height:20px;font-size:.65rem;cursor:pointer;display:flex;align-items:center;justify-content:center;}
</style>
@endpush

@push('scripts')
<script>
// Lokasi custom toggle
function toggleLokasiCustom(sel){
    const c = document.getElementById('inputLokasiCustom');
    c.style.display = sel.value==='__custom__'?'block':'none';
    if(sel.value==='__custom__') c.focus();
}
// Sebelum submit: jika custom lokasi dipilih, ganti value select
document.getElementById('formEviden').addEventListener('submit', function(e){
    const sel = document.getElementById('selectLokasi');
    const c   = document.getElementById('inputLokasiCustom');
    if(sel.value==='__custom__' && c.value){
        const o=document.createElement('option'); o.value=c.value; o.selected=true;
        sel.appendChild(o); sel.value=c.value;
    }
},{capture:true});

// Foto kompres
let processedFotos = [];
document.getElementById('inputFoto').addEventListener('change', async function(){
    const preview = document.getElementById('previewFoto');
    preview.innerHTML=''; processedFotos=[];
    for(let i=0;i<this.files.length;i++){
        let r;
        if(typeof FotoCompress!=='undefined'){
            r = await FotoCompress.compress(this.files[i]);
        } else {
            const du = await new Promise(res=>{const fr=new FileReader();fr.onload=e=>res(e.target.result);fr.readAsDataURL(this.files[i]);});
            r={blob:this.files[i],dataUrl:du,originalKB:Math.round(this.files[i].size/1024),compressedKB:Math.round(this.files[i].size/1024),name:this.files[i].name};
        }
        processedFotos.push({...r,name:this.files[i].name});
        const d=document.createElement('div'); d.className='foto-item';
        d.innerHTML=`<img src="${r.dataUrl}" onclick="bukaLightbox('${r.dataUrl}','${r.name}')"><div class="fi-info">${r.compressedKB}KB</div><button class="fi-rm" type="button" onclick="hapusFoto(${i})">✕</button>`;
        preview.appendChild(d);
    }
});
function hapusFoto(idx){
    processedFotos.splice(idx,1);
    const c=document.getElementById('previewFoto'); c.innerHTML='';
    processedFotos.forEach((r,i)=>{
        const d=document.createElement('div'); d.className='foto-item';
        d.innerHTML=`<img src="${r.dataUrl}"><div class="fi-info">${r.compressedKB}KB</div><button class="fi-rm" type="button" onclick="hapusFoto(${i})">✕</button>`;
        c.appendChild(d);
    });
}
document.getElementById('formEviden').addEventListener('submit', async function(e){
    if(processedFotos.length>0){
        e.stopImmediatePropagation(); e.preventDefault();
        const fd=new FormData(this);
        fd.delete('fotos[]');
        processedFotos.forEach((r,i)=>fd.append('fotos[]',r.blob,r.name||`foto_${i}.jpg`));
        const res=await fetch(this.action,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}});
        if(res.ok||res.redirected) window.location.href='{{ route("eviden.index") }}';
        else alert('Gagal menyimpan. Coba lagi.');
    }
});
</script>
@endpush
