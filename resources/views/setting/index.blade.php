@extends('layouts.app')
@section('title','Pengaturan Aplikasi')
@section('page-title','Pengaturan Aplikasi')

@section('content')

<div class="row g-3">

{{-- ─── KIRI ──────────────────────────────────────────── --}}
<div class="col-12 col-lg-8">

    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-building me-2 text-primary"></i>Identitas Organisasi</div>
        <div class="card-body">
        <form action="{{ route('setting.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row g-3 mb-4">
            <div class="col-12">
                <label class="form-label">Nama Satuan Kerja</label>
                <input type="text" name="satuan_kerja" class="form-control"
                       value="{{ old('satuan_kerja', $settings['satuan_kerja']??'') }}"
                       placeholder="LPPL Radio Lampung">
            </div>
            <div class="col-md-6">
                <label class="form-label">Kepala Stasiun</label>
                <input type="text" name="kepala_stasiun" class="form-control"
                       value="{{ old('kepala_stasiun', $settings['kepala_stasiun']??'') }}"
                       placeholder="Nama kepala stasiun">
            </div>
            <div class="col-md-6">
                <label class="form-label">Kepala Bidang</label>
                <input type="text" name="kepala_bidang" class="form-control"
                       value="{{ old('kepala_bidang', $settings['kepala_bidang']??'') }}"
                       placeholder="Nama kepala bidang">
            </div>
            <div class="col-12">
                <label class="form-label">Koordinator / Pengelola</label>
                <input type="text" name="koordinator" class="form-control"
                       value="{{ old('koordinator', $settings['koordinator']??'') }}"
                       placeholder="Nama koordinator teknik">
                <div class="form-text">Nama ini akan tampil di kolom "Mengetahui" pada laporan PDF.</div>
            </div>
        </div>

        <h6 class="section-title mb-3">🎨 Tampilan Aplikasi</h6>
        <div class="row g-3 mb-4">
            <div class="col-md-5">
                <label class="form-label">Tema Warna Utama</label>
                <div class="d-flex align-items-center gap-2">
                    <input type="color" name="tema_warna" id="colorPicker" class="form-control form-control-color"
                           value="{{ old('tema_warna', $settings['tema_warna']??'#0a3d62') }}"
                           style="height:38px;width:60px">
                    <span class="text-muted small">Warna sidebar & aksen</span>
                </div>
                <div class="d-flex gap-2 mt-2 flex-wrap">
                    @foreach(['#0a3d62'=>'Navy','#1a6b3a'=>'Hijau','#7b1fa2'=>'Ungu','#c62828'=>'Merah','#e65100'=>'Oranye','#263238'=>'Slate'] as $clr=>$nama)
                    @php $aktif = ($settings['tema_warna']??'#0a3d62') === $clr; @endphp
                    <span class="tema-preset" data-color="{{ $clr }}" title="{{ $nama }}"
                          style="width:24px;height:24px;border-radius:50%;background:{{ $clr }};cursor:pointer;
                          outline:{{ $aktif ? '3px solid '.$clr : 'none' }};outline-offset:2px;
                          border:2px solid {{ $aktif ? '#fff' : 'transparent' }}"></span>
                    @endforeach
                </div>
            </div>
            <div class="col-md-7">
                <label class="form-label">Logo Aplikasi</label>
                <div class="d-flex align-items-center gap-3 mb-2">
                    @if(!empty($settings['logo_path']))
                    <img src="{{ asset('storage/'.$settings['logo_path']) }}"
                         style="height:48px;object-fit:contain;border-radius:6px;border:1px solid #dfe6e9">
                    @else
                    <div style="width:48px;height:48px;background:#f0f4f8;border-radius:8px;
                         display:flex;align-items:center;justify-content:center;border:1px dashed #ccc">
                        <i class="bi bi-image text-muted"></i>
                    </div>
                    @endif
                    <input type="file" name="logo" class="form-control" accept="image/png,image/jpg,image/jpeg,image/svg+xml">
                </div>
                <div class="form-text">PNG, JPG, SVG. Maks 2MB. Rekomendasi 100×100px.</div>
            </div>
        </div>

        <h6 class="section-title mb-3">📋 Update Log Versi</h6>
        <div class="mb-4">
            <textarea name="update_log" class="form-control mono" rows="5"
                      style="font-size:.78rem">{{ old('update_log', $settings['update_log']??'') }}</textarea>
            <div class="form-text">Riwayat perubahan fitur aplikasi.</div>
        </div>

        <button type="submit" class="btn btn-primary-custom">
            <i class="bi bi-save me-1"></i>Simpan Pengaturan
        </button>
        </form>
        </div>
    </div>

    {{-- Update Log --}}
    <div class="card">
        <div class="card-header"><i class="bi bi-clock-history me-2 text-info"></i>Riwayat Update</div>
        <div class="card-body">
            <pre style="font-size:.78rem;background:#f8fafc;border-radius:6px;padding:.85rem;
                 border:1px solid #eee;white-space:pre-wrap;max-height:180px;overflow-y:auto;margin:0">{{ $settings['update_log'] ?? 'Belum ada catatan update.' }}</pre>
        </div>
    </div>
