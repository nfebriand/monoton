@extends('layouts.app')
@section('title','Profil Saya')
@section('page-title','Profil Saya')

@section('content')
@php
    $user = auth()->user();
    $divisiColor = ['transmisi'=>'#0a3d62','studio'=>'#7b1fa2','sarana'=>'#10ac84'][$user->divisi] ?? '#888';
@endphp

<div class="row justify-content-center">
<div class="col-12 col-lg-8">

{{-- Card Identitas --}}
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div style="width:64px;height:64px;border-radius:50%;background:var(--primary);
                 color:#fff;display:flex;align-items:center;justify-content:center;
                 font-weight:700;font-size:1.3rem;flex-shrink:0">
                {{ strtoupper(substr($user->name,0,2)) }}
            </div>
            <div class="flex-fill" style="min-width:0">
                <h5 class="fw-bold mb-1">{{ $user->name }}</h5>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="badge" style="background:{{ $divisiColor }}">{{ $user->divisi_label }}</span>
                    @if($user->isAdmin())
                    <span class="badge bg-dark">Super Admin</span>
                    @elseif($user->isAdminDivisi())
                    <span class="badge bg-warning text-dark">Admin Divisi</span>
                    @else
                    <span class="badge bg-secondary">Operator</span>
                    @endif
                    <span class="badge {{ $user->is_active?'bg-success':'bg-secondary' }}">
                        {{ $user->is_active?'Aktif':'Non-aktif' }}
                    </span>
                </div>
            </div>
            <a href="{{ route('users.change-password') }}" class="btn btn-outline-primary flex-shrink-0">
                <i class="bi bi-key me-1"></i>Ganti Password
            </a>
        </div>
    </div>
</div>

{{-- Card Detail Informasi --}}
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-person-vcard me-2 text-primary"></i>Informasi Akun</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <div class="text-muted" style="font-size:.7rem">NAMA LENGKAP</div>
                <div class="fw-bold">{{ $user->name }}</div>
            </div>
            <div class="col-12 col-md-6">
                <div class="text-muted" style="font-size:.7rem">NIP</div>
                <div class="fw-bold mono">{{ $user->nip ?? '–' }}</div>
            </div>
            <div class="col-12 col-md-6">
                <div class="text-muted" style="font-size:.7rem">EMAIL</div>
                <div class="fw-bold">{{ $user->email }}</div>
            </div>
            <div class="col-12 col-md-6">
                <div class="text-muted" style="font-size:.7rem">LOKASI DINAS</div>
                <div class="fw-bold">{{ $user->lokasi_dinas ?? '–' }}</div>
            </div>
            <div class="col-12 col-md-6">
                <div class="text-muted" style="font-size:.7rem">DIVISI</div>
                <div class="fw-bold">
                    <span class="badge" style="background:{{ $divisiColor }}">{{ $user->divisi_label }}</span>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="text-muted" style="font-size:.7rem">PERAN</div>
                <div class="fw-bold">{{ $user->role_label }}</div>
            </div>
            <div class="col-12 col-md-6">
                <div class="text-muted" style="font-size:.7rem">BERGABUNG SEJAK</div>
                <div class="fw-bold mono">{{ $user->created_at?->format('d F Y') ?? '–' }}</div>
            </div>
        </div>
    </div>
</div>

@include('users.partials.ttd-section')    
    
{{-- Jadwal Shift Mendatang --}}
@if(isset($jadwalMendatang) && $jadwalMendatang->isNotEmpty())
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-calendar3 me-2 text-success"></i>Jadwal Shift Mendatang</div>
    <div class="card-body p-0">
        @foreach($jadwalMendatang as $j)
        <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom" style="border-color:var(--border)!important">
            <div style="width:60px;text-align:center">
                <div class="mono fw-bold" style="font-size:.8rem">{{ \Carbon\Carbon::parse($j->tanggal)->format('d/m') }}</div>
                <div class="text-muted" style="font-size:.62rem">{{ \Carbon\Carbon::parse($j->tanggal)->translatedFormat('D') }}</div>
            </div>
            <span class="badge" style="background:{{ [1=>'#0a3d62',2=>'#10ac84',3=>'#ff9f43'][$j->shift]??'#888' }}">{{ $j->shift_label ?? 'Shift '.$j->shift }}</span>
            <span class="mono text-muted" style="font-size:.78rem">{{ $j->jam_mulai }}–{{ $j->jam_selesai }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Statistik Aktivitas --}}
@if(isset($statistik))
<div class="card">
    <div class="card-header"><i class="bi bi-bar-chart-line me-2 text-warning"></i>Aktivitas Saya (30 Hari Terakhir)</div>
    <div class="card-body">
        <div class="row g-2">
            @foreach($statistik as $label => $val)
            <div class="col-6 col-md-3">
                <div class="p-2 rounded text-center" style="background:var(--bg);border:1px solid var(--border)">
                    <div class="text-muted" style="font-size:.62rem">{{ $label }}</div>
                    <div class="mono fw-bold" style="font-size:1.2rem;color:var(--primary)">{{ $val }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

</div>
</div>
@endsection
