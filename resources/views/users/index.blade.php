@extends('layouts.app')
@section('title','Manajemen User')
@section('page-title','Manajemen User')

@section('content')
@php $auth = auth()->user(); @endphp

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        @if($auth->isAdminDivisi())
        <span class="badge bg-secondary">Menampilkan user divisi {{ $auth->divisi_label }}</span>
        @endif
    </div>
    <a href="{{ route('users.create') }}" class="btn btn-primary-custom">
        <i class="bi bi-person-plus me-1"></i>Tambah User
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Nama</th><th>Email</th><th>Divisi</th>
                        <th>Peran</th><th>Lokasi Dinas</th><th>Status</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                    <tr>
                        <td class="ps-3">
                            <div class="fw-bold" style="font-size:.84rem">{{ $u->name }}</div>
                            @if($u->nip)<div class="text-muted mono" style="font-size:.68rem">NIP. {{ $u->nip }}</div>@endif
                        </td>
                        <td style="font-size:.8rem">{{ $u->email }}</td>
                        <td>
                            <span class="badge" style="background:{{ ['transmisi'=>'#0a3d62','studio'=>'#7b1fa2','sarana'=>'#10ac84'][$u->divisi] ?? '#888' }}">
                                {{ $u->divisi_label }}
                            </span>
                        </td>
                        <td>
                            @if($u->isAdmin())
                            <span class="badge bg-dark">Super Admin</span>
                            @elseif($u->isAdminDivisi())
                            <span class="badge bg-warning text-dark">Admin Divisi</span>
                            @else
                            <span class="badge bg-secondary">Operator</span>
                            @endif
                        </td>
                        <td style="font-size:.8rem">{{ $u->lokasi_dinas ?? '–' }}</td>
                        <td>
                            <span class="badge {{ $u->is_active?'bg-success':'bg-secondary' }}">
                                {{ $u->is_active?'Aktif':'Non-aktif' }}
                            </span>
                        </td>
                        <td class="text-end pe-3">
                            <a href="{{ route('users.edit',$u) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                            @if($u->id !== $auth->id)
                            <form action="{{ route('users.destroy',$u) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Hapus user {{ $u->name }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada user</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
        <div class="px-3 py-2 border-top" style="border-color:var(--border)!important">{{ $users->links() }}</div>
        @endif
    </div>
</div>
@endsection
