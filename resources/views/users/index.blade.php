@extends('layouts.app')
@section('title','Manajemen User')
@section('page-title','Manajemen User & Operator')

@section('content')
<div class="d-flex align-items-center gap-2 mb-3">
    <span class="text-muted small">{{ $users->total() }} user terdaftar</span>
    <a href="{{ route('users.create') }}" class="btn btn-primary-custom ms-auto">
        <i class="bi bi-person-plus me-1"></i> Tambah User
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th class="ps-3">Nama</th>
                    <th>NIP</th>
                    <th>Email</th>
                    <th>Lokasi Dinas</th>
                    <th>Role</th>
                    <th class="text-center">Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td class="ps-3">
                        <div class="d-flex align-items-center gap-2">
                            <div style="width:32px;height:32px;border-radius:50%;background:#0a3d62;color:#fff;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;flex-shrink:0">
                                {{ strtoupper(substr($user->name,0,2)) }}
                            </div>
                            <div>
                                <div class="fw-600" style="font-size:.88rem">{{ $user->name }}</div>
                                @if($user->id === auth()->id())
                                <small class="text-muted">(Anda)</small>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="mono" style="font-size:.83rem">{{ $user->nip ?? '–' }}</td>
                    <td style="font-size:.83rem">{{ $user->email }}</td>
                    <td>
                        @if($user->lokasi_dinas)
                        <span class="badge bg-secondary" style="font-size:.72rem">
                            <i class="bi bi-geo-alt me-1"></i>{{ $user->lokasi_dinas }}
                        </span>
                        @else
                        <span class="text-muted">–</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $user->role==='admin'?'bg-danger':'bg-primary' }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $user->is_active?'bg-success':'bg-secondary' }}">
                            {{ $user->is_active?'Aktif':'Nonaktif' }}
                        </span>
                    </td>
                    <td class="pe-3">
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="{{ route('users.edit',$user) }}" class="btn btn-sm btn-outline-warning">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @if($user->id !== auth()->id())
                            <form action="{{ route('users.destroy',$user) }}" method="POST"
                                  onsubmit="return confirm('Hapus user {{ $user->name }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada user</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div class="card-footer">{{ $users->links() }}</div>
    @endif
</div>
@endsection
@push('styles')
<style>.btn-primary-custom{background:#0a3d62;color:#fff;border:none;border-radius:8px;padding:.45rem 1.2rem;font-weight:600;font-size:.875rem;}.btn-primary-custom:hover{background:#1e5f8a;color:#fff;}</style>
@endpush
