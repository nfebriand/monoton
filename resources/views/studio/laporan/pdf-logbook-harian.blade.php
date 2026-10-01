<!DOCTYPE html>
<html><head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans',sans-serif; font-size:9pt; color:#222; }
.page { padding:1cm; }
.header-box { border:1px solid #ccc; margin-bottom:8px; }
.header-top { display:table; width:100%; }
.header-left { display:table-cell; width:75%; padding:8px 10px; vertical-align:middle; }
.header-right { display:table-cell; width:25%; text-align:center; padding:8px; border-left:1px solid #ccc; vertical-align:middle; }
.header-left h3 { font-size:11pt; font-weight:bold; }
.header-left p  { font-size:8.5pt; margin-top:2px; }
table.checklist { width:100%; border-collapse:collapse; margin-top:6px; }
table.checklist th { background:#4a90d9; color:#fff; padding:5px 8px; font-size:8pt; text-align:center; border:1px solid #ccc; }
table.checklist td { padding:5px 8px; font-size:8pt; border:1px solid #ccc; vertical-align:top; }
.col-item { width:50%; }
.col-kondisi { width:15%; }
.col-ket { width:35%; }
.foto-grid { width:100%; margin-top:10px; }
.foto-grid td { border:1px solid #ccc; text-align:center; padding:4px; }
.foto-grid img { max-width:100%; max-height:120px; object-fit:cover; }
.row-header { background:#f0f0f0; }
.row-header td { font-weight:bold; text-align:center; }
.shift-header { background:#4a90d9; color:#fff; }
.shift-sub-header { background:#f39c12; color:#fff; font-weight:bold; }
.catatan-row { background:#fffde7; }
.kondisi-baik     { color:#27ae60; font-weight:bold; }
.kondisi-gangguan { color:#e74c3c; font-weight:bold; }
.kondisi-na       { color:#888; }
.ttd-table { width:100%; border-collapse:collapse; margin-top:20px; }
.ttd-table td { border:none; text-align:center; padding:0 10px; vertical-align:top; font-size:8.5pt; }
.footer-info { margin-top:10px; font-size:7pt; color:#888; text-align:right; }
</style>
</head>
<body>
@foreach($logs as $log)
<div class="page" style="{{ !$loop->first ? 'page-break-before:always;' : '' }}">

<div class="header-box">
    <div class="header-top">
        <div class="header-left">
            <h3>Logbook Laporan Harian Teknik Studio</h3>
            <p>Periode Laporan ({{ $log->tanggal->translatedFormat('F Y') }})</p>
            <p>{{ $settings['satuan_kerja'] ?? 'LPP RRI Bandarlampung' }}</p>
        </div>
    </div>
</div>

<table class="checklist">
    <thead>
        <tr class="shift-header">
            <th style="width:50%">TANGGAL</th>
            <th style="width:25%">Periode Laporan ({{ \Carbon\Carbon::parse($tanggal)->translatedFormat('F Y') }})</th>
            <th style="width:25%">NAMA PETUGAS</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align:center;font-weight:bold">
                {{ $log->tanggal->translatedFormat('l, j F Y') }}
            </td>
            <td style="text-align:center;color:#7b1fa2;font-weight:bold">
                {{ ucfirst($log->shift) }} ({{ substr($log->jam_mulai,0,5) }}{{ $log->jam_selesai?' – '.substr($log->jam_selesai,0,5):'' }})
            </td>
            <td style="text-align:center;font-weight:bold">{{ $log->user->name }}</td>
        </tr>
        {{-- Sub header --}}
        <tr class="shift-sub-header">
            <td colspan="2" style="text-align:center">Aksi</td>
            <td style="text-align:center">Keterangan</td>
        </tr>
        {{-- Checklist items --}}
        @foreach(\App\Models\StudioLog::CHECKLIST_ITEMS as $key => $item)
        @php $kondisi = $log->$key; $ket = $log->{$key.'_ket'}; @endphp
        <tr style="{{ $kondisi==='gangguan'?'background:#fff5f5':'' }}">
            <td class="col-item">
                <strong>{{ $item['label'] }}</strong>
                @if($item['desc'])<br><span style="font-size:7.5pt;color:#666">*{{ $item['desc'] }}</span>@endif
            </td>
            <td class="col-kondisi" style="text-align:center">
                @if($kondisi==='baik')
                <span class="kondisi-baik">Baik</span>
                @elseif($kondisi==='gangguan')
                <span class="kondisi-gangguan">Gangguan</span>
                @else
                <span class="kondisi-na">N/A</span>
                @endif
            </td>
            <td class="col-ket">{{ $ket ?: '' }}</td>
        </tr>
        @endforeach
        {{-- Catatan --}}
        <tr class="catatan-row">
            <td colspan="3" style="font-weight:bold;text-align:center;background:#ffe082">
                Catatan Petugas Dinas (Catatan Berfungsi Untuk Menginfokan Ke Petugas Berikutnya)
            </td>
        </tr>
        <tr class="catatan-row">
            <td colspan="3" style="min-height:40px;padding:8px">
                {{ $log->catatan_petugas ?: '' }}
            </td>
        </tr>
    </tbody>
</table>

@if($log->fotos->isNotEmpty())
<table class="foto-grid">
    <tr>
        @foreach($log->fotos->take(4) as $foto)
        @php
            $fotoRel = $foto->thumb_path ?: $foto->path;
            $fotoB64 = '';
            if ($fotoRel && \Illuminate\Support\Facades\Storage::disk('public')->exists($fotoRel)) {
                $ext  = strtolower(pathinfo($fotoRel, PATHINFO_EXTENSION));
                $mime = $ext==='png'?'image/png':($ext==='webp'?'image/webp':'image/jpeg');
                $fotoB64 = 'data:'.$mime.';base64,'.base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($fotoRel));
            }
        @endphp
        <td style="width:{{ 100/min($log->fotos->count(),4) }}%">
            @if($fotoB64)
            <img src="{{ $fotoB64 }}">
            @endif
            @if($foto->keterangan)
            <div style="font-size:7pt;color:#666;margin-top:2px">{{ $foto->keterangan }}</div>
            @endif
        </td>
        @endforeach
    </tr>
</table>
@endif

<table class="ttd-table">
    <tr>
        <td style="width:50%">
            <div>Mengetahui,</div>
            <div>{{ $koordinator['jabatan'] }}</div>
            @if(!empty($koordinator['ttd_base64']))
            <img src="{{ $koordinator['ttd_base64'] }}" style="height:40px;margin:5px auto;display:block">
            @else
            <div style="height:50px"></div>
            @endif
            <div style="border-top:1px solid #333;padding-top:3px;font-weight:bold">{{ $koordinator['nama'] ?: '___________________' }}</div>
            @if($koordinator['nip'])<div style="font-size:7.5pt;color:#555">NIP. {{ $koordinator['nip'] }}</div>@endif
        </td>
        <td style="width:50%">
            <div>Dibuat oleh,</div>
            <div>Petugas / Operator</div>
            @if(!empty($log->user->ttd_base64))
            <img src="{{ $log->user->ttd_base64 }}" style="height:40px;margin:5px auto;display:block">
            @else
            <div style="height:50px"></div>
            @endif
            <div style="border-top:1px solid #333;padding-top:3px;font-weight:bold">{{ $log->user->name }}</div>
            @if($log->user->nip)<div style="font-size:7.5pt;color:#555">NIP. {{ $log->user->nip }}</div>@endif
        </td>
    </tr>
</table>

<div class="footer-info">Dicetak: {{ now()->format('d/m/Y H:i') }} | MonOTOn v{{ $settings['app_version'] ?? '1.0.0' }}</div>
</div>
@endforeach

@if($logs->isEmpty())
<div class="page" style="text-align:center;padding-top:2cm">
    <p style="color:#888;font-style:italic">Tidak ada data logbook pada tanggal {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}</p>
</div>
@endif
</body></html>
