@extends('layouts.app')
@section('title','Kelola Skema Shift')
@section('page-title','Kelola Skema Shift')

@section('content')

@if(session('success'))
<div class="alert alert-success py-2 mb-3">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger py-2 mb-3"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li style="font-size:.82rem">{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
    <div style="font-size:.82rem;color:var(--muted)">
        Skema shift menentukan pilihan shift saat membuat jadwal per divisi.
        Skema <span class="badge bg-secondary">Default</span> tidak bisa dihapus.
    </div>
    <a href="{{ route('skema-shift.create') }}" class="btn btn-primary-custom">
        <i class="bi bi-plus-circle me-1"></i>Buat Skema Baru
    </a>
</div>

@php $divisiLabel = \App\Models\User::DIVISI_LABEL; @endphp
@forelse($skemas as $divisi => $items)
<div class="card mb-3">
    <div class="card-header d-flex align-items-center gap-2">
        <span class="badge" style="background:{{ ['transmisi'=>'#0a3d62','studio'=>'#2e86ab','sarana'=>'#e67e22'][$divisi]??'#888' }}">
            {{ $divisiLabel[$divisi] ?? ucfirst($divisi) }}
        </span>
        <h6 class="mb-0">Skema Shift — {{ $divisiLabel[$divisi] ?? ucfirst($divisi) }}</h6>
        <span class="badge bg-light text-dark border ms-auto">{{ $items->count() }} skema</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Nama Skema</th>
                        <th>Kode</th>
                        <th>Jumlah Shift</th>
                        <th>Detail Shift</th>
                        <th>Status</th>
                        <th>Dibuat</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $skema)
                    <tr style="{{ !$skema->is_active ? 'opacity:.55' : '' }}">
                        <td class="ps-3">
                            <div class="fw-semibold" style="font-size:.85rem">{{ $skema->nama }}</div>
                            @if($skema->is_default)
                            <span class="badge bg-secondary" style="font-size:.65rem">Default</span>
                            @endif
                        </td>
                        <td class="mono" style="font-size:.8rem">{{ $skema->kode }}</td>
                        <td class="text-center mono fw-bold" style="font-size:.9rem">{{ $skema->items->count() }}</td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($skema->items as $item)
                                <span class="badge" style="font-size:.65rem;background:{{ ['#f39c12','#27ae60','#8e44ad','#2c3e50','#2980b9'][$loop->index % 5] }}">
                                    {{ $item->label }}<br>
                                    <small>{{ $item->jam_mulai_short }}–{{ $item->jam_selesai_short }}</small>
                                </span>
                                @endforeach
                            </div>
                        </td>
                        <td>
                            <form action="{{ route('skema-shift.toggle', $skema) }}" method="POST" class="d-inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="badge border-0 {{ $skema->is_active ? 'bg-success' : 'bg-secondary' }}"
                                        style="font-size:.7rem;cursor:pointer">
                                    {{ $skema->is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </form>
                        </td>
                        <td style="font-size:.75rem;color:var(--muted)">
                            {{ $skema->creator?->name ?? 'Sistem' }}<br>
                            {{ $skema->created_at?->format('d/m/Y') ?? '-' }}
                        </td>
                        <td class="text-end pe-3">
                            <a href="{{ route('skema-shift.edit', $skema) }}" class="btn btn-sm btn-outline-warning">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @if(!$skema->is_default)
                            <form action="{{ route('skema-shift.destroy', $skema) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Hapus skema {{ addslashes($skema->nama) }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@empty
<div class="card text-center py-5 text-muted">
    <i class="bi bi-calendar3 fs-1 d-block mb-2"></i>
    Belum ada skema shift terdefinisi.
    <div class="mt-2">
        <a href="{{ route('skema-shift.create') }}" class="btn btn-primary-custom btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Buat Skema Pertama
        </a>
    </div>
</div>
@endforelse

@endsection
