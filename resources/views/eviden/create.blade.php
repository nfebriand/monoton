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
    @if(!empty($perawatan))
    <input type="hidden" name="perawatan_rutin_id" value="{{ $perawatan->id }}">
    <div class="alert alert-info py-2" style="font-size:.82rem">
        <i class="bi bi-link-45deg me-1"></i>
        Eviden ini akan dikaitkan dengan perawatan rutin <strong>{{ $perawatan->nama_tugas }}</strong> &mdash; {{ $perawatan->nama_objek }}.
        Setelah disimpan Anda diarahkan untuk mencatat pelaksanaannya.
    </div>
    @endif

    @include('eviden._form-fields')

    <h6 class="section-title mb-3">&#128247; Foto Dokumentasi</h6>
    @include('components.foto-multi-ket', ['capName' => 'foto_keterangan'])

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

</script>
@endpush
