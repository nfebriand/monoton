@extends('layouts.app')
@section('title','Master Lokasi')
@section('page-title','Master Lokasi Dinas')

@section('content')
@php
    $divisiColor = ['transmisi'=>'#0a3d62','studio'=>'#7b1fa2','sarana'=>'#10ac84','umum'=>'#555'];
    $divisiList  = \App\Models\Lokasi::DIVISI_LABEL;
@endphp

<div class="d-flex justify-content-end mb-3">
    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-circle me-1"></i>Tambah Lokasi
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Nama Lokasi</th><th>Divisi</th>
                        <th>Alamat</th><th>Keterangan</th>
                        <th>Status</th><th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lokasis as $lok)
                    <tr>
                        <td class="ps-3 fw-bold">{{ $lok->nama }}</td>
                        <td><span class="badge" style="background:{{ $divisiColor[$lok->divisi]??'#888' }}">
                            {{ $divisiList[$lok->divisi] ?? $lok->divisi }}
                        </span></td>
                        <td style="font-size:.8rem">{{ $lok->alamat ?? '–' }}</td>
                        <td style="font-size:.8rem">{{ $lok->keterangan ?? '–' }}</td>
                        <td><span class="badge {{ $lok->is_active?'bg-success':'bg-secondary' }}">{{ $lok->is_active?'Aktif':'Non-aktif' }}</span></td>
                        <td class="text-end pe-3">
                            <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalEdit{{ $lok->id }}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form action="{{ route('lokasi.destroy',$lok) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus lokasi?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    {{-- Modal Edit --}}
                    <div class="modal fade" id="modalEdit{{ $lok->id }}" tabindex="-1">
                        <div class="modal-dialog"><div class="modal-content">
                        <form action="{{ route('lokasi.update',$lok) }}" method="POST">
                            @csrf @method('PUT')
                            <div class="modal-header">
                                <h6 class="modal-title">Edit: {{ $lok->nama }}</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">@include('lokasi._form', ['lok'=>$lok])</div>
                            <div class="modal-footer"><button type="submit" class="btn btn-warning fw-bold">Simpan</button></div>
                        </form>
                        </div></div>
                    </div>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data lokasi</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
    <form action="{{ route('lokasi.store') }}" method="POST">
        @csrf
        <div class="modal-header">
            <h6 class="modal-title">Tambah Lokasi Baru</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">@include('lokasi._form', ['lok'=>null])</div>
        <div class="modal-footer"><button type="submit" class="btn btn-primary-custom">Simpan</button></div>
    </form>
    </div></div>
</div>
@endsection
