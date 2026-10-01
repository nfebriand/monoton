<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Eviden — {{ $eviden->judul }}</title>
<style>
@page {
        /* Mengatur margin atas, kanan, bawah, kiri sebesar 1cm */
        margin: 1cm; 
    }
body{font-family:'DejaVu Sans',Arial,sans-serif;margin:0;padding:0;font-size:9pt;color:#1a1a1a;}
.kop{width:100%;border-bottom:3px solid #0a3d62;padding-bottom:8px;margin-bottom:12px;}
.kop-tbl{width:100%;border-collapse:collapse;}
.kop-logo{width:50px;height:50px;background:#0a3d62;border-radius:8px;text-align:center;line-height:50px;font-size:18pt;color:#00d2d3;}
.kop-brand{padding-left:8px;vertical-align:middle;}
.kop-brand h1{font-size:11pt;font-weight:bold;color:#0a3d62;margin:0;}
.kop-brand .sub{font-size:7pt;color:#555;margin-top:2px;}
.kop-brand .satker{font-size:8pt;color:#0a3d62;font-weight:bold;margin-top:2px;}
.kop-meta{text-align:right;vertical-align:middle;font-size:7.5pt;color:#555;}
.kop-meta strong{color:#0a3d62;}
.judul-box{background:#0a3d62;color:#fff;padding:10px 14px;border-radius:6px;margin-bottom:12px;}
.judul-box h2{font-size:12pt;font-weight:bold;margin:0 0 3px;}
.judul-box .sub{font-size:7.5pt;opacity:.85;}
table.info{width:100%;border-collapse:collapse;margin-bottom:12px;}
table.info td{padding:4px 8px;font-size:8.5pt;vertical-align:top;}
table.info td:first-child{color:#555;width:35%;font-weight:600;}
table.info tr{border-bottom:1px solid #f0f0f0;}
.deskripsi-box{background:#f8fafc;border:1px solid #e0e0e0;border-left:4px solid #0a3d62;border-radius:4px;padding:10px 12px;margin-bottom:12px;font-size:8.5pt;line-height:1.6;white-space:pre-wrap;}
.op-tbl{width:100%;border-collapse:collapse;margin-bottom:12px;}
.op-tbl th{background:#0a3d62;color:#fff;padding:5px 8px;text-align:left;font-size:7.5pt;}
.op-tbl td{padding:4px 8px;font-size:8pt;border:1px solid #e0e0e0;}
.op-tbl tr:nth-child(even) td{background:#f7fafc;}
.op-tbl td.ttd-col{text-align:center;padding:4px 6px;vertical-align:middle;}

.foto-grid{width:100%;border-collapse:collapse;margin-bottom:8px;}
.foto-grid td{padding:4px;vertical-align:top;text-align:center;width:33.33%;}
.foto-item{border:1px solid #ddd;border-radius:4px;overflow:hidden;background:#f4f6f8;height:165px;display:table;width:100%;}
.foto-item-inner{display:table-cell;vertical-align:middle;text-align:center;height:165px;}
.foto-item-inner img{max-width:100%;max-height:155px;width:auto;height:auto;display:inline-block;}
.foto-caption{font-size:6.5pt;color:#555;padding:3px 4px;background:#f0f2f5;text-align:center;border-top:1px solid #e2e6ea;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}

.ttd-tbl{width:100%;border-collapse:collapse;margin-top:18px;}
.ttd-cell{width:33%;text-align:center;font-size:8pt;padding:0 10px;vertical-align:top;}
.ttd-space{height:55px;}
.ttd-sign{height:55px;display:flex;align-items:center;justify-content:center;}
.ttd-sign img{max-height:50px;max-width:140px;object-fit:contain;}
.ttd-line{border-top:1px solid #333;padding-top:3px;font-weight:bold;}
.ttd-sub{font-size:7pt;color:#555;margin-top:2px;}
.pg{text-align:right;font-size:6.5pt;color:#aaa;margin-top:10px;border-top:1px solid #eee;padding-top:4px;}
</style>
</head>
<body>
<div class="kop">
<table class="kop-tbl">
<tr>
    <td style="width:58px"><div class="kop-logo">📡</div></td>
    <td class="kop-brand">
        <h1>MonOTOn — Monitoring Operasional Transmisi Online</h1>
        <div class="sub">LAPORAN CATATAN EVIDEN / DOKUMENTASI KEGIATAN</div>
        @if($satkerName)<div class="satker">{{ $satkerName }}</div>@endif
    </td>
    <td class="kop-meta">
        <div>Tanggal: <strong>{{ \Carbon\Carbon::parse($eviden->tanggal)->format('d/m/Y') }}</strong></div>
        <div>Waktu: <strong>{{ substr($eviden->jam_mulai,0,5) }} – {{ substr($eviden->jam_selesai,0,5) }}</strong></div>
        <div>Durasi: <strong>{{ $eviden->durasi }}</strong></div>
        @if(!empty($eviden->divisi))
        <div>Divisi: <strong>{{ \App\Models\User::DIVISI_LABEL[$eviden->divisi] ?? ucfirst($eviden->divisi) }}</strong></div>
        @endif
        <div>Dicetak: <strong>{{ now()->format('d/m/Y H:i') }}</strong></div>
    </td>
</tr>
</table>
</div>

<div class="judul-box">
    <h2>{{ $eviden->judul }}</h2>
    <div class="sub">
        {{ \Carbon\Carbon::parse($eviden->tanggal)->translatedFormat('l, d F Y') }}
        &nbsp;|&nbsp; {{ substr($eviden->jam_mulai,0,5) }} – {{ substr($eviden->jam_selesai,0,5) }}
        @if($eviden->lokasi) &nbsp;|&nbsp; 📍 {{ $eviden->lokasi }} @endif
    </div>
</div>

<table class="info">
<tr><td>Tanggal Kegiatan</td><td>{{ \Carbon\Carbon::parse($eviden->tanggal)->translatedFormat('l, d F Y') }}</td></tr>
<tr><td>Waktu Pelaksanaan</td><td>{{ substr($eviden->jam_mulai,0,5) }} – {{ substr($eviden->jam_selesai,0,5) }} (Durasi: {{ $eviden->durasi }})</td></tr>
@if($eviden->lokasi)
<tr><td>Lokasi Kegiatan</td><td>{{ $eviden->lokasi }}</td></tr>
@endif
<tr><td>Dibuat Oleh</td><td>{{ $eviden->user->name }}{{ $eviden->user->nip ? ' (NIP. '.$eviden->user->nip.')' : '' }}</td></tr>
@if($eviden->supervisi)
<tr><td>Supervisi / Pengelola</td><td><strong>{{ $eviden->supervisi }}</strong></td></tr>
@endif
<tr><td>Jumlah Foto</td><td>{{ $eviden->fotos->count() }} foto</td></tr>
</table>

@if($eviden->operators->isNotEmpty())
<table class="op-tbl">
<thead><tr><th style="width:25px">No</th><th style="width:35%">Nama Operator</th><th style="width:20%">NIP</th><th>Tanda Tangan</th></tr></thead>
<tbody>
@foreach($eviden->operators as $i => $op)
<tr>
    <td style="text-align:center">{{ $i+1 }}</td>
    <td>{{ $op->name }}</td>
    <td>{{ $op->nip ?? '–' }}</td>
    <td style="text-align:center;padding:6px 8px">
        @php
            $opTtdPath = $op->ttd_path ? storage_path('app/public/' . $op->ttd_path) : null;
            $opTtdB64  = '';
            if ($opTtdPath && file_exists($opTtdPath)) {
                $ext = strtolower(pathinfo($opTtdPath, PATHINFO_EXTENSION));
                $mime = match($ext) { 'png'=>'image/png','webp'=>'image/webp',default=>'image/jpeg' };
                $opTtdB64 = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($opTtdPath));
            }
        @endphp
        @if($opTtdB64)
            <img src="{{ $opTtdB64 }}" style="max-height:40px;max-width:120px;object-fit:contain">
        @else
            <span style="color:#ccc;font-size:7pt">Belum ada TTD</span>
        @endif
    </td>
</tr>
@endforeach
</tbody>
</table>
@endif

@if($eviden->deskripsi)
<div style="font-size:8pt;font-weight:bold;color:#0a3d62;margin-bottom:4px;border-left:3px solid #0a3d62;padding-left:6px;">Uraian Kegiatan</div>
<div class="deskripsi-box">{{ $eviden->deskripsi }}</div>
@endif

@if($eviden->fotos->isNotEmpty())
<div style="font-size:8pt;font-weight:bold;color:#0a3d62;margin-bottom:6px;border-left:3px solid #0a3d62;padding-left:6px;">Dokumentasi Foto ({{ $eviden->fotos->count() }} foto)</div>
@php $fotosChunked = $eviden->fotos->chunk(3); @endphp
@foreach($fotosChunked as $row)
<table class="foto-grid">
<tr>
    @foreach($row as $foto)
    @php
        $fotoRel    = $foto->thumb_path ?: $foto->path;
        $fotoBase64 = '';
        if ($fotoRel && \Illuminate\Support\Facades\Storage::disk('public')->exists($fotoRel)) {
            $ext  = strtolower(pathinfo($fotoRel, PATHINFO_EXTENSION));
            $mime = $ext==='png'?'image/png':($ext==='webp'?'image/webp':'image/jpeg');
            $fotoBase64 = 'data:'.$mime.';base64,'.base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($fotoRel));
        }
    @endphp
    <td>
        <div class="foto-item">
            <div class="foto-item-inner">
                @if($fotoBase64)
                <img src="{{ $fotoBase64 }}" alt="Foto">
                @else
                <span style="color:#aaa;font-size:7pt">Foto tidak tersedia</span>
                @endif
            </div>
        </div>
        <div class="foto-caption">{{ $foto->keterangan ?: 'Foto '.($loop->iteration) }}</div>
    </td>
    @endforeach
    @for($i=0;$i<(3-count($row));$i++)<td></td>@endfor
</tr>
</table>
@endforeach
@endif

{{-- ── TANDA TANGAN — Koordinator sesuai DIVISI eviden ── --}}
@php
    $koordinator = $koordinator ?? \App\Models\AppSetting::getKoordinator($eviden->divisi ?? 'transmisi');
@endphp
<table class="ttd-tbl">
<tr>
    <td class="ttd-cell">
        <div>Mengetahui,</div>
        <div>{{ $koordinator['jabatan'] ?: 'Koordinator Teknik' }}</div>

        @if(!empty($koordinator['ttd_base64']))
        <div class="ttd-sign"><img src="{{ $koordinator['ttd_base64'] }}" alt="TTD"></div>
        @else
        <div class="ttd-space"></div>
        @endif


        <div class="ttd-line">{{ $koordinator['nama'] ?: '( _________________________ )' }}</div>
        @if(!empty($koordinator['nip']))<div class="ttd-sub">NIP. {{ $koordinator['nip'] }}</div>@endif
    </td>
    <td class="ttd-cell"></td>
    <td class="ttd-cell">
        <div>{{ \Carbon\Carbon::parse($eviden->tanggal)->translatedFormat('d F Y') }}</div>
        <div>Dibuat Oleh,</div>
        @php
            $pembuatTtdBase64 = $eviden->user->ttd_base64 ?? null;
        @endphp
        @if($pembuatTtdBase64)
        <div class="ttd-sign"><img src="{{ $pembuatTtdBase64 }}" alt="TTD" style="max-height:50px;max-width:140px;object-fit:contain"></div>
        @else
        <div class="ttd-space"></div>
        @endif
        <div class="ttd-line">{{ $eviden->user->name }}</div>
        @if($eviden->user->nip)<div class="ttd-sub">NIP. {{ $eviden->user->nip }}</div>@endif
    </td>
</tr>
</table>
<div class="pg">MonOTOn v{{ $appVersion }} — Monitoring Operasional Transmisi Online{{ $satkerName?' | '.$satkerName:'' }}</div>
</body>
</html>