</div>

{{-- ─── KANAN ──────────────────────────────────────────── --}}
<div class="col-12 col-lg-4">

    {{-- Info Aplikasi --}}
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-info-circle me-2"></i>Informasi Aplikasi</div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <tr><td class="ps-3 text-muted" style="width:45%">Nama</td><td class="fw-bold">MonOTOn</td></tr>
                <tr><td class="ps-3 text-muted">Versi</td><td class="mono">v{{ $settings['app_version'] ?? '1.0.0' }}</td></tr>
                <tr><td class="ps-3 text-muted">Framework</td><td>Laravel 10</td></tr>
                <tr><td class="ps-3 text-muted">PHP</td><td class="mono">{{ PHP_VERSION }}</td></tr>
                <tr><td class="ps-3 text-muted">Database</td><td>MariaDB / MySQL</td></tr>
            </table>
        </div>
    </div>

    {{-- Lokasi Pemancar --}}
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-geo-alt me-2 text-danger"></i>Lokasi Pemancar Terdaftar</div>
        <div class="card-body p-0">
            @php
                $lokasiList = \App\Models\Pemancar::whereNotNull('lokasi')
                    ->select('lokasi', \Illuminate\Support\Facades\DB::raw('count(*) as jumlah'))
                    ->groupBy('lokasi')->orderBy('lokasi')->get();
            @endphp
            @forelse($lokasiList as $lok)
            <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                <span style="font-size:.85rem"><i class="bi bi-broadcast-pin me-2 text-primary"></i>{{ $lok->lokasi }}</span>
                <span class="badge bg-primary">{{ $lok->jumlah }} pemancar</span>
            </div>
            @empty
            <div class="text-center text-muted py-3 small">Belum ada data lokasi</div>
            @endforelse
        </div>
    </div>

    {{-- Statistik --}}
    <div class="card">
        <div class="card-header"><i class="bi bi-bar-chart me-2 text-success"></i>Statistik Sistem</div>
        <div class="card-body p-0">
            @php
                $statList = [
                    ['Total Pemancar',    \App\Models\Pemancar::count()],
                    ['Total Operator',    \App\Models\User::where('role','operator')->count()],
                    ['Total Log',         \App\Models\OperasionalLog::count()],
                    ['Total Eviden',      \App\Models\Eviden::count()],
                    ['Total Jadwal',      \App\Models\JadwalShift::count()],
                ];
            @endphp
            @foreach($statList as [$lbl,$val])
            <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                <span style="font-size:.83rem" class="text-muted">{{ $lbl }}</span>
                <span class="mono fw-bold">{{ number_format($val) }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.tema-preset').forEach(el=>{
    el.addEventListener('click',function(){
        document.getElementById('colorPicker').value = this.dataset.color;
        document.querySelectorAll('.tema-preset').forEach(p=>{
            p.style.outline='none';
            p.style.border='2px solid transparent';
        });
        this.style.outline=`3px solid ${this.dataset.color}`;
        this.style.outlineOffset='2px';
        this.style.border='2px solid #fff';
    });
});
</script>
@endpush