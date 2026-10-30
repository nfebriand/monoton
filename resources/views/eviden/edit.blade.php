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

    <h6 class="section-title mb-3">&#128247; Foto Dokumentasi</h6>
    <div class="mb-4">
        @include('components.foto-multi-ket', ['capName' => 'foto_keterangan_baru', 'existing' => $eviden->fotos])
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

</script>
@endpush
