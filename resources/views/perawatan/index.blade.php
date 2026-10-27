@extends('layouts.app')
@section('title','Perawatan Rutin')
@section('page-title','Perawatan Rutin Pemancar & Genset')

@section('content')
@php
    $label = \App\Support\PerawatanHitung::LABEL;
    $warna = \App\Support\PerawatanHitung::WARNA;
@endphp

<div class="row g-2 mb-3">
    @foreach(['terlambat','segera','belum','ok'] as $st)
    <div class="col-6 col-md-3">
        <a href="{{ request()->fullUrlWithQuery(['status' => request('status') === $st ? null : $st]) }}" class="text-decoration-none">
            <div class="card text-center" style="{{ request('status') === $st ? 'outline:2px solid '.$warna[$st] : '' }}">
                <div class="card-body py-2">
                    <div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">{{ $label[$st] }}</div>
                    <div class="mono fw-bold" style="font-size:1.6rem;color:{{ $warna[$st] }}">{{ $hitung[$st] }}</div>
                </div>
            </div>
        </a>
    </div>
    @endforeach
</div>

<div class="d-flex gap-2 flex-wrap align-items-center mb-3">
    <a href="{{ route('perawatan.index') }}" class="btn btn-sm {{ !request('tipe') ? 'btn-primary-custom' : 'btn-outline-secondary' }}">Semua</a>
    <a href="{{ route('perawatan.index', ['tipe' => 'pemancar']) }}" class="btn btn-sm {{ request('tipe')==='pemancar' ? 'btn-primary-custom' : 'btn-outline-secondary' }}"><i class="bi bi-broadcast-pin me-1"></i>Pemancar</a>
    <a href="{{ route('perawatan.index', ['tipe' => 'genset']) }}" class="btn btn-sm {{ request('tipe')==='genset' ? 'btn-primary-custom' : 'btn-outline-secondary' }}"><i class="bi bi-battery-charging me-1"></i>Genset</a>
    <a href="{{ request()->fullUrlWithQuery(['semua' => request('semua') ? null : 1]) }}" class="btn btn-sm btn-outline-secondary">{{ request('semua') ? 'Sembunyikan nonaktif' : 'Tampilkan nonaktif' }}</a>
    @if($bisaKelolaPemancar || $bisaKelolaGenset)
    <a href="{{ route('perawatan.create', array_filter(['tipe' => request('tipe')])) }}" class="btn btn-primary-custom ms-auto"><i class="bi bi-plus-circle me-1"></i>Tambah Jadwal</a>
    @endif
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th class="ps-3">Objek</th>
                        <th>Tugas</th>
                        <th>Interval</th>
                        <th>Terakhir</th>
                        <th>Jatuh Tempo</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($baris as $b)
                    @php $i = $b['item']; $h = $b['hasil']; @endphp
                    <tr style="{{ $i->aktif ? '' : 'opacity:.55' }}">
                        <td class="ps-3">
                            <div class="fw-bold" style="font-size:.85rem">
                                <i class="bi {{ $i->objek_tipe==='pemancar' ? 'bi-broadcast-pin' : 'bi-battery-charging' }} me-1 text-primary"></i>{{ $i->nama_objek }}
                            </div>
                            @if($i->lokasi_objek)<small class="text-muted">{{ $i->lokasi_objek }}</small>@endif
                        </td>
                        <td style="font-size:.82rem">{{ $i->nama_tugas }}@if(!$i->aktif) <span class="badge bg-secondary">nonaktif</span>@endif</td>
                        <td class="mono" style="font-size:.76rem">
                            @if($i->interval_hari){{ $i->interval_hari }} hari @endif
                            @if($i->interval_hari && $i->interval_hm) / @endif
                            @if($i->interval_hm){{ rtrim(rtrim(number_format($i->interval_hm,1,'.',''),'0'),'.') }} jam HM @endif
                        </td>
                        <td class="mono" style="font-size:.76rem">
                            {{ $i->terakhir_tanggal ? $i->terakhir_tanggal->format('d/m/Y') : '-' }}
                            @if($i->terakhir_hm !== null)<br><span class="text-muted">HM {{ rtrim(rtrim(number_format($i->terakhir_hm,1,'.',''),'0'),'.') }}</span>@endif
                        </td>
                        <td style="font-size:.78rem">{{ $h['ringkas'] }}</td>
                        <td><span class="badge" style="background:{{ $warna[$h['status']] }}">{{ $label[$h['status']] }}</span></td>
                        <td class="text-end pe-3">
                            <a href="{{ route('perawatan.show', $i) }}" class="btn btn-sm btn-outline-primary" title="Detail & catat pelaksanaan"><i class="bi bi-check2-square"></i></a>
                            @can('perawatan.kelola', $i)
                            <a href="{{ route('perawatan.edit', $i) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-wrench-adjustable d-block fs-2 mb-1"></i>Belum ada jadwal perawatan rutin
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="form-text mt-2">
    Status "Segera" muncul saat jatuh tempo dalam &plusmn;10% interval (tanggal: 3&ndash;14 hari; HM: minimal 5 jam). Pelaksanaan dicatat dari tombol <i class="bi bi-check2-square"></i>.
</div>
@endsection
