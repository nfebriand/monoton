@extends('layouts.app')
@section('title','Pengaturan Aplikasi')
@section('page-title','Pengaturan Aplikasi')

@section('content')
@php
    $currentVersion = $settings['app_version'] ?? '1.0.0';
    $updateLog      = $settings['update_log'] ?? '';
    // Parse log menjadi array entri terpisah
    $logEntries = array_filter(array_map('trim', preg_split('/\n\n+/', $updateLog)));
@endphp

<div class="row g-3">

{{-- ─── KIRI ──────────────────────────────────────────── --}}
<div class="col-12 col-lg-7">

    {{-- Identitas & Tampilan --}}
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-building me-2 text-primary"></i>Identitas & Tampilan</div>
        <div class="card-body">
        <form action="{{ route('setting.update') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <h6 class="section-title mb-3">🏢 Organisasi</h6>
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
                       value="{{ old('kepala_stasiun', $settings['kepala_stasiun']??'') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Kepala Bidang</label>
                <input type="text" name="kepala_bidang" class="form-control"
                       value="{{ old('kepala_bidang', $settings['kepala_bidang']??'') }}">
            </div>
            <div class="col-12">
                <label class="form-label">Koordinator / Pengelola</label>
                <input type="text" name="koordinator" class="form-control"
                       value="{{ old('koordinator', $settings['koordinator']??'') }}"
                       placeholder="Nama koordinator teknik">
                <div class="form-text">Tampil di kolom "Mengetahui" pada semua laporan PDF.</div>
            </div>
        </div>

        <h6 class="section-title mb-3">🎨 Tampilan</h6>
        <div class="row g-3 mb-4">
            <div class="col-md-5">
                <label class="form-label">Tema Warna Utama</label>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <input type="color" name="tema_warna" id="colorPicker"
                           class="form-control form-control-color"
                           value="{{ old('tema_warna', $settings['tema_warna']??'#0a3d62') }}"
                           style="height:38px;width:60px">
                    <span class="text-muted small">Warna sidebar & aksen</span>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    @foreach(['#0a3d62'=>'Navy','#1a6b3a'=>'Hijau','#7b1fa2'=>'Ungu','#c62828'=>'Merah','#e65100'=>'Oranye','#263238'=>'Slate'] as $clr=>$nama)
                    @php $aktif = ($settings['tema_warna']??'#0a3d62') === $clr; @endphp
                    <span class="tema-preset" data-color="{{ $clr }}" title="{{ $nama }}"
                          style="width:24px;height:24px;border-radius:50%;background:{{ $clr }};cursor:pointer;
                          outline:{{ $aktif?'3px solid '.$clr:'none' }};outline-offset:2px;
                          border:2px solid {{ $aktif?'#fff':'transparent' }}"></span>
                    @endforeach
                </div>
            </div>
            <div class="col-md-7">
                <label class="form-label">Logo Aplikasi</label>
                <div class="d-flex align-items-center gap-3 mb-2">
                    @if(!empty($settings['logo_path']))
                    <img src="{{ asset('uploads/'.$settings['logo_path']) }}"
                         style="height:44px;object-fit:contain;border-radius:6px;border:1px solid #dfe6e9;cursor:zoom-in"
                         onclick="bukaLightbox('{{ asset('uploads/'.$settings['logo_path']) }}','Logo')">
                    @else
                    <div style="width:44px;height:44px;background:#f0f4f8;border-radius:8px;
                         display:flex;align-items:center;justify-content:center;border:1px dashed #ccc">
                        <i class="bi bi-image text-muted"></i>
                    </div>
                    @endif
                    <input type="file" name="logo" class="form-control" accept="image/png,image/jpg,image/jpeg,image/svg+xml">
                </div>
                <div class="form-text">PNG, JPG, SVG. Maks 2MB. Rekomendasi 100×100px.</div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary-custom">
            <i class="bi bi-save me-1"></i>Simpan Pengaturan
        </button>
        </form>
        </div>
    </div>

    {{-- ─── Update Log ─── --}}
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span><i class="bi bi-clock-history me-2 text-info"></i>Riwayat Update Versi</span>
            <span class="badge" style="background:var(--primary);font-size:.8rem">v{{ $currentVersion }}</span>
        </div>
        <div class="card-body">

            {{-- Form tambah update baru --}}
            <form action="{{ route('setting.update-log') }}" method="POST" class="mb-4">
            @csrf
            <div class="p-3 rounded mb-3" style="background:#f8fafc;border:1px solid #eee">
                <div class="fw-bold mb-2" style="font-size:.82rem">
                    <i class="bi bi-plus-circle me-1 text-success"></i>Tambah Catatan Update Baru
                </div>
                <div class="mb-2">
                    <label class="form-label">Tipe Versi</label>
                    <div class="d-flex gap-3 flex-wrap">
                        <div class="form-check">
                            <input type="radio" name="tipe_increment" value="patch"
                                   id="tipePatch" class="form-check-input" checked>
                            <label for="tipePatch" class="form-check-label" style="font-size:.82rem">
                                <strong>Patch</strong>
                                <span class="text-muted">(v{{ $currentVersion }} → v{{ \App\Models\AppSetting::incrementVersion($currentVersion,'patch') }})</span>
                                — Bug fix, perbaikan kecil
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="radio" name="tipe_increment" value="minor"
                                   id="tipeMinor" class="form-check-input">
                            <label for="tipeMinor" class="form-check-label" style="font-size:.82rem">
                                <strong>Minor</strong>
                                <span class="text-muted">(v{{ $currentVersion }} → v{{ \App\Models\AppSetting::incrementVersion($currentVersion,'minor') }})</span>
                                — Fitur baru
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="radio" name="tipe_increment" value="major"
                                   id="tipeMajor" class="form-check-input">
                            <label for="tipeMajor" class="form-check-label" style="font-size:.82rem">
                                <strong>Major</strong>
                                <span class="text-muted">(v{{ $currentVersion }} → v{{ \App\Models\AppSetting::incrementVersion($currentVersion,'major') }})</span>
                                — Perubahan besar
                            </label>
                        </div>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label">Catatan Update <span class="text-danger">*</span></label>
                    <textarea name="catatan_update" class="form-control" rows="3"
                              placeholder="- Perbaikan bug pagination&#10;- Tambah fitur cetak eviden&#10;- Revisi tampilan dashboard"></textarea>
                </div>
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="bi bi-plus-circle me-1"></i>Simpan & Auto-increment Versi
                </button>
            </form>
            </div>

            {{-- Riwayat tersimpan --}}
            @if(!empty($logEntries))
            <div style="max-height:320px;overflow-y:auto">
                @foreach($logEntries as $i => $entry)
                @php
                    $lines = explode("\n", trim($entry), 2);
                    $header = trim($lines[0] ?? '');
                    $body   = trim($lines[1] ?? '');
                    // Cek apakah header berisi versi
                    preg_match('/v(\d+\.\d+\.\d+)/', $header, $m);
                    $versi = $m[1] ?? null;
                @endphp
                <div class="mb-3 pb-3 {{ !$loop->last?'border-bottom':'' }}">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        @if($versi)
                        <span class="badge" style="background:var(--primary);font-size:.72rem">v{{ $versi }}</span>
                        @endif
                        <span style="font-size:.72rem;color:#636e72">{{ trim(preg_replace('/v\d+\.\d+\.\d+/', '', $header)) }}</span>
                        @if($i === 0)
                        <span class="badge bg-success ms-1" style="font-size:.62rem">Terbaru</span>
                        @endif
                    </div>
                    @if($body)
                    <div style="font-size:.78rem;color:#444;line-height:1.55;white-space:pre-line;
                         background:#f8fafc;padding:.5rem .75rem;border-radius:5px;
                         border-left:3px solid var(--primary)">{{ $body }}</div>
                    @endif
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center text-muted py-3 small">
                <i class="bi bi-clock-history d-block fs-3 mb-1"></i>
                Belum ada riwayat update. Tambahkan catatan pertama di atas.
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ─── KANAN ──────────────────────────────────────────── --}}
<div class="col-12 col-lg-5">

    {{-- Info Aplikasi --}}
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-info-circle me-2"></i>Informasi Aplikasi</div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <tr>
                    <td class="ps-3 text-muted" style="width:45%">Nama</td>
                    <td class="fw-bold">MonOTOn</td>
                </tr>
                <tr>
                    <td class="ps-3 text-muted">Versi Saat Ini</td>
                    <td>
                        <span class="badge" style="background:var(--primary);font-size:.8rem">
                            v{{ $currentVersion }}
                        </span>
                    </td>
                </tr>
                <tr><td class="ps-3 text-muted">Framework</td><td>Laravel 10</td></tr>
                <tr><td class="ps-3 text-muted">PHP</td><td class="mono">{{ PHP_VERSION }}</td></tr>
                <tr><td class="ps-3 text-muted">Database</td><td>MariaDB / MySQL</td></tr>
                <tr>
                    <td class="ps-3 text-muted">Satuan Kerja</td>
                    <td style="font-size:.82rem">{{ $settings['satuan_kerja'] ?? '–' }}</td>
                </tr>
                <tr>
                    <td class="ps-3 text-muted">Koordinator</td>
                    <td style="font-size:.82rem">{{ $settings['koordinator'] ?? '–' }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Lokasi Pemancar --}}
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-geo-alt me-2 text-danger"></i>Lokasi Pemancar</div>
        <div class="card-body p-0">
            @php
                $lokasiList = \App\Models\Pemancar::whereNotNull('lokasi')
                    ->select('lokasi', \Illuminate\Support\Facades\DB::raw('count(*) as jumlah'))
                    ->groupBy('lokasi')->orderBy('lokasi')->get();
            @endphp
            @forelse($lokasiList as $lok)
            <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                <span style="font-size:.83rem">
                    <i class="bi bi-broadcast-pin me-2 text-primary"></i>{{ $lok->lokasi }}
                </span>
                <span class="badge bg-primary">{{ $lok->jumlah }} pemancar</span>
            </div>
            @empty
            <div class="text-center text-muted py-3 small">Belum ada data lokasi</div>
            @endforelse
        </div>
    </div>

    {{-- Statistik Sistem --}}
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-bar-chart me-2 text-success"></i>Statistik Sistem</div>
        <div class="card-body p-0">
            @php
                $stats = [
                    ['Pemancar Aktif',  \App\Models\Pemancar::where('is_active',true)->count()],
                    ['Total Operator',  \App\Models\User::where('role','operator')->where('is_active',true)->count()],
                    ['Total Log',       \App\Models\OperasionalLog::count()],
                    ['Total Eviden',    \App\Models\Eviden::count()],
                    ['Total Jadwal',    \App\Models\JadwalShift::count()],
                    ['Total Pengguna',  \App\Models\User::where('is_active',true)->count()],
                ];
            @endphp
            @foreach($stats as [$lbl,$val])
            <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                <span style="font-size:.82rem" class="text-muted">{{ $lbl }}</span>
                <span class="mono fw-bold">{{ number_format($val) }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Quick Links --}}
    <div class="card">
        <div class="card-header"><i class="bi bi-lightning me-2 text-warning"></i>Aksi Cepat</div>
        <div class="card-body d-grid gap-2">
            <a href="{{ route('pemancar.create') }}" class="btn btn-sm btn-outline-primary text-start">
                <i class="bi bi-plus-circle me-2"></i>Tambah Pemancar Baru
            </a>
            <a href="{{ route('users.create') }}" class="btn btn-sm btn-outline-success text-start">
                <i class="bi bi-person-plus me-2"></i>Tambah Operator Baru
            </a>
            <a href="{{ route('jadwal.index') }}" class="btn btn-sm btn-outline-warning text-start">
                <i class="bi bi-calendar3 me-2"></i>Atur Jadwal Shift
            </a>
            <a href="{{ route('laporan.index') }}" class="btn btn-sm btn-outline-danger text-start">
                <i class="bi bi-file-earmark-pdf me-2"></i>Buat Laporan
            </a>
        </div>
    </div>

</div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.tema-preset').forEach(el=>{
    el.addEventListener('click',function(){
        document.getElementById('colorPicker').value=this.dataset.color;
        document.querySelectorAll('.tema-preset').forEach(p=>{
            p.style.outline='none'; p.style.border='2px solid transparent';
        });
        this.style.outline=`3px solid ${this.dataset.color}`;
        this.style.outlineOffset='2px';
        this.style.border='2px solid #fff';
    });
});
</script>
@endpush
